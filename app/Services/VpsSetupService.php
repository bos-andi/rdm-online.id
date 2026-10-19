<?php

namespace App\Services;

use App\Models\VpsServer;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class VpsSetupService
{
    public const STEPS = [
        ['key' => 'connect', 'label' => 'Koneksi SSH & cek sistem', 'progress' => 8],
        ['key' => 'packages', 'label' => 'Install Nginx, MySQL & Certbot', 'progress' => 30],
        ['key' => 'php', 'label' => 'Install PHP 7.4', 'progress' => 50],
        ['key' => 'ioncube', 'label' => 'Install ionCube Loader', 'progress' => 60],
        ['key' => 'firewall', 'label' => 'Buka firewall (SSH, HTTP, HTTPS)', 'progress' => 66],
        ['key' => 'prepare', 'label' => 'Siapkan direktori deploy', 'progress' => 72],
        ['key' => 'sync', 'label' => 'Sync Deploy (script, template, RDMhosting.zip)', 'progress' => 90],
        ['key' => 'verify', 'label' => 'Verifikasi server', 'progress' => 100],
    ];

    private const APT = 'apt-get -o DPkg::Lock::Timeout=900 -y -q';

    public function __construct(
        private RemoteDeployExecutor $executor,
        private VpsSshKeyService $sshKeys,
        private VpsSpecService $specs,
    ) {}

    public function cacheKey(VpsServer $server): string
    {
        return 'vps-setup:'.$server->id;
    }

    public function state(VpsServer $server): ?array
    {
        return Cache::get($this->cacheKey($server));
    }

    public function isRunning(VpsServer $server): bool
    {
        $state = $this->state($server);

        return $state
            && in_array($state['status'], ['queued', 'running'], true)
            && now()->timestamp - ($state['updated_at'] ?? 0) < 1200;
    }

    public function markQueued(VpsServer $server): void
    {
        $this->save($server, [
            'status' => 'queued',
            'progress' => 0,
            'current_step' => null,
            'completed' => [],
            'error' => null,
            'logs' => ['Menunggu antrian proses...'],
        ]);
    }

    public function payload(VpsServer $server): array
    {
        $state = $this->state($server) ?? [
            'status' => 'idle', 'progress' => 0, 'current_step' => null, 'completed' => [], 'error' => null, 'logs' => [],
        ];

        $steps = collect(self::STEPS)->map(function (array $step) use ($state) {
            $status = 'pending';
            if (in_array($step['key'], $state['completed'], true)) {
                $status = 'completed';
            } elseif ($state['current_step'] === $step['key']) {
                $status = $state['status'] === 'failed' ? 'failed' : 'running';
            }

            return ['key' => $step['key'], 'label' => $step['label'], 'status' => $status];
        })->values();

        return [
            'server' => $server->code,
            'ip' => $server->ip_address,
            'status' => $state['status'],
            'progress' => $state['progress'],
            'error' => $state['error'],
            'steps' => $steps,
            'logs' => array_slice($state['logs'], -12),
        ];
    }

    public function run(VpsServer $server): void
    {
        $this->sshKeys->materializeKeyFile($server);
        $server->refresh();

        $this->save($server, ['status' => 'running']);

        foreach (self::STEPS as $step) {
            $this->save($server, ['current_step' => $step['key']]);
            $this->log($server, $step['label'].'...');

            try {
                $message = $this->{'step'.ucfirst($step['key'])}($server);
            } catch (\Throwable $e) {
                $this->log($server, 'Gagal: '.$this->trimOutput($e->getMessage()));
                $this->save($server, ['status' => 'failed', 'error' => $step['label'].' gagal: '.$this->trimOutput($e->getMessage(), 600)]);

                return;
            }

            $state = $this->state($server);
            $this->log($server, $message ?: $step['label'].' selesai.');
            $this->save($server, [
                'completed' => array_values(array_unique([...$state['completed'], $step['key']])),
                'progress' => $step['progress'],
            ]);
        }

        $this->specs->detectAndStore($server->fresh());
        $this->save($server, ['status' => 'completed', 'current_step' => null, 'progress' => 100]);
        $this->log($server, 'Server '.$server->code.' siap. Klik "Aktifkan untuk Deploy" untuk memakainya.');
    }

    public function markFailed(VpsServer $server, string $error): void
    {
        $this->save($server, ['status' => 'failed', 'error' => $this->trimOutput($error, 600)]);
    }

    private function stepConnect(VpsServer $server): string
    {
        $result = $this->executor->testConnectionDetectingUser($server);
        if (! $result['ok']) {
            throw new \RuntimeException($result['message']);
        }

        try {
            $output = $this->root($server, '. /etc/os-release; echo "OS=$ID|$VERSION_ID"; echo ROOT_OK', 60);
        } catch (\Throwable $e) {
            throw new \RuntimeException('User '.$server->ssh_user.' tidak punya akses sudo tanpa password. Pakai user root, atau tambahkan "'.$server->ssh_user.' ALL=(ALL) NOPASSWD:ALL" di sudoers VPS.');
        }

        if (! preg_match('/OS=([a-z]+)\|([0-9.]+)/', $output, $m) || ! str_contains($output, 'ROOT_OK')) {
            throw new \RuntimeException('Tidak bisa membaca sistem operasi VPS.');
        }

        if ($m[1] !== 'ubuntu' || ! in_array($m[2], ['22.04', '24.04'], true)) {
            throw new \RuntimeException('VPS harus Ubuntu 22.04 atau 24.04 (terdeteksi: '.$m[1].' '.$m[2].').');
        }

        return 'Terhubung sebagai '.$server->ssh_user.' — Ubuntu '.$m[2].'.';
    }

    private function stepPackages(VpsServer $server): string
    {
        $this->root($server, implode("\n", [
            'set -e',
            'export DEBIAN_FRONTEND=noninteractive',
            self::APT.' update',
            self::APT.' install --no-upgrade nginx mysql-server certbot python3-certbot-nginx unzip rsync curl ca-certificates software-properties-common',
            'systemctl enable --now nginx mysql',
        ]), 1500);

        return 'Nginx, MySQL & Certbot terpasang.';
    }

    private function stepPhp(VpsServer $server): string
    {
        $this->root($server, implode("\n", [
            'set -e',
            'export DEBIAN_FRONTEND=noninteractive',
            'if ! ls /etc/apt/sources.list.d/ 2>/dev/null | grep -q ondrej; then add-apt-repository -y ppa:ondrej/php; fi',
            self::APT.' update',
            self::APT.' install --no-upgrade php7.4-fpm php7.4-cli php7.4-common php7.4-mysql php7.4-mbstring php7.4-xml php7.4-curl php7.4-zip php7.4-gd php7.4-json php7.4-xsl',
            'INI=/etc/php/7.4/fpm/php.ini',
            'sed -i -E "s/^upload_max_filesize = 2M$/upload_max_filesize = 10M/; s/^post_max_size = 8M$/post_max_size = 12M/" "$INI"',
            'systemctl enable --now php7.4-fpm',
            'systemctl reload php7.4-fpm',
        ]), 1500);

        return 'PHP 7.4 terpasang.';
    }

    private function stepIoncube(VpsServer $server): string
    {
        if (str_contains($this->root($server, 'php7.4 -v', 60), 'ionCube')) {
            return 'ionCube sudah terpasang.';
        }

        $local = $this->localIoncubeLoader();
        if ($local) {
            $this->root($server, 'cat > /tmp/rdm-ioncube.so', 300, file_get_contents($local));
        } else {
            $this->root($server, implode("\n", [
                'set -e',
                'cd /tmp',
                'curl -fsSL https://downloads.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz -o ioncube.tar.gz',
                'tar xzf ioncube.tar.gz',
                'cp ioncube/ioncube_loader_lin_7.4.so /tmp/rdm-ioncube.so',
                'rm -rf ioncube ioncube.tar.gz',
            ]), 300);
        }

        $output = $this->root($server, implode("\n", [
            'set -e',
            'D=$(php7.4 -r \'echo ini_get("extension_dir");\')',
            'install -m 644 /tmp/rdm-ioncube.so "$D/ioncube_loader_lin_7.4.so"',
            'rm -f /tmp/rdm-ioncube.so',
            'echo "zend_extension=ioncube_loader_lin_7.4.so" > /etc/php/7.4/fpm/conf.d/00-ioncube.ini',
            'cp /etc/php/7.4/fpm/conf.d/00-ioncube.ini /etc/php/7.4/cli/conf.d/00-ioncube.ini',
            'systemctl restart php7.4-fpm',
            'php7.4 -v',
        ]), 120);

        if (! str_contains($output, 'ionCube')) {
            throw new \RuntimeException('ionCube tidak termuat di PHP 7.4.');
        }

        return 'ionCube Loader aktif di PHP 7.4.';
    }

    private function stepFirewall(VpsServer $server): string
    {
        $port = (int) ($server->ssh_port ?: 22);

        $output = $this->root($server, implode("\n", [
            'if command -v ufw >/dev/null 2>&1 && ufw status | grep -q "Status: active"; then',
            "  ufw allow {$port}/tcp >/dev/null && ufw allow 80/tcp >/dev/null && ufw allow 443/tcp >/dev/null && echo UFW_RULES",
            'else',
            '  echo UFW_INACTIVE',
            'fi',
        ]), 60);

        return str_contains($output, 'UFW_RULES')
            ? "UFW: port {$port}, 80 & 443 dibuka."
            : 'UFW tidak aktif — pastikan firewall di portal provider membuka port 80 & 443.';
    }

    private function stepPrepare(VpsServer $server): string
    {
        $base = $server->resolvedDeployBasePath();
        $optRoot = rtrim(dirname($server->resolvedSourceZipPath()), '/');
        $user = $server->ssh_user;

        foreach ([$base, $optRoot] as $path) {
            if (! preg_match('#^/[A-Za-z0-9._/-]+$#', $path) || str_contains($path, '..') || substr_count($path, '/') < 2) {
                throw new \RuntimeException('Path tidak valid: '.$path);
            }
        }

        $dirs = collect([
            dirname($server->deployScriptPath()),
            dirname($server->resolvedSqlTemplatePath()),
            rtrim($server->resolvedSuspendedRootPath(), '/'),
        ])->map(fn ($d) => escapeshellarg($d))->implode(' ');

        $this->root($server, implode("\n", [
            'set -e',
            'mkdir -p '.escapeshellarg($base).' '.escapeshellarg($optRoot).' '.$dirs,
            'chown www-data:www-data '.escapeshellarg($base),
            'chown -R '.escapeshellarg($user).': '.escapeshellarg($optRoot),
            'chmod -R go-w '.escapeshellarg($optRoot),
            'if [ ! -f /etc/nginx/snippets/block-storage-php.conf ]; then',
            "cat > /etc/nginx/snippets/block-storage-php.conf <<'EOF'",
            'location ~ ^/storage/.*\.php$ {',
            '    deny all;',
            '}',
            'EOF',
            'fi',
            'nginx -t',
        ]), 120);

        return 'Direktori '.$base.' & '.$optRoot.' siap.';
    }

    private function stepSync(VpsServer $server): string
    {
        $exitCode = Artisan::call('rdm:sync-vps', ['code' => $server->code]);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            throw new \RuntimeException($output ?: 'Sync gagal.');
        }

        return 'Sync deploy selesai.';
    }

    private function stepVerify(VpsServer $server): string
    {
        $script = escapeshellarg($server->deployScriptPath());

        $output = $this->root($server, implode("\n", [
            'php7.4 -v | grep -qi ioncube && echo CHECK_IONCUBE',
            'for s in nginx php7.4-fpm mysql; do systemctl is-active --quiet $s && echo CHECK_SVC_$s; done',
            'mysql -N -e "SELECT 1" >/dev/null 2>&1 && echo CHECK_MYSQL',
            'test -x '.$script.' && echo CHECK_SCRIPT',
            'test -s '.escapeshellarg($server->resolvedSourceZipPath()).' && echo CHECK_ZIP',
            'test -s '.escapeshellarg($server->resolvedSqlTemplatePath()).' && echo CHECK_SQL',
            'test -e /run/php/php7.4-fpm.sock && echo CHECK_SOCK',
        ]), 120);

        $checks = [
            'CHECK_IONCUBE' => 'ionCube',
            'CHECK_SVC_nginx' => 'Nginx aktif',
            'CHECK_SVC_php7.4-fpm' => 'PHP 7.4 FPM aktif',
            'CHECK_SVC_mysql' => 'MySQL aktif',
            'CHECK_MYSQL' => 'akses MySQL root',
            'CHECK_SCRIPT' => 'deploy script',
            'CHECK_ZIP' => 'RDMhosting.zip',
            'CHECK_SQL' => 'SQL template',
            'CHECK_SOCK' => 'socket PHP 7.4',
        ];

        $missing = collect($checks)->reject(fn ($label, $marker) => str_contains($output, $marker))->values();
        if ($missing->isNotEmpty()) {
            throw new \RuntimeException('Belum siap: '.$missing->implode(', ').'.');
        }

        $sudoCheck = $this->executor->runSsh($server, 'sudo -n env RDM_SETUP_CHECK=1 '.$script.' 2>&1 || true', 60);
        if (! str_contains($sudoCheck, 'Unknown action')) {
            throw new \RuntimeException('Panel belum bisa menjalankan deploy script via sudo: '.$this->trimOutput($sudoCheck));
        }

        return 'Semua pemeriksaan lolos.';
    }

    private function root(VpsServer $server, string $script, int $timeout, ?string $input = null): string
    {
        $prefix = $server->ssh_user === 'root' ? '' : 'sudo -n ';

        return $this->executor->runSsh($server, $prefix.'bash -c '.escapeshellarg($script), $timeout, $input);
    }

    private function localIoncubeLoader(): ?string
    {
        $process = new Process(['php7.4', '-r', 'echo ini_get("extension_dir");']);
        $process->run();
        $path = rtrim(trim($process->getOutput()), '/').'/ioncube_loader_lin_7.4.so';

        return $process->isSuccessful() && is_readable($path) ? $path : null;
    }

    private function log(VpsServer $server, string $message): void
    {
        $state = $this->state($server) ?? [];
        $logs = $state['logs'] ?? [];
        $logs[] = now('Asia/Jakarta')->format('H:i:s').' '.$message;

        $this->save($server, ['logs' => array_slice($logs, -40)]);
    }

    private function save(VpsServer $server, array $changes): void
    {
        $state = array_merge([
            'status' => 'queued', 'progress' => 0, 'current_step' => null, 'completed' => [], 'error' => null, 'logs' => [],
        ], $this->state($server) ?? [], $changes, ['updated_at' => now()->timestamp]);

        Cache::put($this->cacheKey($server), $state, now()->addDay());
    }

    private function trimOutput(string $text, int $limit = 300): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return mb_strlen($text) > $limit ? '…'.mb_substr($text, -$limit) : $text;
    }
}
