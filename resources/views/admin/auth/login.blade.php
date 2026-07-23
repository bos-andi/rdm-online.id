<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — {{ config('app.name') }}</title>
    @include('layouts.partials.favicon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-950 font-sans p-4">

    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <x-brand.logo height="h-12" class="mx-auto" />
            <p class="text-slate-400 text-sm mt-3">{{ config('admin-menu.brand.subtitle') }}</p>
        </div>

        <div class="bg-white rounded-2xl shadow-2xl shadow-black/20 p-8">
            <h1 class="text-xl font-bold text-slate-900">Masuk Admin</h1>
            <p class="text-slate-500 text-sm mt-1">Gunakan kredensial administrator Anda</p>

            @if ($errors->any())
                <div class="mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}" class="mt-6 space-y-4">
                @csrf
                @if (! empty($redirect ?? old('redirect')))
                    <input type="hidden" name="redirect" value="{{ $redirect ?? old('redirect') }}">
                @endif

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        class="admin-input"
                        placeholder="admin@example.com"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        class="admin-input"
                        placeholder="••••••••"
                    >
                </div>

                <button type="submit" class="w-full admin-btn font-semibold py-3 rounded-xl">
                    Masuk Dashboard
                </button>
            </form>
        </div>

        <p class="text-center text-slate-500 text-xs mt-6">
            <a href="{{ route('landing') }}" class="hover:text-emerald-400 transition-colors">&larr; Kembali ke beranda</a>
        </p>
    </div>

</body>
</html>
