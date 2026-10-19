<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel Affiliate') — {{ config('app.name') }}</title>
    @include('layouts.partials.favicon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen flex bg-gradient-to-br from-emerald-50 via-green-50 to-white text-gray-800 font-sans">

    @include('layouts.partials.mobile-header')

    @php($affiliate = auth('affiliate')->user())

    <aside
        id="sidebar"
        class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-white border-r border-emerald-100 shadow-lg shadow-emerald-100/40 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform"
    >
        <div class="p-5 border-b border-emerald-100 bg-white">
            <x-brand.logo height="h-10" />
            <p class="text-xs text-gray-500 mt-2">{{ config('affiliate-menu.brand.subtitle') }}</p>
        </div>

        <nav class="flex-1 overflow-y-auto sidebar-scroll p-3 space-y-1">
            @foreach (config('affiliate-menu.items', []) as $item)
                <a
                    href="{{ route($item['route']) }}"
                    @class([
                        'menu-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm',
                        'active' => request()->routeIs($item['route']),
                    ])
                >
                    <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4"></i>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="p-4 border-t border-emerald-100 bg-emerald-50/50">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center text-sm font-bold text-white shrink-0">
                    {{ strtoupper(substr($affiliate?->name ?? 'A', 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">{{ $affiliate?->name }}</p>
                    <p class="text-xs text-gray-500 truncate">Kode: {{ $affiliate?->code }}</p>
                </div>
            </div>
            <form method="POST" action="{{ route('affiliate.logout') }}">
                @csrf
                <button type="submit" class="w-full text-xs py-2 rounded-lg border border-emerald-200 text-gray-600 hover:text-rose-600 hover:border-rose-200 transition-colors">
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    <div
        id="sidebar-overlay"
        class="fixed inset-0 bg-emerald-900/20 backdrop-blur-sm z-30 hidden lg:hidden"
    ></div>

    <main class="flex-1 lg:ml-0 mt-14 lg:mt-0 overflow-y-auto min-h-screen">
        <div class="p-6 max-w-6xl mx-auto">
            @if (session('status'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-copy]');
            if (!button) return;

            navigator.clipboard.writeText(button.dataset.copy).then(() => {
                const label = button.querySelector('[data-copy-label]');
                if (!label) return;
                const original = label.textContent;
                label.textContent = 'Tersalin!';
                setTimeout(() => { label.textContent = original; }, 1500);
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
