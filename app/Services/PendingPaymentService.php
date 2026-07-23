<?php

namespace App\Services;

use App\Mail\DeployPaymentReminderMail;
use App\Models\RdmInstance;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PendingPaymentService
{
    public function sendReminderIfDue(RdmInstance $instance): bool
    {
        $instance->refresh();

        if ($instance->status !== 'pending_payment') {
            return false;
        }

        if ($instance->payment_reminder_sent_at) {
            return false;
        }

        if ($instance->payments()->where('status', 'paid')->where('meta->purpose', 'deploy')->exists()) {
            return false;
        }

        $recipient = $instance->pic_email ?: $instance->user?->email;

        if (! $recipient) {
            return false;
        }

        try {
            Mail::to($recipient)->send(new DeployPaymentReminderMail($instance));
            $instance->update(['payment_reminder_sent_at' => now()]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim pengingat pembayaran deploy '.$instance->fqdn.': '.$e->getMessage());

            return false;
        }
    }

    public function cleanupExpired(): int
    {
        $count = 0;

        RdmInstance::query()
            ->where('status', 'pending_payment')
            ->where(function ($query) {
                $query->where('pending_payment_expires_at', '<=', now())
                    ->orWhere(function ($inner) {
                        $inner->whereNull('pending_payment_expires_at')
                            ->where('created_at', '<=', now()->subHours((int) config('deploy.pending_payment_ttl_hours', 24)));
                    });
            })
            ->each(function (RdmInstance $instance) use (&$count) {
                if ($this->deletePendingInstance($instance)) {
                    $count++;
                }
            });

        return $count;
    }

    public function deletePendingInstance(RdmInstance $instance): bool
    {
        if ($instance->status !== 'pending_payment') {
            return false;
        }

        if ($instance->payments()->where('status', 'paid')->exists()) {
            return false;
        }

        $userId = $instance->user_id;
        $instance->payments()->where('status', 'pending')->delete();
        $instance->delete();

        $this->deleteOrphanDeployUser($userId);

        return true;
    }

    private function deleteOrphanDeployUser(int $userId): void
    {
        $user = User::query()->find($userId);

        if (! $user) {
            return;
        }

        if ($user->rdmInstances()->exists()) {
            return;
        }

        if ($user->created_at->greaterThan(now()->subHours((int) config('deploy.pending_payment_ttl_hours', 24) + 1))) {
            $user->delete();
        }
    }
}
