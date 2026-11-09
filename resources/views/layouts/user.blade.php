<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistem PJL')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/user-operasional/layout.css') }}">
    @stack('styles')
</head>
<body
    x-data="{ sidebarOpen: false }"
    @keydown.escape.window="sidebarOpen = false"
    @resize.window="if (window.innerWidth > 768) sidebarOpen = false"
    :class="{ 'op-sidebar-open': sidebarOpen }"
>
    @include('components.user-operasional.header')
    @include('components.user-operasional.sidebar')

    <div class="operasional-content min-h-screen bg-slate-100 text-slate-800">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
