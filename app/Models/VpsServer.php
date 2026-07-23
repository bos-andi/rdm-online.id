<?php

namespace App\Models;

use App\Services\VpsSshKeyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VpsServer extends Model
{
    protected $fillable = [
        'code',
        'location',
        'ip_address',
        'vcpu',
        'ram_gb',
        'max_rdm_capacity',
        'deploy_base_path',
        'is_primary',
        'is_active',
        'is_local',
        'accepts_deploy',
        'ssh_user',
        'ssh_port',
        'ssh_key_name',
        'ssh_public_key',
        'ssh_private_key',
        'ssh_private_key_path',
        'deploy_script_path',
        'source_zip_path',
        'sql_template_path',
        'php_fpm_socket',
        'suspended_root_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'is_local' => 'boolean',
            'accepts_deploy' => 'boolean',
            'ssh_private_key' => 'encrypted',
        ];
    }

    public function rdmInstances(): HasMany
    {
        return $this->hasMany(RdmInstance::class);
    }

    public function activeRdmCount(): int
    {
        return $this->rdmInstances()->where('status', 'active')->count();
    }

    public function deployingRdmCount(): int
    {
        return $this->rdmInstances()->whereIn('status', ['active', 'deploying'])->count();
    }

    public function loadPercent(): int
    {
        if ($this->max_rdm_capacity <= 0) {
            return 0;
        }

        return min(100, (int) round(($this->deployingRdmCount() / $this->max_rdm_capacity) * 100));
    }

    public function isLocal(): bool
    {
        return (bool) $this->is_local;
    }

    public function isRemoteReady(): bool
    {
        if ($this->isLocal()) {
            return is_file($this->deployScriptPath());
        }

        if (! $this->hasSshKey() || ! filled($this->ssh_user) || ! filled($this->ip_address)) {
            return false;
        }

        $keyPath = $this->sshPrivateKeyPath();

        return is_file($keyPath) && is_readable($keyPath);
    }

    public function sshPrivateKeyPath(): string
    {
        if ($this->isLocal()) {
            return '';
        }

        $service = app(VpsSshKeyService::class);
        $path = $service->keyFilePath($this);

        if (filled($this->ssh_private_key)) {
            if (! is_file($path) || ! is_readable($path)) {
                $service->materializeKeyFile($this);
            }

            return $path;
        }

        return $this->ssh_private_key_path ?: config('deploy.ssh.private_key_path', '');
    }

    public function hasSshKey(): bool
    {
        return $this->isLocal() || filled($this->ssh_private_key) || (filled($this->ssh_private_key_path) && is_file($this->ssh_private_key_path));
    }

    public function deployScriptPath(): string
    {
        if ($this->deploy_script_path) {
            return $this->deploy_script_path;
        }

        return $this->isLocal()
            ? config('deploy.deploy_script')
            : config('deploy.remote.deploy_script');
    }

    public function resolvedSourceZipPath(): string
    {
        if ($this->source_zip_path) {
            return $this->source_zip_path;
        }

        return $this->isLocal()
            ? config('deploy.source_zip')
            : config('deploy.remote.source_zip');
    }

    public function resolvedSqlTemplatePath(): string
    {
        if ($this->sql_template_path) {
            return $this->sql_template_path;
        }

        return $this->isLocal()
            ? config('deploy.sql_template')
            : config('deploy.remote.sql_template');
    }

    public function resolvedPhpFpmSocket(): string
    {
        return $this->php_fpm_socket ?: config('deploy.php_fpm_socket');
    }

    public function resolvedSuspendedRootPath(): string
    {
        if ($this->suspended_root_path) {
            return $this->suspended_root_path;
        }

        return $this->isLocal()
            ? config('deploy.suspended_root')
            : config('deploy.remote.suspended_root');
    }

    public function resolvedDeployBasePath(): string
    {
        return rtrim($this->deploy_base_path ?: config('deploy.base_path'), '/');
    }

    public static function activeForDeploy(): ?self
    {
        return static::query()
            ->where('is_primary', true)
            ->where('is_active', true)
            ->first();
    }

    public static function selectForDeploy(): self
    {
        $server = static::activeForDeploy();

        if (! $server) {
            throw new \RuntimeException('Belum ada server deploy aktif. Pilih server di Admin → VPS → Aktifkan untuk Deploy.');
        }

        if (! $server->is_active) {
            throw new \RuntimeException('Server deploy '.$server->code.' tidak aktif.');
        }

        if (! $server->isDeployReady()) {
            throw new \RuntimeException('Server '.$server->code.' belum siap deploy. Periksa SSH / sync terlebih dahulu.');
        }

        if ($server->deployingRdmCount() >= $server->max_rdm_capacity) {
            throw new \RuntimeException('Server '.$server->code.' sudah penuh ('.$server->max_rdm_capacity.' RDM). Pilih server lain sebagai deploy aktif.');
        }

        return $server;
    }

    public function isDeployReady(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return $this->isRemoteReady();
    }

    public static function deployIp(): string
    {
        return static::activeForDeploy()?->ip_address ?? config('deploy.server_ip');
    }
}
