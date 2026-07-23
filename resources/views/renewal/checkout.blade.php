<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout — {{ $instance->madrasah_name }}</title>
    @include('layouts.partials.favicon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-emerald-50 via-green-50 to-white font-sans py-8 px-4">

    <div class="max-w-2xl mx-auto">
        <div class="text-center mb-6">
            <x-brand.logo height="h-10" class="mx-auto" />
            <p class="text-gray-500 text-sm mt-2">Checkout Pembayaran</p>
        </div>

        @include('renewal.partials.checkout-form')

        @unless (session('admin_authenticated'))
            <p class="text-center mt-6">
                <a href="{{ route('admin.login', ['redirect' => url()->current()]) }}" class="text-xs text-slate-500 hover:text-emerald-700 transition-colors">
                    Administrator? Login untuk kelola transaksi
                </a>
            </p>
        @endunless
    </div>

    @include('renewal.partials.checkout-scripts')
</body>
</html>
