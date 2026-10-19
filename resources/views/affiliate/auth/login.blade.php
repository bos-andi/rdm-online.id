<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Affiliate — {{ config('app.name') }}</title>
    @include('layouts.partials.favicon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-50 via-green-50 to-white font-sans p-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="{{ route('landing') }}"><x-brand.logo height="h-12" class="mx-auto" /></a>
            <p class="text-gray-500 text-sm mt-3">Panel Affiliate</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-emerald-100/80 border border-emerald-100 p-8">
            <h1 class="text-xl font-bold text-gray-900">Masuk Panel Affiliate</h1>
            <p class="text-gray-500 text-sm mt-1">Pantau referral, komisi, dan pencairan Anda.</p>

            @if ($errors->any())
                <div class="mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('affiliate.login') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="app-input" placeholder="nama@email.com">
                </div>
                <x-password-input id="password" name="password" label="Password" placeholder="••••••••" autocomplete="current-password" required />

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remember" value="1" class="rounded border-emerald-300 text-emerald-600">
                    Ingat saya
                </label>

                <button type="submit" class="w-full app-btn font-semibold py-3 rounded-xl">
                    Masuk
                </button>
            </form>

            <p class="text-sm text-gray-500 mt-6 text-center">
                Belum terdaftar?
                <a href="{{ route('affiliate.register') }}" class="text-emerald-700 font-medium hover:underline">Daftar jadi affiliate</a>
            </p>
            <p class="text-xs text-gray-400 mt-2 text-center">Lupa password? Hubungi {{ config('company.email') }}</p>
        </div>

        <p class="text-center text-gray-500 text-xs mt-6">
            <a href="{{ route('affiliate.program') }}" class="hover:text-emerald-700 transition-colors">&larr; Info program affiliate</a>
        </p>
    </div>

</body>
</html>
