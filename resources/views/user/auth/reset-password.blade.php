<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Baru — {{ config('app.name') }}</title>
    @include('layouts.partials.favicon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-50 via-green-50 to-white font-sans p-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <x-brand.logo height="h-12" class="mx-auto" />
            <p class="text-gray-500 text-sm mt-3">{{ config('user-menu.brand.subtitle') }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-emerald-100/80 border border-emerald-100 p-8">
            <h1 class="text-xl font-bold text-gray-900">Buat Password Baru</h1>
            <p class="text-gray-500 text-sm mt-1">Masukkan password baru untuk akun Anda.</p>

            @if ($errors->any())
                <div class="mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('user.password.update') }}" class="mt-6 space-y-4">
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email', $email) }}"
                        required
                        autofocus
                        class="app-input"
                        placeholder="admin@madrasah.sch.id"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        minlength="8"
                        class="app-input"
                        placeholder="Min. 8 karakter"
                        autocomplete="new-password"
                    >
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password</label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        minlength="8"
                        class="app-input"
                        placeholder="Ulangi password baru"
                        autocomplete="new-password"
                    >
                </div>

                <button type="submit" class="w-full app-btn font-semibold py-3 rounded-xl">
                    Simpan Password
                </button>
            </form>
        </div>

        <p class="text-center text-gray-500 text-xs mt-6">
            <a href="{{ route('user.login') }}" class="hover:text-emerald-700 transition-colors">&larr; Kembali ke login</a>
        </p>
    </div>

</body>
</html>
