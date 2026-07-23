<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RdmInstance extends Model
{
    protected $fillable = [
        'user_id',
        'vps_server_id',
        'subdomain',
        'fqdn',
        'madrasah_name',
        'jenjang',
        'pic_email',
        'package',
        'deploy_path',
        'db_name',
        'db_user',
        'db_password',
        'status',
        'progress',
        'current_step',
        'error_message',
        'deployed_at',
        'expires_at',
        'expired_at',
        'purge_at',
        'purge_warning_sent_at',
        'pending_payment_expires_at',
        'payment_reminder_sent_at',
        'deploy_welcome_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'db_password' => 'encrypted',
            'deployed_at' => 'datetime',
            'expires_at' => 'datetime',
            'expired_at' => 'datetime',
            'purge_at' => 'datetime',
            'purge_warning_sent_at' => 'datetime',
            'pending_payment_expires_at' => 'datetime',
            'payment_reminder_sent_at' => 'datetime',
            'deploy_welcome_sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vpsServer(): BelongsTo
    {
        return $this->belongsTo(VpsServer::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DeployLog::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function logStep(string $step, string $message, int $progress, string $level = 'info'): void
    {
        $this->logs()->create([
            'step' => $step,
            'message' => $message,
            'progress' => $progress,
            'level' => $level,
        ]);

        $this->update([
            'current_step' => $step,
            'progress' => $progress,
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $message,
        ]);

        $this->logStep($this->current_step ?? 'failed', $message, $this->progress, 'error');
    }

    public function markActive(): void
    {
        $days = (int) (config('deploy.packages.'.$this->package.'.days')
            ?? ($this->package === 'yearly' ? 365 : 30));

        $this->update([
            'status' => 'active',
            'progress' => 100,
            'current_step' => 'completed',
            'deployed_at' => now(),
            'expires_at' => now()->addDays($days),
            'error_message' => null,
            'expired_at' => null,
            'purge_at' => null,
            'purge_warning_sent_at' => null,
        ]);
    }

    public function paidDeployPayment(): ?Payment
    {
        return $this->payments()
            ->where('status', 'paid')
            ->where('meta->purpose', 'deploy')
            ->latest()
            ->first();
    }

    public function canRetryDeploy(): bool
    {
        return $this->status === 'failed' && $this->paidDeployPayment() !== null;
    }

    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    public function canResumePayment(): bool
    {
        if (! $this->isPendingPayment()) {
            return false;
        }

        if ($this->pending_payment_expires_at && $this->pending_payment_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function canDestroy(): bool
    {
        return ! in_array($this->status, ['deploying', 'destroying'], true);
    }

    public function daysUntilPurge(): ?int
    {
        if (! $this->purge_at || $this->purge_at->isPast()) {
            return $this->purge_at ? 0 : null;
        }

        return (int) now()->diffInDays($this->purge_at);
    }

    public function purgeWarningDue(): bool
    {
        if ($this->status !== 'expired' || ! $this->purge_at) {
            return false;
        }

        $days = $this->daysUntilPurge();

        return $days !== null
            && $days <= (int) config('subscription.purge_warning_days', 7)
            && $days > 0;
    }
}
