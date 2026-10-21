@extends('layouts.landing')

@section('title', 'Checkout — RDM-ONLINE.ID')

@php
    $packages = [
        'monthly' => [
            'name' => 'Paket Bulanan',
            'desc' => 'Masa aktif 1 bulan',
            'price' => $pricing['monthly'],
            'original' => $pricing['monthly_original'],
            'period' => '/bulan',
        ],
        'yearly' => [
            'name' => 'Paket Tahunan',
            'desc' => 'Masa aktif 12 bulan',
            'price' => $pricing['yearly'],
            'original' => $pricing['yearly_original'],
            'period' => '/tahun',
        ],
        'biennial' => [
            'name' => 'Paket 2 Tahunan',
            'desc' => 'Masa aktif 24 bulan',
            'price' => $pricing['biennial'],
            'original' => $pricing['biennial_original'],
            'period' => '/2 tahun',
        ],
    ];
    $groupIcons = [
        'QRIS' => 'qr-code',
        'Pembayaran Instan' => 'qr-code',
        'Virtual Account' => 'landmark',
        'Gerai Retail' => 'store',
        'Retail' => 'store',
        'E-Wallet' => 'wallet',
    ];
    $firstMethod = collect($paymentMethods)->flatten(1)->first();
    $rupiah = fn (int $value) => 'Rp'.number_format($value, 0, ',', '.');
@endphp

@section('content')
    <section class="pt-28 pb-20 px-4 sm:px-6 min-h-[70vh] bg-gradient-to-b from-emerald-50 to-white">
        <div class="max-w-5xl mx-auto">
            <div class="text-center max-w-2xl mx-auto">
                <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">Checkout</h1>
                <p class="text-gray-600 mt-3">Pilih paket dan metode pembayaran untuk hosting RDM madrasah Anda.</p>
            </div>

            <form id="checkout-form" action="{{ route('deploy') }}" method="GET" class="mt-10 grid gap-6 lg:grid-cols-[1fr_340px] items-start">
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl border border-emerald-100 shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900">1. Pilih Paket</h2>
                        <p class="text-sm text-gray-500 mt-1">Termasuk hosting, subdomain, SSL, dan backup harian.</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            @foreach ($packages as $code => $package)
                                <label class="relative flex flex-col p-4 rounded-xl border-2 border-gray-200 cursor-pointer hover:border-emerald-300 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50">
                                    <input
                                        type="radio"
                                        name="package"
                                        value="{{ $code }}"
                                        data-price="{{ $package['price'] }}"
                                        data-name="{{ $package['name'] }}"
                                        class="sr-only"
                                        @checked($selectedPackage === $code)
                                    >
                                    <span class="font-semibold text-gray-900">{{ $package['name'] }}</span>
                                    <span class="text-xs text-gray-500">{{ $package['desc'] }}</span>
                                    <span class="mt-3">
                                        @if ($package['original'])
                                            <span class="block text-sm text-gray-400 line-through">{{ $rupiah($package['original']) }}</span>
                                        @endif
                                        <span class="text-2xl font-bold text-emerald-700">{{ $rupiah($package['price']) }}</span>
                                        <span class="text-sm text-gray-400">{{ $package['period'] }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl border border-emerald-100 shadow-sm p-6">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">2. Pilih Metode Pembayaran</h2>
                                <p class="text-sm text-gray-500 mt-1">Biaya admin sesuai metode yang dipilih.</p>
                            </div>
                            @if (config('payment.gateway') === 'tripay')
                                <img src="{{ asset('tripay-logo.png') }}" alt="Tripay" class="h-7 w-auto">
                            @endif
                        </div>

                        <div class="mt-5 space-y-5">
                            @forelse ($paymentMethods as $group => $methods)
                                <div>
                                    <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-800 mb-2">
                                        <i data-lucide="{{ $groupIcons[$group] ?? 'credit-card' }}" class="w-4 h-4"></i>
                                        {{ $group }}
                                    </p>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach ($methods as $method)
                                            @php($fee = $adminFees[$method['code']] ?? 0)
                                            <label class="flex items-center justify-between gap-3 p-3 rounded-lg border-2 border-gray-200 cursor-pointer hover:border-emerald-300 has-[:checked]:border-emerald-600 has-[:checked]:bg-emerald-50">
                                                <span class="flex items-center gap-2 min-w-0">
                                                    <input
                                                        type="radio"
                                                        name="method"
                                                        value="{{ $method['code'] }}"
                                                        data-admin-fee="{{ $fee }}"
                                                        data-fee-percent="{{ $method['fee_percent'] ?? 0 }}"
                                                        data-fee-flat="{{ $method['fee_flat'] ?? $fee }}"
                                                        data-fee-customer="{{ ($method['fee_bearer'] ?? '') === 'customer' ? '1' : '0' }}"
                                                        data-label="{{ $method['label'] }}"
                                                        class="text-emerald-600"
                                                        @checked($firstMethod && $firstMethod['code'] === $method['code'])
                                                    >
                                                    <span class="text-sm font-semibold text-gray-900 truncate">{{ $method['label'] }}</span>
                                                </span>
                                                <span class="text-xs text-gray-500 shrink-0">{{ $fee > 0 ? '+ '.$rupiah($fee) : 'Tanpa biaya admin' }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-rose-600">Metode pembayaran belum tersedia.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <aside class="bg-white rounded-2xl border border-emerald-100 shadow-sm p-6 lg:sticky lg:top-24">
                    <h2 class="text-lg font-semibold text-gray-900">Ringkasan Pesanan</h2>

                    <dl class="mt-4 space-y-2.5 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Produk</dt>
                            <dd class="font-medium text-right">Hosting RDM Madrasah</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Paket</dt>
                            <dd class="font-medium text-right" id="summary-package"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Metode</dt>
                            <dd class="font-medium text-right" id="summary-method">—</dd>
                        </div>
                        <div class="flex justify-between gap-3 pt-3 border-t border-gray-100">
                            <dt class="text-gray-500">Harga paket</dt>
                            <dd class="font-medium" id="summary-price"></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-gray-500">Biaya admin</dt>
                            <dd class="font-medium" id="summary-fee">—</dd>
                        </div>
                        <div class="flex justify-between gap-3 pt-3 border-t border-gray-100 text-base font-bold text-emerald-700">
                            <dt>Total bayar</dt>
                            <dd id="summary-total"></dd>
                        </div>
                    </dl>

                    <button type="submit" class="landing-btn-primary mt-6 w-full py-3 rounded-xl font-semibold">
                        Lanjutkan Pembayaran
                    </button>

                    <p class="mt-3 text-xs text-gray-500 leading-relaxed">
                        Berikutnya Anda mengisi subdomain dan data madrasah, lalu menyelesaikan pembayaran.
                        Dengan melanjutkan, Anda menyetujui
                        <a href="{{ route('pages.terms') }}" class="text-emerald-700 hover:underline">Syarat &amp; Ketentuan</a>
                        dan
                        <a href="{{ route('pages.refund') }}" class="text-emerald-700 hover:underline">Kebijakan Pengembalian Dana</a>.
                    </p>

                    <div class="mt-5 pt-4 border-t border-gray-100 flex items-center gap-2 text-xs text-gray-500">
                        <i data-lucide="shield-check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                        Pembayaran aman diproses oleh {{ config('payment.label') }}.
                    </div>
                </aside>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const form = document.getElementById('checkout-form');
            if (!form) return;

            const rupiah = (value) => 'Rp' + Number(value).toLocaleString('id-ID');
            const text = (id, value) => { document.getElementById(id).textContent = value; };

            const update = () => {
                const pkg = form.querySelector('input[name="package"]:checked');
                const method = form.querySelector('input[name="method"]:checked');
                const price = Number(pkg?.dataset.price || 0);
                const fee = method?.dataset.feeCustomer === '1'
                    ? Math.ceil(price * Number(method.dataset.feePercent || 0) / 100) + Number(method.dataset.feeFlat || 0)
                    : Number(method?.dataset.adminFee || 0);

                text('summary-package', pkg?.dataset.name || '—');
                text('summary-method', method?.dataset.label || '—');
                text('summary-price', rupiah(price));
                text('summary-fee', method ? (fee > 0 ? rupiah(fee) : 'Gratis') : '—');
                text('summary-total', rupiah(price + fee));
            };

            form.addEventListener('change', update);
            update();
        })();
    </script>
@endpush
