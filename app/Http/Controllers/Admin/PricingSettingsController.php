<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PackagePricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PricingSettingsController extends Controller
{
    public function update(Request $request, PackagePricingService $pricing): RedirectResponse
    {
        $data = $request->validate([
            'monthly_price' => ['required', 'integer', 'min:0'],
            'yearly_price' => ['required', 'integer', 'min:0'],
            'biennial_price' => ['required', 'integer', 'min:0'],
            'monthly_original' => ['nullable', 'integer', 'min:0'],
            'yearly_original' => ['nullable', 'integer', 'min:0'],
            'biennial_original' => ['nullable', 'integer', 'min:0'],
            'promo_ends_at' => ['nullable', 'date'],
        ]);

        $prices = [];
        $originals = [];

        foreach (PackagePricingService::PACKAGES as $package) {
            $prices[$package] = (int) $data[$package.'_price'];
            $originals[$package] = isset($data[$package.'_original']) ? (int) $data[$package.'_original'] : null;
        }

        $pricing->update($prices, $originals, $data['promo_ends_at'] ?? null);

        return back()->with('status', 'Harga paket berhasil disimpan.');
    }
}
