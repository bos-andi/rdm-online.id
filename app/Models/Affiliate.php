<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Affiliate extends Authenticatable
{
    public const STATUS_LABELS = [
        'pending' => 'Menunggu Persetujuan',
        'active' => 'Aktif',
        'rejected' => 'Ditolak',
        'suspended' => 'Dinonaktifkan',
    ];

    public const BANK_STATUS_LABELS = [
        'unsubmitted' => 'Belum diajukan',
        'pending' => 'Menunggu verifikasi',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak',
    ];

    public const DOCUMENTS = [
        'ktp' => 'Foto KTP',
        'bank_book' => 'Foto buku tabungan',
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'code',
        'status',
        'commission_rate',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'ktp_path',
        'bank_book_path',
        'bank_verification_status',
        'bank_verification_note',
        'bank_submitted_at',
        'bank_verified_at',
        'admin_note',
        'approved_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'email_code_expires_at' => 'datetime',
            'email_code_sent_at' => 'datetime',
            'commission_rate' => 'decimal:2',
            'approved_at' => 'datetime',
            'bank_submitted_at' => 'datetime',
            'bank_verified_at' => 'datetime',
        ];
    }

    public function referredUsers(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by_affiliate_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(AffiliatePayout::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusVariant(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'pending' => 'warning',
            'rejected', 'suspended' => 'danger',
            default => 'default',
        };
    }

    public function referralUrl(): string
    {
        return url('/').'/?ref='.urlencode($this->code);
    }

    public function hasBankAccount(): bool
    {
        return filled($this->bank_name) && filled($this->bank_account_number) && filled($this->bank_account_name);
    }

    public function isBankVerified(): bool
    {
        return $this->bank_verification_status === 'verified' && $this->hasBankAccount();
    }

    public function bankStatusLabel(): string
    {
        return self::BANK_STATUS_LABELS[$this->bank_verification_status] ?? $this->bank_verification_status;
    }

    public function bankStatusVariant(): string
    {
        return match ($this->bank_verification_status) {
            'verified' => 'success',
            'pending' => 'warning',
            'rejected' => 'danger',
            default => 'default',
        };
    }

    public function documentPath(string $type): ?string
    {
        return match ($type) {
            'ktp' => $this->ktp_path,
            'bank_book' => $this->bank_book_path,
            default => null,
        };
    }

    public function documentIsPdf(string $type): bool
    {
        return str_ends_with(strtolower((string) $this->documentPath($type)), '.pdf');
    }
}
