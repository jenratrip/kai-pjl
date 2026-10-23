<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistem PJL')</title>

    {{-- CSS utama layout operasional --}}
    <link rel="stylesheet" href="{{ asset('css/user-operasional/layout.css') }}">

    {{-- CSS tambahan: mendukung @section dan @push --}}
    @yield('styles')
    @stack('styles')
</head>
<body>
    {{--
        Dinas aktif petugas yang sedang login.
        Dipakai header untuk menampilkan jenis petugas (Reguler / Wakilan)
        sesuai pilihan saat login (disimpan di dinas.is_pengganti).
    --}}
    @php
        $dinasHeader = auth()->check()
            ? \App\Models\Dinas::where('user_id', auth()->id())
                ->where('status', 'berlangsung')
                ->latest('id')
                ->first()
            : null;

        $jenisPetugas = $dinasHeader?->is_pengganti ? 'wakilan' : 'reguler';
    @endphp

    {{-- Header operasional --}}
    @include('components.user-operasional.header', [
        'dinasHeader'  => $dinasHeader,
        'jenisPetugas' => $jenisPetugas,
    ])

    {{-- Sidebar operasional --}}
    @include('components.user-operasional.sidebar')

    {{-- Konten halaman --}}
    <main class="operasional-content">
        @yield('content')
    </main>

    {{-- JavaScript utama layout operasional --}}
    <script src="{{ asset('js/user-operasional/layout.js') }}"></script>

    {{-- JavaScript tambahan: mendukung @section dan @push --}}
    @yield('scripts')
    @stack('scripts')
</body>
</html>