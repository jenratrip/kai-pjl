
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistem PJL')</title>

    @stack('styles')

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body
    class="min-h-screen bg-[#f5f6f8] text-[#1d2d50] antialiased"
    x-data="{ sidebarOpen: false }"
    @keydown.escape.window="sidebarOpen = false"
>
    {{-- Overlay mobile --}}
    <div
        x-cloak
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        aria-hidden="true"
    ></div>

    {{-- Sidebar PJL --}}
    @include('components.user.sidebar')

    {{-- Header PJL --}}
    @include('components.user.header')

    {{-- Konten halaman --}}
    <main
        class="min-h-screen pt-[76px] transition-[margin,width] duration-300 ease-in-out"
        :class="sidebarOpen
            ? 'ml-[230px] w-[calc(100%-230px)]'
            : 'ml-0 w-full'"
    >
        <div class="min-h-[calc(100vh-76px)]">
            @yield('content')
        </div>
    </main>

    @stack('scripts')
</body>
</html>
