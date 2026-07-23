<?php

namespace App\Services;

use App\Models\RdmInstance;
use App\Models\User;
use Illuminate\Support\Collection;
use Symfony\Component\Process\Process;

class UserDashboardService
{
    public function __construct(
        private SubscriptionService $subscriptions,
    ) {}

    public function instancesFor(User $user): Collection
    {
        return RdmInstance::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function stats(User $user): array
    {
        $instances = $this->instancesFor($user);
        $active = $instances->filter(fn (RdmInstance $instance) => $this->subscriptions->isAccessible($instance));

        return [
            'activeCount' => $active->count(),
            'daysRemaining' => $this->shortestSubscriptionDays($active),
            'storageBytes' => $this->totalStorageBytes($active),
            'lastBackupAt' => $active->max('deployed_at'),
            'serverOnline' => $active->isNotEmpty(),
            'notifications' => $instances->whereIn('status', ['failed', 'deploying', 'expired', 'suspended'])->count(),
        ];
    }

    private function shortestSubscriptionDays(Collection $instances): ?int
    {
        $remaining = $instances
            ->map(fn (RdmInstance $instance) => $this->subscriptions->daysRemaining($instance))
            ->filter(fn (?int $days) => $days !== null);

        return $remaining->isEmpty() ? null : $remaining->min();
    }

    private function totalStorageBytes(Collection $instances): int
    {
        $total = 0;

        foreach ($instances as $instance) {
            if (! is_dir($instance->deploy_path)) {
                continue;
            }

            $process = Process::fromShellCommandline(
                'du -sb '.escapeshellarg($instance->deploy_path)
            );
            $process->run();

            if ($process->isSuccessful()) {
                $total += (int) strtok(trim($process->getOutput()), "\t");
            }
        }

        return $total;
    }

    public function formatStorage(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }
}
