<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Perpanjang Langganan — {{ $instance->madrasah_name }}</title>
    @include('layouts.partials.favicon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-50 via-green-50 to-white font-sans p-4">

    <div class="w-full max-w-lg">
        <div class="text-center mb-8">
            <x-brand.logo height="h-12" class="mx-auto" />
            <p class="text-gray-500 text-sm mt-3">Perpanjang Langganan RDM Online</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-emerald-100/80 border border-emerald-100 p-8">
            <div class="mb-6">
                <p class="text-xs uppercase tracking-wide text-emerald-700 font-semibold">Tanpa login</p>
                <h1 class="text-xl font-bold text-gray-900 mt-1">{{ $instance->madrasah_name }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $instance->fqdn }}</p>
            </div>

            <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-4 mb-6 space-y-2 text-sm">
                <div class="flex justify-between gap-4">
                    <span class="text-gray-600">Status</span>
                    <span class="font-medium text-gray-800">{{ $statusLabel }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-gray-600">Paket</span>
                    <span class="font-medium text-gray-800">{{ $pkg['label'] ?? $instance->package }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-gray-600">Masa aktif</span>
                    <span class="font-medium text-gray-800">+{{ $pkg['days'] ?? ($instance->package === 'yearly' ? 365 : 30) }} hari</span>
                </div>
                <div class="flex justify-between gap-4 pt-2 border-t border-emerald-100">
                    <span class="text-gray-600">Harga paket</span>
                    <span class="font-medium text-gray-800">Rp{{ number_format($amount, 0, ',', '.') }}</span>
                </div>
                <p class="text-xs text-gray-500 pt-1">+ biaya admin sesuai metode di halaman checkout</p>
            </div>

            @if ($errors->any())
                <div class="mb-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('renewal.prepare', $instance->subdomain) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email (opsional, untuk bukti bayar)</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', $instance->pic_email) }}"
                        class="app-input"
                        placeholder="admin@madrasah.sch.id"
                    >
                </div>

                <button type="submit" class="w-full app-btn font-semibold py-3 rounded-xl">
                    Lanjut ke Checkout
                </button>
            </form>

            <p class="text-xs text-gray-500 mt-4 text-center">
                Pembayaran aman melalui Xendit. Mendukung QRIS, e-wallet, virtual account, dan retail outlet.
            </p>
        </div>

        <p class="text-center text-gray-500 text-xs mt-6">
            <a href="{{ route('landing') }}" class="hover:text-emerald-700 transition-colors">&larr; Kembali ke beranda</a>
        </p>
    </div>

</body>
</html>
