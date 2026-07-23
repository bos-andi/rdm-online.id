<?php

namespace App\Console\Commands;

use App\Models\VpsServer;
use App\Services\VpsSshKeyService;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class SyncVpsServerCommand extends Command
{
    protected $signature = 'rdm:sync-vps {code : Kode server VPS, contoh ID-02}';

    protected $description = 'Sinkronkan deploy script, zip RDM, dan SQL template ke VPS remote';

    public function handle(VpsSshKeyService $sshKeys): int
    {
        $server = VpsServer::query()->where('code', strtoupper($this->argument('code')))->first();

        if (! $server) {
            $this->error('Server tidak ditemukan.');

            return self::FAILURE;
        }

        if ($server->isLocal()) {
            $this->warn('Server lokal — tidak perlu sync via SSH.');

            return self::SUCCESS;
        }

        if (! $server->isRemoteReady()) {
            $this->error('SSH/key belum siap. Buat key di Admin → VPS atau upload private key.');

            return self::FAILURE;
        }

        $sshKeys->materializeKeyFile($server);
        $server->refresh();

        $target = $server->ssh_user.'@'.$server->ip_address;
        $remoteRoot = rtrim(dirname($server->resolvedSourceZipPath()), '/');
        $remoteScript = $server->deployScriptPath();
        $remoteZip = $server->resolvedSourceZipPath();
        $remoteSql = $server->resolvedSqlTemplatePath();
        $remoteSuspended = rtrim($server->resolvedSuspendedRootPath(), '/').'/index.html';

        $this->info("Sync ke {$server->code} ({$target})...");

        $mkdirCmd = 'mkdir -p '
            .escapeshellarg(dirname($remoteScript)).' '
            .escapeshellarg(dirname($remoteSql)).' '
            .escapeshellarg(dirname($remoteSuspended));

        if (! $this->runSsh($server, $mkdirCmd)) {
            return self::FAILURE;
        }

        $smallFiles = [
            [base_path('scripts/rdm-deploy.sh'), $remoteScript],
            [config('deploy.sql_template'), $remoteSql],
            [storage_path('deploy/suspended/index.html'), $remoteSuspended],
        ];

        foreach ($smallFiles as [$local, $remote]) {
            if (! is_file($local)) {
                $this->warn('Lewati (tidak ada): '.basename($local));

                continue;
            }

            $this->line('→ '.basename($local));

            if (! $this->runRsync($server, $local, $remote)) {
                return self::FAILURE;
            }
        }

        $zipUrl = rtrim(config('app.url', 'https://rdm-online.id'), '/').'/RDMhosting.zip';
        $this->line('→ RDMhosting.zip (unduh di VPS dari '.$zipUrl.')');

        $downloadCmd = 'curl -fsSL '.escapeshellarg($zipUrl).' -o '.escapeshellarg($remoteZip.'.tmp')
            .' && mv '.escapeshellarg($remoteZip.'.tmp').' '.escapeshellarg($remoteZip);

        if (! $this->runSsh($server, $downloadCmd, timeout: 600)) {
            $this->warn('Unduh via curl gagal, coba rsync zip...');

            if (! is_file(config('deploy.source_zip'))) {
                $this->error('RDMhosting.zip tidak ditemukan di panel.');

                return self::FAILURE;
            }

            if (! $this->runRsync($server, config('deploy.source_zip'), $remoteZip, timeout: 900)) {
                return self::FAILURE;
            }
        }

        if (! $this->runSsh($server, 'chmod +x '.escapeshellarg($remoteScript))) {
            return self::FAILURE;
        }

        $this->info('Sync selesai.');

        return self::SUCCESS;
    }

    private function runSsh(VpsServer $server, string $remoteCommand, int $timeout = 60, int $retries = 3): bool
    {
        $target = $server->ssh_user.'@'.$server->ip_address;

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            $process = new Process(array_merge(
                ['ssh'],
                $this->sshOptions($server),
                [$target, $remoteCommand]
            ));
            $process->setTimeout($timeout);
            $process->run();

            if ($process->isSuccessful()) {
                return true;
            }

            $error = trim($process->getErrorOutput() ?: $process->getOutput());

            if ($attempt < $retries && $this->isTransient($error)) {
                $this->warn("SSH gagal (percobaan {$attempt}/{$retries}), ulang...");
                usleep(1000000);

                continue;
            }

            $this->error($error ?: 'SSH gagal.');

            return false;
        }

        return false;
    }

    private function runRsync(VpsServer $server, string $local, string $remote, int $timeout = 300, int $retries = 3): bool
    {
        $target = $server->ssh_user.'@'.$server->ip_address;
        $sshCmd = 'ssh '.implode(' ', array_map('escapeshellarg', $this->sshOptions($server)));

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            $process = new Process([
                'rsync', '-avz', '--partial', '--timeout=120',
                '-e', $sshCmd,
                $local,
                $target.':'.$remote,
            ]);
            $process->setTimeout($timeout);
            $process->run();

            if ($process->isSuccessful()) {
                return true;
            }

            $error = trim($process->getErrorOutput() ?: $process->getOutput());

            if ($attempt < $retries && $this->isTransient($error)) {
                $this->warn("rsync gagal (percobaan {$attempt}/{$retries}), ulang...");
                usleep(1000000);

                continue;
            }

            $this->error($error ?: 'rsync gagal.');

            return false;
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function sshOptions(VpsServer $server): array
    {
        return [
            '-i', $server->sshPrivateKeyPath(),
            '-p', (string) $server->ssh_port,
            '-o', 'BatchMode=yes',
            '-o', 'StrictHostKeyChecking=accept-new',
            '-o', 'ConnectTimeout=30',
            '-o', 'ServerAliveInterval=15',
            '-o', 'ServerAliveCountMax=8',
        ];
    }

    private function isTransient(string $error): bool
    {
        return str_contains($error, 'Connection reset')
            || str_contains($error, 'Connection closed')
            || str_contains($error, 'kex_exchange_identification')
            || str_contains($error, 'unexpectedly closed');
    }
}
