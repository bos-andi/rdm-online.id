<?php

namespace App\Services;

use App\Mail\AffiliateMail;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePayout;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AffiliateService
{
    public const COOKIE = 'rdm_ref';

    public const CODE_PREFIX = 'AKD';

    public const EMAIL_CODE_MINUTES = 15;

    public const EMAIL_CODE_COOLDOWN_SECONDS = 60;

    public const EMAIL_CODE_MAX_ATTEMPTS = 5;

    private const DEFAULTS = [
        'default_rate' => 20,
        'min_payout' => 50000,
        'cookie_days' => 30,
    ];

    /** @return array{default_rate: float, min_payout: int, cookie_days: int} */
    public function settings(): array
    {
        return [
            'default_rate' => (float) $this->setting('default_rate'),
            'min_payout' => (int) $this->setting('min_payout'),
            'cookie_days' => (int) $this->setting('cookie_days'),
        ];
    }

    public function updateSettings(float $defaultRate, int $minPayout, int $cookieDays): void
    {
        Setting::setValue('affiliate.default_rate', $defaultRate);
        Setting::setValue('affiliate.min_payout', $minPayout);
        Setting::setValue('affiliate.cookie_days', $cookieDays);
    }

    public function rateFor(Affiliate $affiliate): float
    {
        return $affiliate->commission_rate !== null
            ? (float) $affiliate->commission_rate
            : $this->settings()['default_rate'];
    }

    public function findActiveByCode(?string $code): ?Affiliate
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return null;
        }

        return Affiliate::query()->where('code', $code)->where('status', 'active')->first();
    }

    public function generateCode(): string
    {
        $taken = Affiliate::query()->where('code', 'like', self::CODE_PREFIX.'%')->pluck('code')->flip();
        $free = array_values(array_filter(
            range(100, 999),
            fn (int $number) => ! isset($taken[self::CODE_PREFIX.$number]),
        ));

        if ($free !== []) {
            return self::CODE_PREFIX.$free[array_rand($free)];
        }

        do {
            $code = self::CODE_PREFIX.random_int(1000, 9999);
        } while (isset($taken[$code]));

        return $code;
    }

    public function emailCodeCooldown(Affiliate $affiliate): int
    {
        if (! $affiliate->email_code_sent_at) {
            return 0;
        }

        $elapsed = (int) $affiliate->email_code_sent_at->diffInSeconds(now(), true);

        return max(0, self::EMAIL_CODE_COOLDOWN_SECONDS - $elapsed);
    }

    public function hasUsableEmailCode(Affiliate $affiliate): bool
    {
        return $affiliate->email_code_hash !== null
            && $affiliate->email_code_expires_at?->isFuture()
            && $affiliate->email_code_attempts < self::EMAIL_CODE_MAX_ATTEMPTS;
    }

    public function sendEmailCode(Affiliate $affiliate): void
    {
        if ($affiliate->hasVerifiedEmail()) {
            throw new InvalidArgumentException('Email Anda sudah terverifikasi.');
        }

        if ($wait = $this->emailCodeCooldown($affiliate)) {
            throw new InvalidArgumentException("Tunggu {$wait} detik sebelum meminta kode baru.");
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $affiliate->forceFill([
            'email_code_hash' => Hash::make($code),
            'email_code_expires_at' => now()->addMinutes(self::EMAIL_CODE_MINUTES),
            'email_code_sent_at' => now(),
            'email_code_attempts' => 0,
        ])->save();

        try {
            Mail::to($affiliate->email)->send(new AffiliateMail(
                'Kode verifikasi email: '.$code,
                'Verifikasi Email Affiliate',
                [
                    'Halo '.$affiliate->name.',',
                    'Masukkan kode berikut untuk mengaktifkan email akun affiliate RDM Online Anda:',
                    '# '.$code,
                    'Kode berlaku '.self::EMAIL_CODE_MINUTES.' menit. Jangan berikan kode ini kepada siapa pun.',
                    'Jika Anda tidak merasa mendaftar, abaikan email ini.',
                ],
                route('affiliate.verification'),
                'Buka Halaman Verifikasi',
            ));
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim kode verifikasi affiliate ke '.$affiliate->email.': '.$e->getMessage());
            $affiliate->forceFill(['email_code_sent_at' => null])->save();

            throw new InvalidArgumentException('Email verifikasi gagal dikirim. Pastikan alamat email benar, lalu coba lagi.');
        }
    }

    public function verifyEmailCode(Affiliate $affiliate, string $code): void
    {
        if ($affiliate->hasVerifiedEmail()) {
            return;
        }

        if (! $this->hasUsableEmailCode($affiliate)) {
            throw new InvalidArgumentException('Kode sudah kedaluwarsa atau terlalu banyak percobaan. Kirim ulang kode baru.');
        }

        if (! Hash::check($code, $affiliate->email_code_hash)) {
            $affiliate->increment('email_code_attempts');
            $left = self::EMAIL_CODE_MAX_ATTEMPTS - $affiliate->email_code_attempts;

            throw new InvalidArgumentException($left > 0
                ? "Kode verifikasi salah. Sisa percobaan: {$left}."
                : 'Kode verifikasi salah 5 kali. Kirim ulang kode baru.');
        }

        $affiliate->forceFill([
            'email_verified_at' => now(),
            'email_code_hash' => null,
            'email_code_expires_at' => null,
            'email_code_attempts' => 0,
        ])->save();

        $this->notify(
            config('company.email'),
            'Pendaftar affiliate baru — '.$affiliate->name,
            'Pendaftar Affiliate Baru',
            [
                "**{$affiliate->name}** mendaftar sebagai affiliate (email terverifikasi) dan menunggu persetujuan.",
                "Email: {$affiliate->email} · WhatsApp: {$affiliate->phone}",
            ],
            route('admin.affiliate.show', $affiliate),
            'Tinjau di Panel Admin',
        );
    }

    public function changeUnverifiedEmail(Affiliate $affiliate, string $email): void
    {
        if ($affiliate->hasVerifiedEmail()) {
            throw new InvalidArgumentException('Email yang sudah terverifikasi tidak bisa diubah dari sini.');
        }

        $affiliate->forceFill([
            'email' => strtolower($email),
            'email_code_hash' => null,
            'email_code_expires_at' => null,
            'email_code_sent_at' => null,
            'email_code_attempts' => 0,
        ])->save();

        $this->sendEmailCode($affiliate);
    }

    /**
     * Returns the affiliate whose ?ref= link was just opened, so the caller can
     * persist it in a cookie. Clicks are counted once per browser per code.
     */
    public function captureReferral(Request $request): ?Affiliate
    {
        $ref = $request->query('ref');
        $affiliate = is_string($ref) ? $this->findActiveByCode($ref) : null;

        if (! $affiliate) {
            return null;
        }

        if (strtoupper((string) $request->cookie(self::COOKIE)) !== $affiliate->code) {
            $affiliate->increment('clicks');
        }

        return $affiliate;
    }

    public function resolveForSignup(?string $inputCode, Request $request): ?Affiliate
    {
        if (filled($inputCode)) {
            return $this->findActiveByCode($inputCode);
        }

        return $this->findActiveByCode($request->cookie(self::COOKIE));
    }

    public function attachToUser(User $user, ?Affiliate $affiliate): void
    {
        if (! $affiliate || $user->referred_by_affiliate_id) {
            return;
        }

        if (strcasecmp($affiliate->email, $user->email) === 0) {
            return;
        }

        $user->forceFill([
            'referred_by_affiliate_id' => $affiliate->id,
            'referred_at' => now(),
        ])->save();
    }

    public function recordCommission(Payment $payment): ?AffiliateCommission
    {
        if ($payment->status !== 'paid') {
            return null;
        }

        $payment->loadMissing('rdmInstance.user.referrer');
        $instance = $payment->rdmInstance;
        $affiliate = $instance?->user?->referrer;

        if (! $affiliate || ! $affiliate->isActive()) {
            return null;
        }

        $meta = $payment->meta ?? [];
        $baseAmount = (int) ($meta['package_amount'] ?? max(0, $payment->amount - (int) ($meta['admin_fee'] ?? 0)));

        if ($baseAmount <= 0) {
            return null;
        }

        $rate = $this->rateFor($affiliate);
        $amount = (int) floor($baseAmount * $rate / 100);

        if ($amount <= 0) {
            return null;
        }

        $purpose = ($meta['purpose'] ?? 'renewal') === 'deploy' ? 'deploy' : 'renewal';
        $packageLabel = config('deploy.packages.'.$payment->package.'.label', $payment->package);

        return AffiliateCommission::query()->firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'affiliate_id' => $affiliate->id,
                'user_id' => $instance->user_id,
                'rdm_instance_id' => $instance->id,
                'purpose' => $purpose,
                'description' => Str::limit(($instance->madrasah_name ?: $instance->fqdn).' — Paket '.$packageLabel, 190),
                'base_amount' => $baseAmount,
                'rate' => $rate,
                'amount' => $amount,
                'status' => 'available',
            ],
        );
    }

    /** @return array{available: int, requested: int, paid: int, total: int} */
    public function balance(Affiliate $affiliate): array
    {
        $sums = $affiliate->commissions()
            ->selectRaw('status, SUM(amount) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $available = (int) ($sums['available'] ?? 0);
        $requested = (int) ($sums['requested'] ?? 0);
        $paid = (int) ($sums['paid'] ?? 0);

        return [
            'available' => $available,
            'requested' => $requested,
            'paid' => $paid,
            'total' => $available + $requested + $paid,
        ];
    }

    public function requestPayout(Affiliate $affiliate): AffiliatePayout
    {
        if (! $affiliate->isActive()) {
            throw new InvalidArgumentException('Akun affiliate belum aktif.');
        }

        if (! $affiliate->hasBankAccount()) {
            throw new InvalidArgumentException('Lengkapi data rekening di menu Profil terlebih dahulu.');
        }

        if (! $affiliate->isBankVerified()) {
            throw new InvalidArgumentException('Rekening pencairan belum terverifikasi. Upload KTP dan buku tabungan di menu Profil & Rekening, lalu tunggu verifikasi admin.');
        }

        $payout = DB::transaction(function () use ($affiliate) {
            if ($affiliate->payouts()->where('status', 'requested')->lockForUpdate()->exists()) {
                throw new InvalidArgumentException('Masih ada pengajuan pencairan yang sedang diproses.');
            }

            $commissions = $affiliate->commissions()->where('status', 'available')->lockForUpdate()->get();
            $amount = (int) $commissions->sum('amount');
            $minimum = $this->settings()['min_payout'];

            if ($amount <= 0 || $amount < $minimum) {
                throw new InvalidArgumentException('Saldo minimal pencairan Rp'.number_format($minimum, 0, ',', '.').'.');
            }

            $payout = $affiliate->payouts()->create([
                'amount' => $amount,
                'status' => 'requested',
                'bank_name' => $affiliate->bank_name,
                'bank_account_number' => $affiliate->bank_account_number,
                'bank_account_name' => $affiliate->bank_account_name,
            ]);

            AffiliateCommission::query()
                ->whereIn('id', $commissions->pluck('id'))
                ->update(['status' => 'requested', 'affiliate_payout_id' => $payout->id]);

            return $payout;
        });

        $this->notify(
            config('company.email'),
            'Pengajuan pencairan affiliate — '.$affiliate->name,
            'Pengajuan Pencairan Komisi',
            [
                "**{$affiliate->name}** ({$affiliate->code}) mengajukan pencairan komisi sebesar **".$this->rupiah($payout->amount).'**.',
                "Rekening: {$payout->bank_name} {$payout->bank_account_number} a.n. {$payout->bank_account_name}.",
            ],
            route('admin.affiliate.payouts'),
            'Proses di Panel Admin',
        );

        return $payout;
    }

    public function markPayoutPaid(AffiliatePayout $payout, ?string $reference, ?string $note): void
    {
        if ($payout->status !== 'requested') {
            throw new InvalidArgumentException('Pencairan ini sudah diproses.');
        }

        DB::transaction(function () use ($payout, $reference, $note) {
            $payout->update([
                'status' => 'paid',
                'transfer_reference' => $reference,
                'admin_note' => $note,
                'processed_at' => now(),
            ]);

            $payout->commissions()->update(['status' => 'paid']);
        });

        $this->notify(
            $payout->affiliate->email,
            'Komisi affiliate sudah ditransfer',
            'Pencairan Komisi Berhasil',
            array_filter([
                'Halo '.$payout->affiliate->name.',',
                'Pencairan komisi sebesar **'.$this->rupiah($payout->amount).'** sudah kami transfer ke rekening '
                    ."{$payout->bank_name} {$payout->bank_account_number} a.n. {$payout->bank_account_name}.",
                $reference ? "Referensi transfer: {$reference}" : null,
                $note ? "Catatan: {$note}" : null,
                'Terima kasih sudah mengenalkan RDM Online ke lebih banyak madrasah.',
            ]),
            route('affiliate.payouts'),
            'Lihat Riwayat Pencairan',
        );
    }

    public function rejectPayout(AffiliatePayout $payout, ?string $note): void
    {
        if ($payout->status !== 'requested') {
            throw new InvalidArgumentException('Pencairan ini sudah diproses.');
        }

        DB::transaction(function () use ($payout, $note) {
            $payout->update([
                'status' => 'rejected',
                'admin_note' => $note,
                'processed_at' => now(),
            ]);

            $payout->commissions()->update(['status' => 'available', 'affiliate_payout_id' => null]);
        });

        $this->notify(
            $payout->affiliate->email,
            'Pengajuan pencairan komisi ditolak',
            'Pengajuan Pencairan Ditolak',
            array_filter([
                'Halo '.$payout->affiliate->name.',',
                'Pengajuan pencairan sebesar **'.$this->rupiah($payout->amount).'** belum dapat kami proses. Saldo komisi dikembalikan dan bisa diajukan ulang.',
                $note ? "Alasan: {$note}" : null,
            ]),
            route('affiliate.payouts'),
            'Buka Panel Affiliate',
        );
    }

    /**
     * @param  array{bank_name: string, bank_account_number: string, bank_account_name: string}  $bank
     * @param  array<string, UploadedFile|null>  $files
     */
    public function submitBankAccount(Affiliate $affiliate, array $bank, array $files): void
    {
        $columns = ['ktp' => 'ktp_path', 'bank_book' => 'bank_book_path'];
        $updates = $bank;
        $oldFiles = [];

        foreach ($columns as $type => $column) {
            $file = $files[$type] ?? null;

            if (! $file) {
                continue;
            }

            $extension = strtolower($file->extension() ?: $file->getClientOriginalExtension() ?: 'jpg');
            $updates[$column] = $file->storeAs(
                'affiliate-docs/'.$affiliate->id,
                $type.'-'.Str::random(20).'.'.$extension,
                'local',
            );

            if ($affiliate->{$column}) {
                $oldFiles[] = $affiliate->{$column};
            }
        }

        $affiliate->update($updates + [
            'bank_verification_status' => 'pending',
            'bank_verification_note' => null,
            'bank_submitted_at' => now(),
            'bank_verified_at' => null,
        ]);

        if ($oldFiles !== []) {
            Storage::disk('local')->delete($oldFiles);
        }

        $this->notify(
            config('company.email'),
            'Verifikasi rekening affiliate: '.$affiliate->name,
            'Rekening Affiliate Perlu Diverifikasi',
            [
                "**{$affiliate->name}** ({$affiliate->code}) mengajukan verifikasi rekening pencairan.",
                "Rekening: {$affiliate->bank_name} {$affiliate->bank_account_number} a.n. {$affiliate->bank_account_name}",
                'Periksa foto KTP dan buku tabungan, pastikan nama sesuai, lalu setujui atau tolak.',
            ],
            route('admin.affiliate.show', $affiliate),
            'Periksa Dokumen',
        );
    }

    public function verifyBankAccount(Affiliate $affiliate): void
    {
        if (! $affiliate->hasBankAccount() || ! $affiliate->ktp_path || ! $affiliate->bank_book_path) {
            throw new InvalidArgumentException('Data rekening atau dokumen belum lengkap.');
        }

        $affiliate->update([
            'bank_verification_status' => 'verified',
            'bank_verification_note' => null,
            'bank_verified_at' => now(),
        ]);

        $this->notify(
            $affiliate->email,
            'Rekening pencairan Anda terverifikasi',
            'Rekening Terverifikasi',
            [
                'Halo '.$affiliate->name.',',
                "Rekening **{$affiliate->bank_name} {$affiliate->bank_account_number}** a.n. {$affiliate->bank_account_name} sudah terverifikasi.",
                'Anda sekarang bisa mengajukan pencairan komisi dari panel affiliate.',
            ],
            route('affiliate.payouts'),
            'Buka Pencairan',
        );
    }

    public function rejectBankAccount(Affiliate $affiliate, string $note): void
    {
        $affiliate->update([
            'bank_verification_status' => 'rejected',
            'bank_verification_note' => $note,
            'bank_verified_at' => null,
        ]);

        $this->notify(
            $affiliate->email,
            'Verifikasi rekening perlu diperbaiki',
            'Verifikasi Rekening Ditolak',
            [
                'Halo '.$affiliate->name.',',
                'Verifikasi rekening pencairan Anda belum dapat kami setujui.',
                'Alasan: '.$note,
                'Silakan perbaiki data rekening atau upload ulang dokumen di menu Profil & Rekening.',
            ],
            route('affiliate.profile'),
            'Perbaiki Rekening',
        );
    }

    public function documentResponse(Affiliate $affiliate, string $type): \Symfony\Component\HttpFoundation\Response
    {
        $path = $affiliate->documentPath($type);

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function notify(?string $to, string $subject, string $heading, array $lines, ?string $actionUrl = null, ?string $actionLabel = null): void
    {
        if (blank($to)) {
            return;
        }

        try {
            Mail::to($to)->send(new AffiliateMail($subject, $heading, $lines, $actionUrl, $actionLabel));
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim email affiliate ke '.$to.': '.$e->getMessage());
        }
    }

    public function rupiah(int $value): string
    {
        return 'Rp'.number_format($value, 0, ',', '.');
    }

    public function formatRate(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 2, ',', '.'), '0'), ',').'%';
    }

    private function setting(string $key): mixed
    {
        $value = Setting::getValue('affiliate.'.$key);

        return ($value === null || $value === '') ? self::DEFAULTS[$key] : $value;
    }
}
