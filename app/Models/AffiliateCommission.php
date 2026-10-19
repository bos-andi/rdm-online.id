<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    public const STATUS_LABELS = [
        'available' => 'Siap Dicairkan',
        'requested' => 'Dalam Pengajuan',
        'paid' => 'Sudah Dibayar',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = [
        'affiliate_id',
        'payment_id',
        'user_id',
        'rdm_instance_id',
        'affiliate_payout_id',
        'purpose',
        'description',
        'base_amount',
        'rate',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'base_amount' => 'integer',
            'rate' => 'decimal:2',
            'amount' => 'integer',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rdmInstance(): BelongsTo
    {
        return $this->belongsTo(RdmInstance::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(AffiliatePayout::class, 'affiliate_payout_id');
    }

    public function purposeLabel(): string
    {
        return $this->purpose === 'deploy' ? 'Pendaftaran baru' : 'Perpanjangan';
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'available' => 'default',
            'requested' => 'warning',
            'cancelled' => 'danger',
            default => 'default',
        };
    }
}
