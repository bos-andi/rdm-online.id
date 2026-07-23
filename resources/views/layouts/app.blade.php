<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('user-menu.brand.title')) — {{ config('app.name') }}</title>
    @include('layouts.partials.favicon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen flex bg-gradient-to-br from-emerald-50 via-green-50 to-white text-gray-800 font-sans">

    @include('layouts.partials.mobile-header')

    @include('layouts.partials.sidebar')

    <div
        id="sidebar-overlay"
        class="fixed inset-0 bg-emerald-900/20 backdrop-blur-sm z-30 hidden lg:hidden"
    ></div>

    <main class="flex-1 lg:ml-0 mt-14 lg:mt-0 overflow-y-auto min-h-screen">
        <div class="p-6 max-w-6xl mx-auto">
            @yield('content')
        </div>
    </main>

    @auth
        <x-user.password-change-modal />
    @endauth

    @stack('scripts')
</body>
</html>
