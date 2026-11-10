<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    {{-- CSRF Token --}}
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        @yield('title', 'Superadmin') - KAI PJL
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    @stack('styles')

</head>

<body class="superadmin-shell min-h-screen bg-slate-50 text-slate-800" x-data="{ sidebarOpen: false }" x-on:keydown.escape.window="sidebarOpen = false">

    <div
        x-cloak
        x-show="sidebarOpen"
        x-transition.opacity
        x-on:click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"
        aria-hidden="true"
    ></div>

    {{-- Sidebar --}}
    <x-superadmin.sidebar />

    {{-- Main Content --}}
    <div class="min-h-screen lg:pl-64">

        {{-- Header --}}
        <x-superadmin.header />

        {{-- Page Content --}}
        <main class="superadmin-content px-4 py-5 sm:px-6 sm:py-7 xl:px-8">

            @yield('content')

        </main>

    </div>

    @stack('scripts')

</body>

</html>
