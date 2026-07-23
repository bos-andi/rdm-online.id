<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'rdm_instance_id',
        'external_id',
        'xendit_invoice_id',
        'amount',
        'package',
        'payment_channel',
        'status',
        'payer_email',
        'invoice_url',
        'paid_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function rdmInstance(): BelongsTo
    {
        return $this->belongsTo(RdmInstance::class);
    }

    public function markPaid(array $payload = []): void
    {
        $paidAt = now();

        if (! empty($payload['paid_at'])) {
            try {
                $paidAt = \Illuminate\Support\Carbon::parse($payload['paid_at']);
            } catch (\Throwable) {
                // keep now()
            }
        }

        $this->update([
            'status' => 'paid',
            'paid_at' => $paidAt,
            'meta' => array_merge($this->meta ?? [], ['webhook' => $payload]),
        ]);
    }

    public function channelLabel(): string
    {
        if (! $this->payment_channel) {
            return '—';
        }

        return config('xendit.methods.'.$this->payment_channel.'.label')
            ?? strtoupper(str_replace('_', ' ', $this->payment_channel));
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'paid' => 'Lunas',
            'pending' => 'Menunggu',
            'expired' => 'Kedaluwarsa',
            'failed' => 'Gagal',
            default => ucfirst($this->status),
        };
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'pending' => 'warning',
            'expired', 'failed' => 'danger',
            default => 'default',
        };
    }
}
