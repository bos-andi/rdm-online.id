<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class PackagePricingService
{
    public const PACKAGES = ['monthly', 'yearly', 'biennial'];

    private const ORIGINAL_PRICE_FALLBACKS = [
        'yearly' => 100000,
        'biennial' => 500000,
    ];

    public function monthlyPrice(): int
    {
        return $this->priceFor('monthly');
    }

    public function yearlyPrice(): int
    {
        return $this->priceFor('yearly');
    }

    public function biennialPrice(): int
    {
        return $this->priceFor('biennial');
    }

    public function priceFor(string $package): int
    {
        $fallback = (int) config('deploy.packages.'.$package.'.price', 0);

        return $this->intSetting('packages.'.$package.'.price', $fallback);
    }

    public function originalPriceFor(string $package): ?int
    {
        $fallback = self::ORIGINAL_PRICE_FALLBACKS[$package] ?? null;
        $value = $this->nullableIntSetting('packages.'.$package.'.original_price', $fallback);

        if ($value === null || $value <= $this->priceFor($package)) {
            return null;
        }

        return $value;
    }

    public function promoEndsAt(): ?Carbon
    {
        if (! $this->tableReady()) {
            return null;
        }

        $raw = Setting::getValue('packages.promo_ends_at');

        if (! $raw) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    public function isPromoActive(): bool
    {
        $endsAt = $this->promoEndsAt();

        if ($endsAt === null) {
            return $this->hasAnyDiscount();
        }

        return $endsAt->isFuture() && $this->hasAnyDiscount();
    }

    public function hasAnyDiscount(): bool
    {
        foreach (self::PACKAGES as $package) {
            if ($this->originalPriceFor($package) !== null) {
                return true;
            }
        }

        return false;
    }

    /** @return array{monthly: int, yearly: int, biennial: int} */
    public function prices(): array
    {
        return [
            'monthly' => $this->monthlyPrice(),
            'yearly' => $this->yearlyPrice(),
            'biennial' => $this->biennialPrice(),
        ];
    }

    /**
     * @return array{
     *     monthly: int,
     *     yearly: int,
     *     biennial: int,
     *     monthly_original: ?int,
     *     yearly_original: ?int,
     *     biennial_original: ?int,
     *     promo_ends_at: ?string,
     *     promo_active: bool
     * }
     */
    public function display(): array
    {
        $this->ensureDefaultPromoWindow();

        $promoActive = $this->isPromoActive();

        return [
            'monthly' => $this->monthlyPrice(),
            'yearly' => $this->yearlyPrice(),
            'biennial' => $this->biennialPrice(),
            'monthly_original' => $promoActive ? $this->originalPriceFor('monthly') : null,
            'yearly_original' => $promoActive ? $this->originalPriceFor('yearly') : null,
            'biennial_original' => $promoActive ? $this->originalPriceFor('biennial') : null,
            'promo_ends_at' => $this->promoEndsAt()?->toIso8601String(),
            'promo_active' => $promoActive,
        ];
    }

    private function ensureDefaultPromoWindow(): void
    {
        if (! $this->tableReady() || ! $this->hasAnyDiscount()) {
            return;
        }

        if ($this->promoEndsAt()) {
            return;
        }

        // Default 14 hari agar countdown langsung tampil saat ada harga coretan
        Setting::setValue('packages.promo_ends_at', now()->addDays(14)->format('Y-m-d H:i:s'));
    }

    /**
     * @param  array<string, int>  $prices  keyed by package code
     * @param  array<string, ?int>  $originals  keyed by package code
     */
    public function update(array $prices, array $originals, ?string $promoEndsAt): void
    {
        foreach (self::PACKAGES as $package) {
            if (array_key_exists($package, $prices)) {
                Setting::setValue('packages.'.$package.'.price', max(0, (int) $prices[$package]));
            }

            if (array_key_exists($package, $originals)) {
                $original = $originals[$package];
                Setting::setValue(
                    'packages.'.$package.'.original_price',
                    $original !== null && $original > 0 ? $original : null,
                );
            }
        }

        Setting::setValue('packages.promo_ends_at', $promoEndsAt ?: null);
        $this->applyToConfig();
    }

    public function applyToConfig(): void
    {
        if (! $this->tableReady()) {
            return;
        }

        foreach (self::PACKAGES as $package) {
            $stored = Setting::getValue('packages.'.$package.'.price');

            if ($stored === null || $stored === '') {
                continue;
            }

            config(['deploy.packages.'.$package.'.price' => max(0, (int) $stored)]);
        }
    }

    private function intSetting(string $key, int $fallback): int
    {
        if (! $this->tableReady()) {
            return $fallback;
        }

        $stored = Setting::getValue($key);

        if ($stored === null || $stored === '') {
            return $fallback;
        }

        return max(0, (int) $stored);
    }

    private function nullableIntSetting(string $key, ?int $fallback): ?int
    {
        if (! $this->tableReady()) {
            return $fallback;
        }

        $stored = Setting::getValue($key, '__missing__');

        if ($stored === '__missing__') {
            return $fallback;
        }

        if ($stored === null || $stored === '') {
            return null;
        }

        return max(0, (int) $stored);
    }

    private function tableReady(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (\Throwable) {
            return false;
        }
    }
}
