<aside
    id="sidebar"
    class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-white border-r border-emerald-100 shadow-lg shadow-emerald-100/40 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform"
>
    <div class="p-5 border-b border-emerald-100 bg-white">
        <x-brand.logo height="h-10" />
        <p class="text-xs text-gray-500 mt-2">{{ config('user-menu.brand.subtitle') }}</p>
    </div>

    <nav class="flex-1 overflow-y-auto sidebar-scroll p-3 space-y-1">
        @foreach (config('user-menu.items', []) as $item)
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
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center text-sm font-bold text-white">
                {{ strtoupper(substr($userName ?? 'M', 0, 1)) }}
            </div>
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $userName ?? 'Pengguna' }}</p>
                <p class="text-xs text-gray-500">{{ $userEmail ?? '' }}</p>
            </div>
        </div>
    </div>
</aside>
