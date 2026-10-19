@extends('layouts.affiliate')

@section('title', 'Dashboard')

@php($rupiah = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.'))
@php($rateLabel = app(\App\Services\AffiliateService::class)->formatRate($rate))

@section('content')
    <x-user.page-header title="Halo, {{ $affiliate->name }}" subtitle="Bagikan link Anda dan dapatkan komisi {{ $rateLabel }} dari setiap pembayaran madrasah yang Anda ajak." />

    <div class="app-card p-6 mb-6 bg-gradient-to-br from-emerald-600 to-green-600 text-white border-0">
        <p class="text-sm text-emerald-100">Link referral Anda</p>
        <div class="mt-2 flex flex-col sm:flex-row gap-3">
            <input type="text" readonly value="{{ $affiliate->referralUrl() }}" class="flex-1 min-w-0 rounded-xl bg-white/15 border border-white/20 px-4 py-3 text-white font-medium focus:outline-none" onfocus="this.select()">
            <button type="button" data-copy="{{ $affiliate->referralUrl() }}" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-white text-emerald-700 font-semibold hover:bg-emerald-50 transition-colors">
                <i data-lucide="copy" class="w-4 h-4"></i>
                <span data-copy-label>Salin Link</span>
            </button>
        </div>
        <div class="mt-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-emerald-50">
            <span>Kode referral: <strong class="text-white tracking-wide">{{ $affiliate->code }}</strong>
                <button type="button" data-copy="{{ $affiliate->code }}" class="ml-1 underline underline-offset-2 hover:text-white"><span data-copy-label>salin</span></button>
            </span>
            <span>Komisi: <strong class="text-white">{{ $rateLabel }}</strong> setiap pembayaran, termasuk perpanjangan</span>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-user.stat-card label="Klik link" :value="number_format($affiliate->clicks, 0, ',', '.')" />
        <x-user.stat-card label="Madrasah mendaftar" :value="$referralCount" />
        <x-user.stat-card label="Sudah membayar" :value="$payingCount" />
        <x-user.stat-card label="Total komisi" :value="$rupiah($balance['total'])" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="app-card p-6">
            <h3 class="font-semibold text-gray-900">Saldo Komisi</h3>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-muted">Siap dicairkan</dt><dd class="font-bold text-emerald-700 text-base">{{ $rupiah($balance['available']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Dalam pengajuan</dt><dd class="font-medium">{{ $rupiah($balance['requested']) }}</dd></div>
                <div class="flex justify-between"><dt class="text-muted">Sudah dibayar</dt><dd class="font-medium">{{ $rupiah($balance['paid']) }}</dd></div>
            </dl>
            <a href="{{ route('affiliate.payouts') }}" class="app-btn mt-5 block text-center py-2.5 rounded-xl font-semibold">Cairkan Komisi</a>
            <p class="text-xs text-muted mt-2 text-center">Minimal pencairan {{ $rupiah($settings['min_payout']) }}</p>
        </div>

        <div class="app-card lg:col-span-2 overflow-hidden">
            <div class="app-card-header px-5 py-3 flex items-center justify-between">
                <span class="font-semibold text-gray-800">Komisi Terbaru</span>
                <a href="{{ route('affiliate.commissions') }}" class="text-sm text-emerald-700 hover:underline">Lihat semua</a>
            </div>
            @forelse ($recentCommissions as $commission)
                <div class="px-5 py-3 border-t border-emerald-50 flex items-center justify-between gap-4 text-sm">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-800 truncate">{{ $commission->description }}</p>
                        <p class="text-xs text-muted">{{ $commission->purposeLabel() }} · {{ $commission->created_at->translatedFormat('d M Y') }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-semibold text-emerald-700">+{{ $rupiah($commission->amount) }}</p>
                        <x-user.badge :status="$commission->statusLabel()" :variant="$commission->statusVariant()" />
                    </div>
                </div>
            @empty
                <div class="px-5 py-10 text-center text-muted text-sm border-t border-emerald-50">
                    Belum ada komisi. Bagikan link referral Anda ke grup operator, KKM, atau kenalan madrasah untuk mulai mendapatkan komisi.
                </div>
            @endforelse
        </div>
    </div>

    <div class="app-card p-6 mt-6">
        <h3 class="font-semibold text-gray-900">Cara kerja</h3>
        <ol class="mt-4 grid gap-4 sm:grid-cols-3 text-sm text-gray-600">
            <li class="flex gap-3"><span class="flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 font-bold shrink-0">1</span>Bagikan link referral, atau minta madrasah mengisi kode <strong>{{ $affiliate->code }}</strong> saat mendaftar.</li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 font-bold shrink-0">2</span>Madrasah membuat akun & membayar paket — komisi tercatat otomatis.</li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 font-bold shrink-0">3</span>Setiap perpanjangan menambah komisi lagi. Cairkan kapan saja setelah mencapai batas minimal.</li>
        </ol>
        <p class="text-xs text-muted mt-4">Link berlaku {{ $settings['cookie_days'] }} hari sejak diklik di perangkat yang sama.</p>
    </div>
@endsection
