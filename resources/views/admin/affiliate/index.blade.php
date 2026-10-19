@extends('layouts.admin')

@section('title', 'Affiliate')

@php
    $rupiah = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.');
    $service = app(\App\Services\AffiliateService::class);
@endphp

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <x-admin.page-header title="Affiliate" subtitle="Kelola tim affiliate, komisi, dan pencairan." class="mb-0" />
        <div class="flex flex-wrap gap-2">
            @if ($pendingVerifications > 0)
                <a href="{{ route('admin.affiliate', ['verifikasi' => 'pending']) }}" class="px-4 py-2 rounded-lg border border-amber-300 bg-amber-50 text-sm font-semibold text-amber-800 hover:bg-amber-100">
                    Verifikasi rekening <span class="ml-1 px-1.5 py-0.5 rounded-full bg-amber-200 text-xs">{{ $pendingVerifications }}</span>
                </a>
            @endif
            <a href="{{ route('affiliate.program') }}" target="_blank" class="px-4 py-2 rounded-lg border border-slate-300 text-sm text-slate-700 hover:bg-white">Halaman publik</a>
            <a href="{{ route('admin.affiliate.payouts') }}" class="admin-btn px-4 py-2 rounded-lg text-sm font-semibold">
                Pencairan @if ($pendingPayouts > 0)<span class="ml-1 px-1.5 py-0.5 rounded-full bg-white/25 text-xs">{{ $pendingPayouts }}</span>@endif
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">{{ $errors->first() }}</div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-admin.stat-card label="Affiliate aktif" :value="$counts['active'] ?? 0" />
        <x-admin.stat-card label="Menunggu persetujuan" :value="$counts['pending'] ?? 0" value-class="text-amber-600" />
        <x-admin.stat-card label="Komisi belum dibayar" :value="$rupiah($totals['unpaid'])" value-class="text-slate-900" />
        <x-admin.stat-card label="Komisi sudah dibayar" :value="$rupiah($totals['paid'])" value-class="text-slate-900" />
    </div>

    <div class="grid gap-6 lg:grid-cols-4">
        <div class="lg:col-span-3 admin-card overflow-x-auto">
            <div class="admin-card-header px-4 py-3 flex flex-wrap items-center gap-2 text-sm">
                @if ($verification)
                    <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-800">Menunggu verifikasi rekening</span>
                    <a href="{{ route('admin.affiliate') }}" class="text-xs text-slate-500 hover:underline mr-2">hapus filter</a>
                @endif
                @foreach (['' => 'Semua'] + \App\Models\Affiliate::STATUS_LABELS as $key => $label)
                    <a href="{{ route('admin.affiliate', array_filter(['status' => $key])) }}" @class([
                        'px-3 py-1 rounded-full',
                        'bg-slate-900 text-white' => (string) $status === (string) $key,
                        'text-slate-600 hover:bg-slate-100' => (string) $status !== (string) $key,
                    ])>
                        {{ $label }}
                        @if ($key !== '' && ($counts[$key] ?? 0) > 0)<span class="opacity-70">({{ $counts[$key] }})</span>@endif
                    </a>
                @endforeach
            </div>
            <table class="w-full text-sm min-w-[760px]">
                <thead>
                    <tr class="border-t border-slate-100">
                        <th class="p-3 text-left font-semibold text-slate-700">Affiliate</th>
                        <th class="p-3 text-left font-semibold text-slate-700">Kode</th>
                        <th class="p-3 text-left font-semibold text-slate-700">Status</th>
                        <th class="p-3 text-right font-semibold text-slate-700">Klik / Referral</th>
                        <th class="p-3 text-right font-semibold text-slate-700">Komisi</th>
                        <th class="p-3 text-right font-semibold text-slate-700">Belum dibayar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($affiliates as $affiliate)
                        <tr class="border-t border-slate-100 hover:bg-slate-50">
                            <td class="p-3">
                                <a href="{{ route('admin.affiliate.show', $affiliate) }}" class="font-medium text-slate-900 hover:text-emerald-700">{{ $affiliate->name }}</a>
                                <p class="text-xs text-slate-500">{{ $affiliate->email }} · {{ $affiliate->phone }}</p>
                                @unless ($affiliate->hasVerifiedEmail())
                                    <span class="inline-block mt-1 text-xs text-rose-700 font-medium">Email belum diverifikasi</span>
                                @endunless
                                @if ($affiliate->bank_verification_status === 'pending')
                                    <a href="{{ route('admin.affiliate.show', $affiliate) }}#rekening" class="inline-block mt-1 text-xs text-amber-700 font-medium">Rekening menunggu verifikasi</a>
                                @endif
                            </td>
                            <td class="p-3 font-mono text-xs">{{ $affiliate->code }}</td>
                            <td class="p-3"><x-admin.badge :status="$affiliate->statusLabel()" :variant="$affiliate->statusVariant()" /></td>
                            <td class="p-3 text-right">{{ number_format($affiliate->clicks, 0, ',', '.') }} / {{ $affiliate->referred_users_count }}</td>
                            <td class="p-3 text-right">{{ $rupiah((int) $affiliate->earned_total) }}</td>
                            <td class="p-3 text-right font-semibold">{{ $rupiah((int) $affiliate->unpaid_total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-10 text-center text-slate-500">Belum ada affiliate.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $affiliates->links() }}</div>
        </div>

        <form method="POST" action="{{ route('admin.affiliate.settings') }}" class="admin-card p-5 space-y-4 self-start">
            @csrf
            <h2 class="font-semibold text-slate-900">Pengaturan Program</h2>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Komisi default (%)</label>
                <input name="default_rate" type="number" step="0.01" min="0" max="100" value="{{ old('default_rate', $settings['default_rate']) }}" class="admin-input" required>
                <p class="text-xs text-slate-500 mt-1">Dari harga paket, setiap pembayaran. Bisa diatur khusus per affiliate.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Minimal pencairan (Rp)</label>
                <input name="min_payout" type="number" min="0" step="1000" value="{{ old('min_payout', $settings['min_payout']) }}" class="admin-input" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Masa berlaku link (hari)</label>
                <input name="cookie_days" type="number" min="1" max="365" value="{{ old('cookie_days', $settings['cookie_days']) }}" class="admin-input" required>
            </div>
            <button type="submit" class="admin-btn w-full py-2.5 rounded-lg font-semibold">Simpan</button>
        </form>
    </div>
@endsection
