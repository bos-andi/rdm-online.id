<?php

namespace App\Services;

use App\Models\VpsServer;

class VpsSpecService
{
    public function __construct(
        private RemoteDeployExecutor $executor,
    ) {}

    public function detectAndStore(VpsServer $server): bool
    {
        $specs = $this->detect($server);

        if (! $specs) {
            return false;
        }

        $server->update([
            'vcpu' => $specs['vcpu'],
            'ram_gb' => $specs['ram_gb'],
        ]);

        return true;
    }

    /**
     * @return array{vcpu: int, ram_gb: int, disk_used?: string, disk_total?: string, uptime?: string}|null
     */
    public function detect(VpsServer $server): ?array
    {
        if ($server->isLocal()) {
            return $this->detectLocal();
        }

        if (! $server->isRemoteReady()) {
            return null;
        }

        try {
            $output = $this->executor->runSsh($server, 'nproc 2>/dev/null; free -m 2>/dev/null | awk \'/Mem:/{print $2}\'; df -h / 2>/dev/null | tail -1; uptime -p 2>/dev/null');

            return $this->parseOutput($output);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{vcpu: int, ram_gb: int, disk_used: string, disk_total: string, uptime: string}
     */
    public function detectLocal(): array
    {
        $vcpu = (int) trim(shell_exec('nproc 2>/dev/null') ?: '0');
        $ramGb = 0;

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

        return [
            'vcpu' => max(1, $vcpu),
            'ram_gb' => max(1, $ramGb ?: 1),
            'disk_used' => $diskUsed,
            'disk_total' => $diskTotal,
            'uptime' => trim(shell_exec('uptime -p 2>/dev/null') ?: '—'),
        ];
    }

    /**
     * @return array{vcpu: int, ram_gb: int, disk_used: string, disk_total: string, uptime: string}|null
     */
    private function parseOutput(string $output): ?array
    {
        $lines = array_values(array_filter(array_map('trim', explode("\n", trim($output)))));

        if (count($lines) < 2) {
            return null;
        }

        $vcpu = max(1, (int) $lines[0]);
        $ramMb = (int) ($lines[1] ?? 0);
        $ramGb = max(1, (int) round($ramMb / 1024));

        $diskUsed = '—';
        $diskTotal = '—';
        if (isset($lines[2]) && preg_match('/(\S+)\s+(\S+)\s+(\S+)/', $lines[2], $matches)) {
            $diskTotal = $matches[1];
            $diskUsed = $matches[2];
        }

        $uptime = $lines[3] ?? '—';

        return [
            'vcpu' => $vcpu,
            'ram_gb' => $ramGb,
            'disk_used' => $diskUsed,
            'disk_total' => $diskTotal,
            'uptime' => $uptime,
        ];
    }
}
