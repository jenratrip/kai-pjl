<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'Sistem PJL - Admin')
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

</head>


<body
    class="min-h-screen bg-[#f5f6f8] text-[#1d2d50]"
    x-data="{ sidebarOpen: false }"
>

    {{-- Overlay Mobile --}}
    <div
        x-show="sidebarOpen"
        x-cloak
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
    ></div>


    {{-- Sidebar --}}
    <x-admin.sidebar />


    {{-- Header --}}
    <x-admin.header />


    {{-- Main --}}
    <main
        class="min-h-screen pt-[62px] transition-all duration-300 lg:ml-[230px]"
    >

        <div class="min-h-[calc(100vh-62px)]">

            @yield('content')

        </div>

        <x-admin.footer />

    </main>


    @stack('scripts')

</body>

</html>