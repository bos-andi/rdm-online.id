<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran Berhasil — RDM Online</title>
    @include('layouts.partials.favicon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-50 via-green-50 to-white font-sans p-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <x-brand.logo height="h-12" class="mx-auto" />
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-emerald-100/80 border border-emerald-100 p-8 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl">✓</div>
            <h1 class="text-xl font-bold text-gray-900">Pembayaran Diterima</h1>
            <p class="text-sm text-gray-500 mt-2">
                Terima kasih! Pembayaran perpanjang langganan untuk
                <strong>{{ $fqdn }}</strong> sedang diproses.
            </p>

            @if ($payment?->status === 'paid')
                <p class="mt-4 text-sm text-emerald-700 font-medium">RDM Anda sudah diaktifkan kembali.</p>
                <a href="https://{{ $fqdn }}" class="inline-block mt-6 app-btn px-6 py-3 rounded-xl font-semibold">
                    Buka RDM
                </a>
            @else
                <p class="mt-4 text-sm text-amber-700">
                    Aktivasi otomatis membutuhkan beberapa detik. Silakan refresh halaman RDM Anda sebentar lagi.
                </p>
                <a href="https://{{ $fqdn }}" class="inline-block mt-6 app-btn px-6 py-3 rounded-xl font-semibold">
                    Coba Buka RDM
                </a>
            @endif
        </div>

        <p class="text-center text-gray-500 text-xs mt-6">
            <a href="{{ route('user.login') }}" class="hover:text-emerald-700 transition-colors">Masuk panel pengguna</a>
        </p>
    </div>

</body>
</html>
