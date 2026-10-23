<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistem PJL')</title>

    {{-- CSS utama aplikasi --}}
    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    {{-- CSS header, sidebar, dan layout --}}
    <link
        rel="stylesheet"
        href="{{ asset('css/user/layout-user.css') }}"
    >

    {{-- CSS khusus setiap halaman --}}
    @stack('styles')
</head>

<body class="min-h-screen bg-[#f5f6f8] text-[#1d2d50]">

    {{-- HEADER --}}
    @include('components.user.header')

    {{-- SIDEBAR --}}
    @include('components.user.sidebar')

    {{-- OVERLAY SIDEBAR MOBILE --}}
    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
        onclick="closeSidebar()"
    ></div>

    {{-- KONTEN HALAMAN --}}
    <main class="main">
        @yield('content')
    </main>

    {{-- JAVASCRIPT LAYOUT --}}
    <script
        src="{{ asset('js/user/layout.js') }}"
        defer
    ></script>

    {{-- JAVASCRIPT KHUSUS SETIAP HALAMAN --}}
    @stack('scripts')

</body>
</html>