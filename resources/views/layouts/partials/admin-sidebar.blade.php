<aside
    id="sidebar"
    class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-slate-900 border-r border-slate-800 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform"
>
    <div class="p-5 border-b border-slate-800">
        <x-brand.logo height="h-9" />
        <p class="text-xs text-slate-500 mt-2">{{ config('admin-menu.brand.subtitle') }}</p>
    </div>

    <nav class="flex-1 overflow-y-auto sidebar-scroll p-3 space-y-1">
        @foreach (config('admin-menu.items', []) as $item)
            <a
                href="{{ route($item['route']) }}"
                @class([
                    'admin-menu-item w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm',
                    'active' => request()->routeIs($item['route']),
                ])
            >
                <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4"></i>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="p-4 border-t border-slate-800">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center text-sm font-bold text-white">
                {{ strtoupper(substr(session('admin_email', 'A'), 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-200 truncate">Administrator</p>
                <p class="text-xs text-slate-500 truncate">{{ session('admin_email') }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="w-full text-xs py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-rose-400 transition-colors">
                Logout
            </button>
        </form>
    </div>
</aside>
