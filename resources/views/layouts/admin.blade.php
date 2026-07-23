<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('admin-menu.brand.title')) — Admin</title>
    @include('layouts.partials.favicon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen flex bg-slate-100 text-slate-800 font-sans">

    @include('layouts.partials.admin-mobile-header')

    @include('layouts.partials.admin-sidebar')

    <div
        id="sidebar-overlay"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-30 hidden lg:hidden"
    ></div>

    <main class="flex-1 lg:ml-0 mt-14 lg:mt-0 overflow-y-auto min-h-screen">
        <div class="p-6 max-w-7xl mx-auto">
            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>
</html>
