<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Affiliate — {{ config('app.name') }}</title>
    @include('layouts.partials.favicon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-50 via-green-50 to-white font-sans p-4">

    <div class="w-full max-w-md py-8">
        <div class="text-center mb-8">
            <a href="{{ route('landing') }}"><x-brand.logo height="h-12" class="mx-auto" /></a>
            <p class="text-gray-500 text-sm mt-3">Program Affiliate</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-emerald-100/80 border border-emerald-100 p-8">
            <h1 class="text-xl font-bold text-gray-900">Daftar Jadi Affiliate</h1>
            <p class="text-gray-500 text-sm mt-1">
                Dapatkan komisi {{ app(\App\Services\AffiliateService::class)->formatRate($settings['default_rate']) }} dari setiap pembayaran madrasah yang Anda ajak.
            </p>

            @if ($errors->any())
                <div class="mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('affiliate.register') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama lengkap</label>
                    <input id="name" name="name" value="{{ old('name') }}" required autofocus class="app-input" placeholder="Nama sesuai rekening">
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required class="app-input" placeholder="nama@email.com" autocomplete="email">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp</label>
                    <input id="phone" name="phone" value="{{ old('phone') }}" required class="app-input" placeholder="08xxxxxxxxxx" inputmode="tel">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <x-password-input id="password" name="password" label="Password" placeholder="Min. 8 karakter" required />
                    <x-password-input id="password_confirmation" name="password_confirmation" label="Konfirmasi" placeholder="Ulangi" required />
                </div>

                <label class="flex items-start gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="agree" value="1" @checked(old('agree')) class="mt-0.5 rounded border-emerald-300 text-emerald-600" required>
                    <span>
                        Saya menyetujui <a href="{{ route('pages.terms') }}" target="_blank" class="text-emerald-700 hover:underline">Syarat &amp; Ketentuan</a>
                        serta tidak akan melakukan spam atau klaim menyesatkan saat mempromosikan RDM Online.
                    </span>
                </label>

                <button type="submit" class="w-full app-btn font-semibold py-3 rounded-xl">
                    Daftar Sekarang
                </button>
            </form>

            <p class="text-sm text-gray-500 mt-6 text-center">
                Sudah terdaftar?
                <a href="{{ route('affiliate.login') }}" class="text-emerald-700 font-medium hover:underline">Masuk</a>
            </p>
        </div>

        <p class="text-center text-gray-500 text-xs mt-6">
            <a href="{{ route('affiliate.program') }}" class="hover:text-emerald-700 transition-colors">&larr; Info program affiliate</a>
        </p>
    </div>

</body>
</html>
