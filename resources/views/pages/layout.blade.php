@extends('layouts.landing')

@section('content')
    <section class="pt-28 pb-10 lg:pt-32 bg-gradient-to-b from-emerald-50 to-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <p class="text-sm font-medium text-emerald-700 mb-2">@yield('eyebrow', 'Informasi')</p>
            <h1 class="text-3xl sm:text-4xl font-bold text-emerald-950">@yield('heading')</h1>
            @hasSection('updated')
                <p class="mt-3 text-sm text-gray-500">Terakhir diperbarui: @yield('updated')</p>
            @endif
        </div>
    </section>

    <section class="pb-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="legal-content">
                @yield('page')
            </div>

            <div class="mt-12 pt-6 border-t border-emerald-100 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                <a href="{{ route('pages.about') }}" class="text-emerald-700 hover:underline">Tentang Kami</a>
                <a href="{{ route('pages.terms') }}" class="text-emerald-700 hover:underline">Syarat &amp; Ketentuan</a>
                <a href="{{ route('pages.privacy') }}" class="text-emerald-700 hover:underline">Kebijakan Privasi</a>
                <a href="{{ route('pages.refund') }}" class="text-emerald-700 hover:underline">Kebijakan Pengembalian Dana</a>
                <a href="{{ route('pages.contact') }}" class="text-emerald-700 hover:underline">Kontak</a>
            </div>
        </div>
    </section>
@endsection
