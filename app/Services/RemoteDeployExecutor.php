<?php

namespace App\Services;

use App\Models\VpsServer;
use Symfony\Component\Process\Process;

class RemoteDeployExecutor
{
    /** @var array<int, bool> */
    private array $activeSessions = [];

    public function beginSession(VpsServer $server): void
    {
        if ($server->isLocal() || isset($this->activeSessions[$server->id])) {
            return;
        }

        if (! $server->isRemoteReady()) {
            return;
        }

        $this->ensureControlDir();
        $result = $this->runSshProcess($server, 'true', 30, retries: 8, openMaster: true);

        if ($result['ok']) {
            $this->activeSessions[$server->id] = true;
        }
    }

    public function endSession(VpsServer $server): void
    {
        if ($server->isLocal() || ! isset($this->activeSessions[$server->id])) {
            return;
        }

        unset($this->activeSessions[$server->id]);

        $process = new Process(array_merge(
            ['ssh'],
            $this->sshMultiplexOptions($server),
            ['-O', 'exit'],
            [$server->ssh_user.'@'.$server->ip_address],
        ));
        $process->setTimeout(15);
        $process->run();

        @unlink($this->controlPath($server));
    }

    public function run(VpsServer $server, array $args, array $env = []): string
    {
        if ($server->isLocal()) {
            return $this->runLocal($server, $args, $env);
        }

        return $this->runRemote($server, $args, $env);
    }

    public function testConnection(VpsServer $server): array
    {
        if ($server->isLocal()) {
            return ['ok' => true, 'message' => 'Server lokal — deploy dijalankan langsung di mesin panel.'];
        }

        if (! $server->isRemoteReady()) {
            return ['ok' => false, 'message' => 'SSH belum dikonfigurasi. Buat atau upload key di Admin → VPS.'];
        }

        $result = $this->runSshProcess($server, 'echo rdm-ssh-ok', 30, retries: 8);

        if ($result['ok']) {
            return ['ok' => true, 'message' => 'Koneksi SSH berhasil.'];
        }

        return [
            'ok' => false,
            'message' => $this->friendlySshError($result['error'], $server),
        ];
    }

    public function runSsh(VpsServer $server, string $command): string
    {
        if ($server->isLocal()) {
            $process = new Process(['bash', '-lc', $command]);
            $process->setTimeout(30);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
            }

            return trim($process->getOutput());
        }

        if (! $server->isRemoteReady()) {
            throw new \RuntimeException('SSH belum siap untuk server '.$server->code.'.');
        }

        $result = $this->runSshProcess($server, $command, 120, retries: 8);

        if (! $result['ok']) {
            throw new \RuntimeException($result['error'] ?: 'SSH gagal.');
        }

        return $result['output'];
    }

    /**
     * @return array{ok: bool, error: string, output: string}
     */
    private function runSshProcess(VpsServer $server, string $remoteCommand, int $timeout, int $retries = 1, bool $openMaster = false): array
    {
        $error = '';

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            $command = $this->sshBaseCommand($server, $remoteCommand, $openMaster && $attempt === 1);
            $process = new Process($command);
            $process->setTimeout($timeout);
            $process->run();

            if ($process->isSuccessful()) {
                if ($openMaster) {
                    $this->activeSessions[$server->id] = true;
                }

                return [
                    'ok' => true,
                    'error' => '',
                    'output' => trim($process->getOutput()),
                ];
            }

            $error = trim($process->getErrorOutput() ?: $process->getOutput());

            if ($this->isStaleHostKeyError($error)) {
                $this->forgetHostKey($server->ip_address);
            } elseif (! $this->isTransientSshError($error) || $attempt === $retries) {
                break;
            }

            usleep(500000 * $attempt);
        }

        return ['ok' => false, 'error' => $error, 'output' => ''];
    }

    private function isTransientSshError(string $error): bool
    {
        return str_contains($error, 'Connection reset')
            || str_contains($error, 'Connection closed')
            || str_contains($error, 'kex_exchange_identification');
    }

    private function friendlySshError(string $error, VpsServer $server): string
    {
        if (str_contains($error, 'Permission denied')) {
            return 'VPS menolak koneksi — public key RDM belum terpasang di '
                .$server->ip_address.' (user: '.$server->ssh_user.').';
        }

        if (str_contains($error, 'Connection refused')) {
            return 'SSH port '.$server->ssh_port.' ditolak. Pastikan VPS aktif dan port 22 terbuka di firewall.';
        }

        if (str_contains($error, 'Connection timed out') || str_contains($error, 'timed out')) {
            return 'Timeout ke '.$server->ip_address.'.';
        }

        if ($this->isStaleHostKeyError($error)) {
            return 'Host key VPS berubah. Klik Tes SSH sekali lagi.';
        }

        if ($this->isTransientSshError($error)) {
            return 'Koneksi SSH ke '.$server->ip_address.' tidak stabil (provider VPS). Coba deploy lagi dalam 1–2 menit.';
        }

        if (str_contains($error, 'a password is required') || str_contains($error, 'sudo:')) {
            return 'Sudo di VPS belum dikonfigurasi. Jalankan bootstrap di VPS.';
        }

        return $error ?: 'Koneksi SSH gagal.';
    }

    private function runLocal(VpsServer $server, array $args, array $env): string
    {
        $process = new Process(array_merge(['sudo', $server->deployScriptPath()], $args));
        $process->setTimeout(600);
        $process->setEnv($this->buildEnv($server, $env));
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput() ?: $process->getOutput()));
        }

        return trim($process->getOutput());
    }

    private function runRemote(VpsServer $server, array $args, array $env): string
    {
        if (! $server->isRemoteReady()) {
            throw new \RuntimeException('Server '.$server->code.' belum siap deploy remote (SSH/key/path).');
        }

        $remoteCommand = $this->buildRemoteShellCommand($server, $args, $env);
        $result = $this->runSshProcess($server, $remoteCommand, 600, retries: 8);

        if (! $result['ok']) {
            throw new \RuntimeException($result['error'] ?: 'Deploy remote gagal.');
        }

        return $result['output'];
    }

    /**
     * @return array<int, string>
     */
    private function sshBaseCommand(VpsServer $server, string $remoteCommand, bool $openMaster = false): array
    {
        $base = [
            'ssh',
            '-i', $server->sshPrivateKeyPath(),
            '-p', (string) $server->ssh_port,
            '-o', 'BatchMode=yes',
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ConnectTimeout=30',
            '-o', 'ServerAliveInterval=15',
            '-o', 'ServerAliveCountMax=10',
        ];

        if ($openMaster) {
            return array_merge($base, $this->sshMultiplexOptions($server, master: true), [
                '-f', '-N',
                $server->ssh_user.'@'.$server->ip_address,
            ]);
        }

        $multiplex = $this->hasSession($server)
            ? $this->sshMultiplexOptions($server)
            : [];

        return array_merge($base, $multiplex, [
            $server->ssh_user.'@'.$server->ip_address,
            $remoteCommand,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function sshMultiplexOptions(VpsServer $server, bool $master = false): array
    {
        return [
            '-o', 'ControlMaster='.($master ? 'yes' : 'auto'),
            '-o', 'ControlPath='.$this->controlPath($server),
            '-o', 'ControlPersist=600',
        ];
    }

    private function hasSession(VpsServer $server): bool
    {
        return isset($this->activeSessions[$server->id])
            && is_file($this->controlPath($server));
    }

    private function controlPath(VpsServer $server): string
    {
        return storage_path('deploy/ssh/ctl-'.$server->id);
    }

    private function ensureControlDir(): void
    {
        $dir = storage_path('deploy/ssh');

        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
    }

    private function buildRemoteShellCommand(VpsServer $server, array $args, array $env): string
    {
        $script = escapeshellarg($server->deployScriptPath());
        $escapedArgs = collect($args)->map(fn ($arg) => escapeshellarg((string) $arg))->implode(' ');

        $envPrefix = collect($this->buildEnv($server, $env))
            ->map(fn ($value, $key) => $key.'='.escapeshellarg((string) $value))
            ->implode(' ');

        return trim('sudo -n env '.$envPrefix.' '.$script.' '.$escapedArgs);
    }

    private function knownHostsFile(): string
    {
        return '/var/www/.ssh/known_hosts';
    }

    private function isStaleHostKeyError(string $error): bool
    {
        return str_contains($error, 'REMOTE HOST IDENTIFICATION HAS CHANGED')
            || str_contains($error, 'Host key verification failed');
    }

    private function forgetHostKey(string $ip): void
    {
        $file = $this->knownHostsFile();

        if (! is_file($file)) {
            return;
        }

        $process = new Process(['ssh-keygen', '-f', $file, '-R', $ip]);
        $process->run();
    }

    private function buildEnv(VpsServer $server, array $extra): array
    {
        return array_merge([
            'DEPLOY_PHP_FPM_SOCKET' => $server->resolvedPhpFpmSocket(),
            'DEPLOY_SQL_TEMPLATE' => $server->resolvedSqlTemplatePath(),
            'SUSPENDED_ROOT' => $server->resolvedSuspendedRootPath(),
        ], $extra);
    }
}
