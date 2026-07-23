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
            'monthly_original' => ['nullable', 'integer', 'min:0'],
            'yearly_original' => ['nullable', 'integer', 'min:0'],
            'promo_ends_at' => ['nullable', 'date'],
        ]);

        $pricing->update(
            (int) $data['monthly_price'],
            (int) $data['yearly_price'],
            isset($data['monthly_original']) ? (int) $data['monthly_original'] : null,
            isset($data['yearly_original']) ? (int) $data['yearly_original'] : null,
            $data['promo_ends_at'] ?? null,
        );

        return back()->with('status', 'Harga paket berhasil disimpan.');
    }
}
