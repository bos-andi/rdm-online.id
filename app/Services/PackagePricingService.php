<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class PackagePricingService
{
    public function monthlyPrice(): int
    {
        return $this->priceFor('monthly');
    }

    public function yearlyPrice(): int
    {
        return $this->priceFor('yearly');
    }

    public function priceFor(string $package): int
    {
        $fallback = (int) config('deploy.packages.'.$package.'.price', 0);

        return $this->intSetting('packages.'.$package.'.price', $fallback);
    }

    public function originalPriceFor(string $package): ?int
    {
        $fallback = $package === 'yearly' ? 100000 : null;
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
        return $this->originalPriceFor('monthly') !== null
            || $this->originalPriceFor('yearly') !== null;
    }

    /** @return array{monthly: int, yearly: int} */
    public function prices(): array
    {
        return [
            'monthly' => $this->monthlyPrice(),
            'yearly' => $this->yearlyPrice(),
        ];
    }

    /**
     * @return array{
     *     monthly: int,
     *     yearly: int,
     *     monthly_original: ?int,
     *     yearly_original: ?int,
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
            'monthly_original' => $promoActive ? $this->originalPriceFor('monthly') : null,
            'yearly_original' => $promoActive ? $this->originalPriceFor('yearly') : null,
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

    public function update(
        int $monthly,
        int $yearly,
        ?int $monthlyOriginal,
        ?int $yearlyOriginal,
        ?string $promoEndsAt,
    ): void {
        Setting::setValue('packages.monthly.price', $monthly);
        Setting::setValue('packages.yearly.price', $yearly);
        Setting::setValue(
            'packages.monthly.original_price',
            $monthlyOriginal !== null && $monthlyOriginal > 0 ? $monthlyOriginal : null,
        );
        Setting::setValue(
            'packages.yearly.original_price',
            $yearlyOriginal !== null && $yearlyOriginal > 0 ? $yearlyOriginal : null,
        );
        Setting::setValue('packages.promo_ends_at', $promoEndsAt ?: null);
        $this->applyToConfig();
    }

    public function applyToConfig(): void
    {
        if (! $this->tableReady()) {
            return;
        }

        foreach (['monthly', 'yearly'] as $package) {
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
