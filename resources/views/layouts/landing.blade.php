<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Deploy RDM Online madrasah Anda dalam satu klik. DNS, SSL, database, dan hosting otomatis. Siap pakai dari RDMhosting.">
    <title>@yield('title', 'RDM-ONLINE.ID — Deploy RDM Madrasah Satu Klik')</title>
    @include('layouts.partials.favicon')
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans text-gray-800 antialiased bg-white">

    @include('landing.partials.navbar')

    @yield('content')

    @include('landing.partials.footer')

    @stack('scripts')
</body>
</html>
