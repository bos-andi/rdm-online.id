<?php

namespace App\Services;

use App\Jobs\DeployRdmJob;
use App\Mail\RdmDeployedMail;
use App\Models\RdmInstance;
use App\Models\VpsServer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DeployService
{
    public function __construct(
        private SubdomainService $subdomainService,
        private HostingerDnsService $hostingerDns,
        private RemoteDeployExecutor $executor,
    ) {}

    public function run(RdmInstance $instance): void
    {
        $instance->loadMissing('vpsServer');
        $server = $instance->vpsServer;

        if (! $server) {
            throw new \RuntimeException('Instance tidak terhubung ke server VPS.');
        }

        if (! $server->isLocal()) {
            app(VpsSshKeyService::class)->materializeKeyFile($server);
            $server->refresh();
            $this->executor->beginSession($server);
        }

        try {
            $this->runStepIfNeeded($instance, 'reserve', 5, function () use ($instance, $server) {
                if (! $this->subdomainService->isAvailable($instance->subdomain, $instance->id, forRetry: true)) {
                    throw new \RuntimeException('Subdomain tidak lagi tersedia.');
                }

                if ($this->hostingerDns->isConfigured()) {
                    $this->hostingerDns->createSubdomainARecord($instance->subdomain, $server->ip_address);
                }
            });

            $sourceZip = $server->resolvedSourceZipPath();
            $sqlTemplate = $server->resolvedSqlTemplatePath();

            $this->runScriptIfNeeded($instance, 'extract', 25, [
                'extract',
                $instance->subdomain,
                '', '', '',
                $instance->deploy_path,
                $sourceZip,
            ]);

            $this->runScriptIfNeeded($instance, 'database', 35, [
                'database',
                $instance->subdomain,
                $instance->db_name,
                $instance->db_user,
                $instance->db_password,
                $instance->deploy_path,
                $sourceZip,
            ]);

            $this->runScriptIfNeeded($instance, 'import', 45, [
                'import',
                $instance->subdomain,
                $instance->db_name,
                $instance->db_user,
                $instance->db_password,
                $instance->deploy_path,
                $sourceZip,
                '',
                $sqlTemplate,
            ]);

            $this->runScriptIfNeeded($instance, 'config', 55, [
                'config',
                $instance->subdomain,
                $instance->db_name,
                $instance->db_user,
                $instance->db_password,
                $instance->deploy_path,
                $sourceZip,
            ]);

            $this->runScriptIfNeeded($instance, 'nginx', 70, [
                'nginx',
                $instance->subdomain,
                '', '', '',
                $instance->deploy_path,
                $sourceZip,
            ]);

            try {
                if ($this->stepCompleted($instance, 'ssl')) {
                    $this->logSkippedStep($instance, 'ssl', 85);
                } else {
                    $this->runScript($instance, 'ssl', 85, [
                    'ssl',
                    $instance->subdomain,
                    '', '', '',
                    $instance->deploy_path,
                    $sourceZip,
                    config('deploy.certbot_email'),
                ]);
                }
            } catch (\Throwable $e) {
                $instance->logStep('ssl', 'SSL gagal: '.$e->getMessage().' — lanjut tanpa SSL.', 85, 'warning');
            }

            $this->runStepIfNeeded($instance, 'notify', 95, function () use ($instance) {
                Mail::to($instance->pic_email)->send(new RdmDeployedMail($instance));
            });

            $instance->markActive();
            $instance->logStep('completed', 'Deploy selesai di server '.($server->code ?? '—').'.', 100);
        } catch (\Throwable $e) {
            $instance->markFailed($e->getMessage());
            throw $e;
        } finally {
            if ($server && ! $server->isLocal()) {
                $this->executor->endSession($server);
            }
        }
    }

    public function suspendInstance(RdmInstance $instance): void
    {
        $instance->loadMissing('vpsServer');

        $this->runScriptCommand($instance, [
            'suspend',
            $instance->subdomain,
            '', '', '',
            $instance->deploy_path,
        ]);
    }

    public function restoreInstance(RdmInstance $instance): void
    {
        $instance->loadMissing('vpsServer');
        $server = $instance->vpsServer;

        $this->runScriptCommand($instance, [
            'restore',
            $instance->subdomain,
            '', '', '',
            $instance->deploy_path,
            $server?->resolvedSourceZipPath() ?? config('deploy.source_zip'),
            config('deploy.certbot_email'),
        ]);
    }

    public function destroyInstance(RdmInstance $instance): void
    {
        $instance->loadMissing('vpsServer');
        $server = $instance->vpsServer;

        if (! $server) {
            throw new \RuntimeException('Server VPS instance tidak ditemukan.');
        }

        if (! $server->isLocal()) {
            app(VpsSshKeyService::class)->materializeKeyFile($server);
            $server->refresh();
            $this->executor->beginSession($server);
        }

        try {
            $this->runScriptCommand($instance, [
                'destroy',
                $instance->subdomain,
                $instance->db_name,
                $instance->db_user,
                $instance->db_password,
                $instance->deploy_path,
                $server->resolvedSourceZipPath(),
                config('deploy.certbot_email'),
            ]);
        } finally {
            if (! $server->isLocal()) {
                $this->executor->endSession($server);
            }
        }
    }

    public function createInstance(array $data, ?int $userId = null, string $status = 'deploying'): RdmInstance
    {
        $subdomain = $this->subdomainService->normalize($data['subdomain']);

        RdmInstance::query()
            ->where('subdomain', $subdomain)
            ->where('status', 'failed')
            ->delete();

        if (! $this->subdomainService->isAvailable($subdomain)) {
            throw new \RuntimeException('Subdomain tidak tersedia.');
        }

        $vpsServer = VpsServer::selectForDeploy();
        $basePath = $vpsServer->resolvedDeployBasePath();

        return RdmInstance::create([
            'user_id' => $userId ?? auth()->id(),
            'vps_server_id' => $vpsServer->id,
            'subdomain' => $subdomain,
            'fqdn' => $this->subdomainService->fqdn($subdomain),
            'madrasah_name' => $data['madrasah_name'],
            'jenjang' => $data['jenjang'],
            'pic_email' => $data['pic_email'],
            'package' => $data['package'],
            'deploy_path' => $basePath.'/'.$subdomain,
            'db_name' => 'rdm_'.str_replace('-', '_', $subdomain),
            'db_user' => 'rdm_'.str_replace('-', '_', $subdomain),
            'db_password' => Str::password(20, symbols: false),
            'status' => $status,
            'progress' => 0,
        ]);
    }

    private function runStep(RdmInstance $instance, string $step, int $progress, callable $callback): void
    {
        $label = collect(config('deploy.steps'))->firstWhere('key', $step)['label'] ?? $step;
        $instance->logStep($step, $label.'...', $progress);
        $callback();
        $instance->logStep($step, $label.' selesai.', $progress);
    }

    private function runScript(RdmInstance $instance, string $step, int $progress, array $args): void
    {
        $label = collect(config('deploy.steps'))->firstWhere('key', $step)['label'] ?? $step;
        $instance->logStep($step, $label.'...', $progress);

        $this->runScriptCommand($instance, $args);

        $instance->logStep($step, $label.' selesai.', $progress);
    }

    private function runScriptCommand(RdmInstance $instance, array $args): void
    {
        $server = $instance->vpsServer;

        if (! $server) {
            throw new \RuntimeException('Server VPS instance tidak ditemukan.');
        }

        $this->executor->run($server, $args);
    }

    public function retryDeploy(RdmInstance $instance): void
    {
        if (! $instance->canRetryDeploy()) {
            throw new \RuntimeException('Deploy ini tidak dapat diulang. Pastikan status gagal dan pembayaran sudah lunas.');
        }

        if ($instance->status === 'deploying') {
            throw new \RuntimeException('Deploy sedang berjalan.');
        }

        $server = VpsServer::selectForDeploy();

        $instance->update([
            'status' => 'deploying',
            'progress' => $this->resumeProgress($instance),
            'current_step' => null,
            'error_message' => null,
            'vps_server_id' => $server->id,
            'deploy_path' => $server->resolvedDeployBasePath().'/'.$instance->subdomain,
        ]);

        $instance->logs()->create([
            'step' => 'retry',
            'message' => 'Deploy diulang — pembayaran sebelumnya tetap berlaku.',
            'progress' => 0,
            'level' => 'info',
        ]);

        DeployRdmJob::dispatch($instance->fresh());
    }

    private function stepCompleted(RdmInstance $instance, string $step): bool
    {
        return $instance->logs()
            ->where('step', $step)
            ->where('message', 'like', '%selesai%')
            ->exists();
    }

    private function resumeProgress(RdmInstance $instance): int
    {
        $completed = collect(config('deploy.steps'))
            ->filter(fn (array $step) => $this->stepCompleted($instance, $step['key']));

        if ($completed->isEmpty()) {
            return 0;
        }

        return (int) $completed->last()['progress'];
    }

    private function logSkippedStep(RdmInstance $instance, string $step, int $progress): void
    {
        $label = collect(config('deploy.steps'))->firstWhere('key', $step)['label'] ?? $step;

        $instance->logs()->create([
            'step' => $step,
            'message' => $label.' — dilewati (sudah selesai).',
            'progress' => $progress,
            'level' => 'info',
        ]);

        $instance->update([
            'current_step' => $step,
            'progress' => $progress,
        ]);
    }

    private function runStepIfNeeded(RdmInstance $instance, string $step, int $progress, callable $callback): void
    {
        if ($this->stepCompleted($instance, $step)) {
            $this->logSkippedStep($instance, $step, $progress);

            if ($step === 'reserve' && $this->hostingerDns->isConfigured()) {
                $server = $instance->vpsServer;
                if ($server) {
                    $this->hostingerDns->createSubdomainARecord($instance->subdomain, $server->ip_address);
                }
            }

            return;
        }

        $this->runStep($instance, $step, $progress, $callback);
    }

    private function runScriptIfNeeded(RdmInstance $instance, string $step, int $progress, array $args): void
    {
        if ($this->stepCompleted($instance, $step)) {
            $this->logSkippedStep($instance, $step, $progress);

            return;
        }

        $this->runScript($instance, $step, $progress, $args);
    }
}
