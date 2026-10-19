<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\VpsServer;
use App\Models\DeployLog;
use App\Models\Payment;
use App\Models\RdmInstance;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

class AdminPanelService
{
    public function __construct(
        private HostingerDnsService $dns,
    ) {}

    public function dashboardStats(): array
    {
        return [
            'totalCustomers' => User::query()->count(),
            'activeRdm' => RdmInstance::query()->where('status', 'active')->count(),
            'deploying' => RdmInstance::query()->where('status', 'deploying')->count(),
            'failed' => RdmInstance::query()->where('status', 'failed')->count(),
            'recentLogs' => DeployLog::query()->with('rdmInstance')->latest()->limit(10)->get(),
        ];
    }

    public function vpsServers(): array
    {
        $liveSpecs = $this->serverSpecs();

        return VpsServer::query()
            ->withCount(['rdmInstances as active_rdm_count' => fn ($q) => $q->where('status', 'active')])
            ->withCount(['rdmInstances as deploying_rdm_count' => fn ($q) => $q->whereIn('status', ['active', 'deploying'])])
            ->orderByDesc('is_local')
            ->orderBy('code')
            ->get()
            ->map(function (VpsServer $server) use ($liveSpecs) {
                $active = $server->active_rdm_count;
                $deploying = $server->deploying_rdm_count;
                $max = $server->max_rdm_capacity;
                $load = $max > 0 ? min(100, (int) round(($deploying / $max) * 100)) : 0;
                $isLocal = $server->is_local;
                $deployReady = $server->isDeployReady();

                return [
                    'id' => $server->id,
                    'name' => $server->code,
                    'location' => $server->location,
                    'ip' => $server->ip_address,
                    'spec' => $server->vcpu > 0
                        ? $server->vcpu.' vCPU • '.$server->ram_gb.'GB RAM'
                        : 'vCPU/RAM — deteksi via Tes SSH',
                    'load' => $load,
                    'count' => $active.'/'.$max,
                    'disk_used' => $isLocal ? $liveSpecs['disk_used'] : '—',
                    'disk_total' => $isLocal ? $liveSpecs['disk_total'] : '—',
                    'uptime' => $isLocal ? $liveSpecs['uptime'] : '—',
                    'is_primary' => $server->is_primary,
                    'is_local' => $server->is_local,
                    'accepts_deploy' => $server->accepts_deploy,
                    'deploy_ready' => $deployReady,
                    'is_active' => $server->is_active,
                    'notes' => $server->notes,
                    'deploy_base_path' => $server->deploy_base_path,
                    'ssh_user' => $server->ssh_user,
                    'ssh_port' => $server->ssh_port,
                    'ssh_key_name' => $server->ssh_key_name,
                    'ssh_public_key' => $server->ssh_public_key,
                    'has_ssh_key' => $server->hasSshKey(),
                ];
            })
            ->all();
    }

    public function dnsOverview(): array
    {
        $domain = config('deploy.domain');
        $records = $this->dns->isConfigured() ? $this->dns->getRecords() : [];
        $subdomainRecords = collect($records)->filter(function (array $record) {
            $name = $record['name'] ?? '';
            $type = strtoupper($record['type'] ?? '');

            return $name !== '@' && in_array($type, ['A', 'AAAA', 'CNAME'], true);
        })->values();

        return [
            'domain' => $domain,
            'apiConfigured' => $this->dns->isConfigured(),
            'apiOnline' => $this->dns->isConfigured() && count($records) > 0,
            'totalRecords' => count($records),
            'subdomainRecords' => $subdomainRecords,
            'serverIp' => config('deploy.server_ip'),
        ];
    }

    public function databaseOverview(): array
    {
        $databases = collect(DB::select(
            "SELECT table_schema AS name,
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
             FROM information_schema.tables
             WHERE table_schema LIKE 'rdm\\_%' OR table_schema = ?
             GROUP BY table_schema
             ORDER BY table_schema",
            [config('database.connections.mysql.database')]
        ));

        $instances = RdmInstance::query()
            ->orderBy('subdomain')
            ->get(['id', 'subdomain', 'fqdn', 'db_name', 'status', 'madrasah_name']);

        return [
            'total' => $databases->count(),
            'databases' => $databases,
            'instances' => $instances,
        ];
    }

    public function backupOverview(): Collection
    {
        return RdmInstance::query()
            ->where('status', 'active')
            ->orderByDesc('deployed_at')
            ->get(['madrasah_name', 'fqdn', 'db_name', 'deployed_at', 'deploy_path']);
    }

    public function billingOverview(): array
    {
        $instances = RdmInstance::query()->get();
        $packages = config('deploy.packages', []);

        $active = $instances->where('status', 'active');
        $deploying = $instances->where('status', 'deploying');
        $failed = $instances->where('status', 'failed');

        $payments = Payment::query()
            ->with('rdmInstance')
            ->where('external_id', 'not like', 'probe-%')
            ->latest()
            ->get();

        $paidPayments = $payments->where('status', 'paid');
        $pendingPayments = $payments->where('status', 'pending');

        $paidThisMonth = $paidPayments->filter(
            fn (Payment $p) => $p->paid_at?->isCurrentMonth()
        );

        $expiringSoon = $active->filter(function (RdmInstance $instance) {
            if (! $instance->expires_at) {
                return false;
            }

            return $instance->expires_at->isFuture()
                && now()->diffInDays($instance->expires_at) <= 7;
        })->count();

        $gatewayChannels = app(\App\Services\PaymentService::class)->gatewayChannelSummary();

        return [
            'pending' => $deploying->count() + $failed->count() + $expiringSoon,
            'paidThisMonth' => $paidThisMonth->count(),
            'paidThisMonthAmount' => $paidThisMonth->sum('amount'),
            'pendingPayments' => $pendingPayments->count(),
            'totalPaidAmount' => $paidPayments->sum('amount'),
            'totalPayments' => $payments->count(),
            'monthlyRevenue' => $active->sum(fn (RdmInstance $i) => $packages[$i->package]['price'] ?? 0),
            'byPackage' => $active->groupBy('package')->map->count(),
            'instances' => $instances->sortByDesc('deployed_at'),
            'payments' => $payments,
            'gatewayChannels' => $gatewayChannels,
        ];
    }

    public function supportOverview(): array
    {
        $failed = RdmInstance::query()->where('status', 'failed')->latest()->get();
        $deploying = RdmInstance::query()->where('status', 'deploying')->latest()->get();
        $resolved = RdmInstance::query()->where('status', 'active')->count();

        return [
            'incoming' => $failed->count(),
            'processing' => $deploying->count(),
            'resolved' => $resolved,
            'failedInstances' => $failed,
            'deployingInstances' => $deploying,
        ];
    }

    public function settingsOverview(): array
    {
        return [
            [
                'label' => 'Pengaturan Deploy',
                'configured' => is_file(config('deploy.source_zip')) && is_file(config('deploy.deploy_script')),
                'detail' => 'RDMhosting.zip & deploy script',
            ],
            [
                'label' => 'Hostinger DNS API',
                'configured' => $this->dns->isConfigured(),
                'detail' => 'Token API Hostinger',
            ],
            [
                'label' => 'Email SMTP',
                'configured' => filled(config('mail.mailers.smtp.host')) && filled(config('mail.from.address')),
                'detail' => config('mail.from.address', '—'),
            ],
            [
                'label' => 'Queue Worker',
                'configured' => config('queue.default') === 'database',
                'detail' => 'Koneksi: '.config('queue.default'),
            ],
            [
                'label' => 'SSL Certbot',
                'configured' => filled(config('deploy.certbot_email')),
                'detail' => config('deploy.certbot_email', '—'),
            ],
            [
                'label' => 'PHP RDM (ionCube)',
                'configured' => is_file(config('deploy.php_fpm_socket', '')) || file_exists('/run/php/php7.4-fpm.sock'),
                'detail' => 'PHP 7.4 FPM',
            ],
        ];
    }

    public function activityLogs(int $limit = 50): Collection
    {
        return DeployLog::query()
            ->with('rdmInstance')
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function serverSpecs(): array
    {
        $vcpu = (int) trim(shell_exec('nproc 2>/dev/null') ?: '4');
        $ramGb = 4;

        $freeOutput = shell_exec('free -m 2>/dev/null');
        if ($freeOutput && preg_match('/Mem:\s+(\d+)/', $freeOutput, $matches)) {
            $ramGb = max(1, (int) round(((int) $matches[1]) / 1024));
        }

        $diskUsed = '—';
        $diskTotal = '—';
        $dfOutput = shell_exec('df -h / 2>/dev/null | tail -1');
        if ($dfOutput && preg_match('/(\S+)\s+(\S+)\s+(\S+)/', trim($dfOutput), $matches)) {
            $diskTotal = $matches[1];
            $diskUsed = $matches[2];
        }

        $uptime = trim(shell_exec('uptime -p 2>/dev/null') ?: '—');

        return [
            'vcpu' => $vcpu,
            'ram_gb' => $ramGb,
            'disk_used' => $diskUsed,
            'disk_total' => $diskTotal,
            'uptime' => $uptime,
        ];
    }
}
