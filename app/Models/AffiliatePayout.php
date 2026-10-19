<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffiliatePayout extends Model
{
    public const STATUS_LABELS = [
        'requested' => 'Diproses',
        'paid' => 'Sudah Ditransfer',
        'rejected' => 'Ditolak',
    ];

    protected $fillable = [
        'affiliate_id',
        'amount',
        'status',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'transfer_reference',
        'admin_note',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'requested' => 'warning',
            'rejected' => 'danger',
            default => 'default',
        };
    }
}
