<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Email — {{ config('app.name') }}</title>
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
            <h1 class="text-xl font-bold text-gray-900">Verifikasi Email</h1>
            <p class="text-gray-500 text-sm mt-1">
                Kami mengirim 6 digit kode ke <strong class="text-gray-900 break-all">{{ $affiliate->email }}</strong>.
                Cek folder Inbox atau Spam, lalu masukkan kodenya di bawah.
            </p>

            @if (session('status'))
                <div class="mt-4 p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any() || session('send_error'))
                <div class="mt-4 p-3 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                    {{ $errors->first() ?: session('send_error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('affiliate.verification.verify') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Kode verifikasi</label>
                    <input id="code" name="code" required autofocus maxlength="6" inputmode="numeric" pattern="\d{6}"
                        autocomplete="one-time-code" placeholder="••••••"
                        class="app-input text-center text-2xl font-mono tracking-widest">
                </div>

                <button type="submit" class="w-full app-btn font-semibold py-3 rounded-xl">
                    Verifikasi Email
                </button>
            </form>

            <form method="POST" action="{{ route('affiliate.verification.resend') }}" class="mt-4 text-center">
                @csrf
                <button type="submit" id="resend-btn" data-cooldown="{{ $cooldown }}"
                    class="text-sm text-emerald-700 font-medium hover:underline disabled:opacity-50 disabled:cursor-not-allowed"
                    @disabled($cooldown > 0)>
                    Kirim ulang kode
                </button>
            </form>

            <details class="mt-6 text-sm text-gray-600" @if ($errors->has('email')) open @endif>
                <summary class="cursor-pointer text-gray-500 hover:text-emerald-700">Salah ketik email?</summary>
                <form method="POST" action="{{ route('affiliate.verification.email') }}" class="mt-3 space-y-3">
                    @csrf
                    <input name="email" type="email" value="{{ old('email') }}" required class="app-input" placeholder="Email yang benar" autocomplete="email">
                    <button type="submit" class="w-full app-btn font-semibold py-3 rounded-xl">Ganti Email &amp; Kirim Kode</button>
                </form>
            </details>
        </div>

        <form method="POST" action="{{ route('affiliate.logout') }}" class="text-center mt-6">
            @csrf
            <button type="submit" class="text-gray-500 text-xs hover:text-emerald-700 transition-colors">Keluar</button>
        </form>
    </div>

    <script>
        (() => {
            const button = document.getElementById('resend-btn');
            let remaining = Number(button.dataset.cooldown) || 0;
            const label = 'Kirim ulang kode';
            const tick = () => {
                if (remaining <= 0) {
                    button.disabled = false;
                    button.textContent = label;
                    return;
                }
                button.textContent = `${label} (${remaining} dtk)`;
                remaining -= 1;
                setTimeout(tick, 1000);
            };
            tick();

            const input = document.getElementById('code');
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, 6);
            });
        })();
    </script>
</body>
</html>
