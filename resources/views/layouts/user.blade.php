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
    class="min-h-screen overflow-x-hidden bg-[#f5f6f8] text-[#1d2d50] antialiased"
    x-data="{
        sidebarOpen: window.matchMedia('(min-width: 1024px)').matches
            && localStorage.getItem('pjl_sidebar_open') === 'true',

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;

            localStorage.setItem(
                'pjl_sidebar_open',
                this.sidebarOpen ? 'true' : 'false'
            );
        },

        closeSidebar() {
            this.sidebarOpen = false;

            localStorage.setItem('pjl_sidebar_open', 'false');
        }
    }"
    @keydown.escape.window="closeSidebar()"
>
    {{-- =====================================================
         OVERLAY MOBILE
    ====================================================== --}}
    <div
        x-cloak
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-in-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in-out duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="closeSidebar()"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
        aria-hidden="true"
    ></div>


    {{-- =====================================================
         SIDEBAR PJL
    ====================================================== --}}
    @include('components.user.sidebar')


    {{-- =====================================================
         HEADER PJL
    ====================================================== --}}
    @include('components.user.header')


    {{-- =====================================================
         KONTEN HALAMAN
    ====================================================== --}}
    <main
        class="min-h-screen pt-[76px] transition-[margin,width] duration-300 ease-in-out"
        :class="sidebarOpen
            ? 'lg:ml-[260px] lg:w-[calc(100%-260px)]'
            : 'ml-0 w-full'"
    >
        <div class="min-h-[calc(100vh-76px)]">
            @yield('content')
        </div>
    </main>


    {{-- =====================================================
         SCRIPT TAMBAHAN
    ====================================================== --}}
    @stack('scripts')

</body>
</html>