<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        @yield('title', 'Superadmin') - KAI PJL
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body class="min-h-screen bg-gray-100 text-gray-800">

    {{-- Sidebar --}}
    <x-superadmin.sidebar />

    {{-- Main Content --}}
    <div class="min-h-screen pl-64">

        {{-- Header --}}
        <x-superadmin.header />

        {{-- Page Content --}}
        <main class="p-8">

            @yield('content')

        </main>

    </div>

</body>

</html>