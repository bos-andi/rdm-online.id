@extends('layouts.affiliate')

@section('title', 'Komisi')

@php($rupiah = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.'))
@php($rateLabel = fn ($r) => app(\App\Services\AffiliateService::class)->formatRate((float) $r))

@section('content')
    <x-user.page-header title="Komisi" subtitle="Setiap pembayaran dari madrasah referral Anda — termasuk perpanjangan — tercatat di sini." />

    <div class="grid gap-4 sm:grid-cols-3 mb-6">
        <x-user.stat-card label="Siap dicairkan" :value="$rupiah($balance['available'])" />
        <x-user.stat-card label="Dalam pengajuan" :value="$rupiah($balance['requested'])" value-class="text-amber-600" />
        <x-user.stat-card label="Sudah dibayar" :value="$rupiah($balance['paid'])" value-class="text-gray-800" />
    </div>

    <div class="app-card overflow-x-auto">
        <table class="w-full text-sm min-w-[720px]">
            <thead>
                <tr class="app-card-header">
                    <th class="p-3 text-left font-semibold text-gray-700">Tanggal</th>
                    <th class="p-3 text-left font-semibold text-gray-700">Keterangan</th>
                    <th class="p-3 text-right font-semibold text-gray-700">Nilai paket</th>
                    <th class="p-3 text-right font-semibold text-gray-700">Komisi</th>
                    <th class="p-3 text-left font-semibold text-gray-700">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($commissions as $commission)
                    <tr class="border-t border-emerald-50">
                        <td class="p-3 text-muted whitespace-nowrap">{{ $commission->created_at->translatedFormat('d M Y') }}</td>
                        <td class="p-3">
                            <p class="font-medium text-gray-800">{{ $commission->description }}</p>
                            <p class="text-xs text-muted">{{ $commission->purposeLabel() }}</p>
                        </td>
                        <td class="p-3 text-right whitespace-nowrap">{{ $rupiah($commission->base_amount) }}</td>
                        <td class="p-3 text-right whitespace-nowrap">
                            <span class="font-semibold text-emerald-700">{{ $rupiah($commission->amount) }}</span>
                            <span class="block text-xs text-muted">{{ $rateLabel($commission->rate) }}</span>
                        </td>
                        <td class="p-3"><x-user.badge :status="$commission->statusLabel()" :variant="$commission->statusVariant()" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-10 text-center text-muted">Belum ada komisi.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $commissions->links() }}</div>
@endsection
