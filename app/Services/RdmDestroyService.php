<?php

namespace App\Services;

use App\Models\RdmInstance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RdmDestroyService
{
    public function __construct(
        private DeployService $deployService,
        private HostingerDnsService $dns,
    ) {}

    public function queueDestroy(RdmInstance $instance): void
    {
        if ($instance->status === 'destroying') {
            throw new \RuntimeException('Penghapusan sedang berjalan.');
        }

        Cache::put(
            $this->needsServerCacheKey($instance->id),
            $this->resolveNeedsServerCleanup($instance),
            now()->addHour(),
        );

        $instance->update([
            'status' => 'destroying',
            'progress' => 0,
            'current_step' => 'server',
            'error_message' => null,
        ]);

        $instance->logs()->create([
            'step' => 'destroy',
            'message' => 'Penghapusan permanen dimulai...',
            'progress' => 0,
            'level' => 'info',
        ]);

        \App\Jobs\DestroyRdmJob::dispatch($instance->fresh());
    }

    public function destroyWithProgress(RdmInstance $instance): void
    {
        $instance->loadMissing('vpsServer');
        $fqdn = $instance->fqdn;
        $needsServer = (bool) Cache::get($this->needsServerCacheKey($instance->id), false);

        try {
            if ($needsServer) {
                $this->runStep($instance, 'server', 50, function () use ($instance) {
                    $this->destroyOnServer($instance);
                });
            } else {
                $this->logSkippedStep($instance, 'server', 50);
            }

            $this->runStep($instance, 'dns', 80, function () use ($instance) {
                if ($this->dns->isConfigured()) {
                    $this->dns->deleteSubdomainRecord($instance->subdomain);
                }
            });

            Cache::put($this->destroyedCacheKey($instance->id), $fqdn, now()->addMinutes(10));
            Cache::forget($this->needsServerCacheKey($instance->id));

            $this->runStep($instance, 'panel', 100, function () use ($instance) {
                $this->finalizePanelCleanup($instance);
            }, skipPostLog: true);

            Log::info('RDM dihapus permanen: '.$fqdn);
        } catch (\Throwable $e) {
            $this->recordDestroyFailure($instance, $fqdn, $e);

            throw $e;
        }
    }

    public function destroy(RdmInstance $instance, bool $deleteDns = true): void
    {
        if ($this->resolveNeedsServerCleanup($instance)) {
            $this->destroyOnServer($instance);
        }

        if ($deleteDns && $this->dns->isConfigured()) {
            try {
                $this->dns->deleteSubdomainRecord($instance->subdomain);
            } catch (\Throwable $e) {
                Log::warning('Destroy DNS gagal untuk '.$instance->fqdn.': '.$e->getMessage());
            }
        }

        $this->finalizePanelCleanup($instance);
    }

    public function destroyedCacheKey(int $instanceId): string
    {
        return 'rdm-destroyed:'.$instanceId;
    }

    public function needsServerCacheKey(int $instanceId): string
    {
        return 'rdm-destroy-needs-server:'.$instanceId;
    }

    private function destroyOnServer(RdmInstance $instance): void
    {
        try {
            $this->deployService->destroyInstance($instance);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Unknown action: destroy')) {
                $this->syncDeployScript($instance);
                $this->deployService->destroyInstance($instance->fresh(['vpsServer']));
            } else {
                throw $e;
            }
        }
    }

    private function finalizePanelCleanup(RdmInstance $instance): void
    {
        $instance->payments()->each(function ($payment) use ($instance) {
            $meta = $payment->meta ?? [];
            $meta['purged_fqdn'] = $instance->fqdn;
            $meta['purged_at'] = now()->toIso8601String();
            $payment->update(['meta' => $meta]);
        });

        $instance->logs()->delete();
        $instance->delete();
    }

    private function runStep(RdmInstance $instance, string $step, int $progress, callable $callback, bool $skipPostLog = false): void
    {
        $label = collect(config('deploy.destroy_steps'))->firstWhere('key', $step)['label'] ?? $step;

        $instance->update(['current_step' => $step, 'progress' => $progress]);
        $instance->logs()->create([
            'step' => $step,
            'message' => $label.'...',
            'progress' => $progress,
            'level' => 'info',
        ]);

        $callback();

        if ($skipPostLog || ! RdmInstance::query()->whereKey($instance->id)->exists()) {
            return;
        }

        $instance->logs()->create([
            'step' => $step,
            'message' => $label.' selesai.',
            'progress' => $progress,
            'level' => 'info',
        ]);
    }

    private function recordDestroyFailure(RdmInstance $instance, string $fqdn, \Throwable $e): void
    {
        if (! RdmInstance::query()->whereKey($instance->id)->exists()) {
            Cache::put($this->destroyedCacheKey($instance->id), $fqdn, now()->addMinutes(10));
            Cache::forget($this->needsServerCacheKey($instance->id));

            return;
        }

        $instance->refresh();
        $instance->update([
            'status' => 'failed',
            'error_message' => 'Gagal menghapus: '.\Illuminate\Support\Str::limit($e->getMessage(), 500),
        ]);

        $instance->logs()->create([
            'step' => $instance->current_step ?? 'destroy',
            'message' => \Illuminate\Support\Str::limit($e->getMessage(), 500),
            'progress' => $instance->progress,
            'level' => 'error',
        ]);
    }

    private function logSkippedStep(RdmInstance $instance, string $step, int $progress): void
    {
        $label = collect(config('deploy.destroy_steps'))->firstWhere('key', $step)['label'] ?? $step;

        $instance->update(['current_step' => $step, 'progress' => $progress]);
        $instance->logs()->create([
            'step' => $step,
            'message' => $label.' — dilewati (belum terdeploy di server).',
            'progress' => $progress,
            'level' => 'info',
        ]);
    }

    private function resolveNeedsServerCleanup(RdmInstance $instance): bool
    {
        if (in_array($instance->status, ['active', 'expired', 'suspended'], true)) {
            return true;
        }

        if (in_array($instance->status, ['deploying', 'failed'], true)) {
            return $instance->progress > 0
                || $instance->logs()->where('message', 'like', '%selesai%')->exists();
        }

        return false;
    }

    private function syncDeployScript(RdmInstance $instance): void
    {
        $code = $instance->vpsServer?->code;

        if (! $code) {
            throw new \RuntimeException('Server VPS tidak ditemukan untuk sync script.');
        }

        $exitCode = \Illuminate\Support\Facades\Artisan::call('rdm:sync-vps', ['code' => $code]);

        if ($exitCode !== 0) {
            throw new \RuntimeException('Gagal sync deploy script ke '.$code.': '.trim(\Illuminate\Support\Facades\Artisan::output()));
        }
    }
}
