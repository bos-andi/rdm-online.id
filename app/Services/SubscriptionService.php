<?php

namespace App\Services;

use App\Models\RdmInstance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function packagePeriodDays(RdmInstance $instance): int
    {
        return (int) (config('deploy.packages.'.$instance->package.'.days')
            ?? ($instance->package === 'yearly' ? 365 : 30));
    }

    public function expiresAt(RdmInstance $instance): ?Carbon
    {
        if ($instance->expires_at) {
            return $instance->expires_at->copy();
        }

        if (! $instance->deployed_at) {
            return null;
        }

        return $instance->deployed_at->copy()->addDays($this->packagePeriodDays($instance));
    }

    public function isExpired(RdmInstance $instance): bool
    {
        if (in_array($instance->status, ['expired', 'suspended'], true)) {
            return true;
        }

        if ($instance->status !== 'active') {
            return false;
        }

        $expiresAt = $this->expiresAt($instance);

        return $expiresAt !== null && now()->greaterThan($expiresAt);
    }

    public function isAccessible(RdmInstance $instance): bool
    {
        return $instance->status === 'active' && ! $this->isExpired($instance);
    }

    public function daysRemaining(RdmInstance $instance): ?int
    {
        $expiresAt = $this->expiresAt($instance);

        if (! $expiresAt) {
            return null;
        }

        if (now()->greaterThan($expiresAt)) {
            return 0;
        }

        return (int) now()->diffInDays($expiresAt);
    }

    public function userHasActiveSubscription(User $user): bool
    {
        return $this->activeInstancesFor($user)->isNotEmpty();
    }

    public function activeInstancesFor(User $user): Collection
    {
        return $user->rdmInstances()
            ->where('status', 'active')
            ->get()
            ->filter(fn (RdmInstance $instance) => ! $this->isExpired($instance));
    }

    public function syncExpiredInstancesForUser(User $user): void
    {
        $user->rdmInstances()
            ->where('status', 'active')
            ->get()
            ->each(function (RdmInstance $instance) {
                if ($this->isExpired($instance)) {
                    $this->expireInstance($instance);
                }
            });
    }

    public function expireDueInstances(): int
    {
        $count = 0;

        RdmInstance::query()
            ->where('status', 'active')
            ->whereNotNull('deployed_at')
            ->get()
            ->each(function (RdmInstance $instance) use (&$count) {
                if ($this->isExpired($instance)) {
                    $this->expireInstance($instance);
                    $count++;
                }
            });

        return $count;
    }

    public function expireInstance(RdmInstance $instance): void
    {
        if ($instance->status === 'expired') {
            return;
        }

        $graceDays = (int) config('subscription.grace_days', 30);

        $instance->update([
            'status' => 'expired',
            'error_message' => 'Langganan habis. Data akan dihapus permanen setelah '.$graceDays.' hari jika tidak diperpanjang.',
            'expired_at' => now(),
            'purge_at' => now()->addDays($graceDays),
            'purge_warning_sent_at' => null,
        ]);

        $this->suspendNginx($instance);
    }

    public function deactivateInstance(RdmInstance $instance, ?string $reason = null): void
    {
        if ($instance->status !== 'active') {
            throw new \InvalidArgumentException('Hanya RDM berstatus aktif yang bisa dinonaktifkan.');
        }

        $instance->update([
            'status' => 'suspended',
            'error_message' => $reason ?: 'Dinonaktifkan oleh administrator.',
        ]);

        $this->suspendNginx($instance);
    }

    private function suspendNginx(RdmInstance $instance): void
    {
        try {
            app(DeployService::class)->suspendInstance($instance);
        } catch (\Throwable $e) {
            Log::warning('Gagal suspend RDM '.$instance->fqdn.': '.$e->getMessage());
        }
    }

    public function renewInstance(RdmInstance $instance): void
    {
        if (! in_array($instance->status, ['active', 'expired', 'suspended'], true)) {
            throw new \InvalidArgumentException('RDM ini tidak dapat diaktifkan kembali.');
        }

        $days = $this->packagePeriodDays($instance);
        $base = $instance->expires_at && $instance->expires_at->isFuture()
            ? $instance->expires_at
            : now();

        $instance->update([
            'status' => 'active',
            'expires_at' => $base->copy()->addDays($days),
            'error_message' => null,
            'expired_at' => null,
            'purge_at' => null,
            'purge_warning_sent_at' => null,
        ]);

        try {
            app(DeployService::class)->restoreInstance($instance);
        } catch (\Throwable $e) {
            Log::warning('Gagal restore RDM '.$instance->fqdn.': '.$e->getMessage());
            throw $e;
        }
    }

    public function statusLabel(RdmInstance $instance): string
    {
        if ($instance->status === 'active' && $this->isExpired($instance)) {
            return 'Expired';
        }

        return match ($instance->status) {
            'active' => 'Aktif',
            'pending_payment' => 'Menunggu Pembayaran',
            'expired' => 'Expired',
            'suspended' => 'Nonaktif',
            'deploying' => 'Deploying',
            'destroying' => 'Menghapus',
            'failed' => 'Gagal',
            default => ucfirst($instance->status),
        };
    }

    public function sendPurgeWarnings(): int
    {
        $warningDays = (int) config('subscription.purge_warning_days', 7);
        $count = 0;

        RdmInstance::query()
            ->where('status', 'expired')
            ->whereNotNull('purge_at')
            ->whereNull('purge_warning_sent_at')
            ->where('purge_at', '>', now())
            ->where('purge_at', '<=', now()->addDays($warningDays))
            ->each(function (RdmInstance $instance) use (&$count) {
                try {
                    \Illuminate\Support\Facades\Mail::to($instance->pic_email)
                        ->send(new \App\Mail\RdmPurgeWarningMail($instance));

                    $instance->update(['purge_warning_sent_at' => now()]);
                    $count++;
                } catch (\Throwable $e) {
                    Log::warning('Gagal kirim peringatan purge '.$instance->fqdn.': '.$e->getMessage());
                }
            });

        return $count;
    }

    public function purgeExpiredInstances(): int
    {
        $count = 0;
        $destroyService = app(RdmDestroyService::class);

        RdmInstance::query()
            ->where('status', 'expired')
            ->whereNotNull('purge_at')
            ->where('purge_at', '<=', now())
            ->each(function (RdmInstance $instance) use ($destroyService, &$count) {
                try {
                    $destroyService->destroy($instance);
                    $count++;
                } catch (\Throwable $e) {
                    Log::error('Gagal purge RDM '.$instance->fqdn.': '.$e->getMessage());
                }
            });

        return $count;
    }
}
