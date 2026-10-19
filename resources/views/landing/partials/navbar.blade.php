<header class="fixed top-0 inset-x-0 z-50 bg-white/80 backdrop-blur-lg border-b border-emerald-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('landing') }}" class="flex items-center">
                <x-brand.logo height="h-10" />
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
                <a href="{{ route('landing') }}#fitur" class="hover:text-emerald-600 transition-colors">Fitur</a>
                <a href="{{ route('landing') }}#cara-kerja" class="hover:text-emerald-600 transition-colors">Cara Kerja</a>
                <a href="{{ route('landing') }}#harga" class="hover:text-emerald-600 transition-colors">Harga</a>
                <a href="{{ route('landing') }}#faq" class="hover:text-emerald-600 transition-colors">FAQ</a>
            </nav>

            <div class="hidden md:flex items-center gap-3">
                @auth
                    <a href="{{ route('user.dashboard') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800 px-4 py-2">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('user.login') }}" class="text-sm font-medium text-emerald-700 hover:text-emerald-800 px-4 py-2">
                        Masuk
                    </a>
                @endauth
                <a href="{{ route('deploy') }}" class="landing-btn-primary text-sm px-5 py-2.5 rounded-xl">
                    Deploy Sekarang
                </a>
            </div>

            <button id="landing-menu-toggle" type="button" class="md:hidden p-2 rounded-lg text-emerald-700 hover:bg-emerald-50">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
        </div>
    </div>

    <div id="landing-mobile-menu" class="hidden md:hidden border-t border-emerald-100 bg-white px-4 py-4 space-y-1">
        <a href="{{ route('landing') }}#fitur" class="block px-3 py-2.5 rounded-lg hover:bg-emerald-50 text-gray-700">Fitur</a>
        <a href="{{ route('landing') }}#cara-kerja" class="block px-3 py-2.5 rounded-lg hover:bg-emerald-50 text-gray-700">Cara Kerja</a>
        <a href="{{ route('landing') }}#harga" class="block px-3 py-2.5 rounded-lg hover:bg-emerald-50 text-gray-700">Harga</a>
        <a href="{{ route('landing') }}#faq" class="block px-3 py-2.5 rounded-lg hover:bg-emerald-50 text-gray-700">FAQ</a>
        <div class="pt-3 flex flex-col gap-2">
            <a href="{{ route('user.login') }}" class="text-center py-2.5 rounded-xl border border-emerald-200 text-emerald-700 font-medium">Masuk</a>
            <a href="{{ route('deploy') }}" class="landing-btn-primary text-center py-2.5 rounded-xl">Deploy Sekarang</a>
        </div>
    </div>
</header>
