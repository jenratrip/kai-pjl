@extends('layouts.superadmin')

@section('title', 'Monitoring Sistem')

@section('content')
<div class="space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                Monitoring Sistem
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Pantau koneksi database, aktivitas, dan error aplikasi.
            </p>
        </div>

        <a href="{{ route('superadmin.monitoring.index') }}"
           class="rounded-lg bg-[#0b1f3a] px-4 py-2 text-sm font-medium text-white hover:bg-blue-900">
            Muat Ulang
        </a>
    </div>

    {{-- Status utama --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Koneksi Database</p>

            <div class="mt-3 flex items-center gap-2">
                <span class="h-3 w-3 rounded-full
                    {{ $databaseConnected ? 'bg-green-500' : 'bg-red-500' }}">
                </span>

                <span class="font-semibold
                    {{ $databaseConnected ? 'text-green-700' : 'text-red-700' }}">
                    {{ $databaseConnected ? 'Terhubung' : 'Bermasalah' }}
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Aktivitas Hari Ini</p>
            <p class="mt-3 text-3xl font-bold text-gray-800">
                {{ number_format($activitiesToday) }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Berdasarkan Activity Log</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Error Hari Ini</p>
            <p class="mt-3 text-3xl font-bold
                {{ $errorsToday > 0 ? 'text-red-600' : 'text-green-700' }}">
                {{ number_format($errorsToday) }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
                Error level ERROR ke atas di log Laravel
            </p>
        </div>

    </div>

    {{-- Informasi aplikasi --}}
    <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-800">
            Informasi Aplikasi
        </h2>

        <div class="mt-4 divide-y divide-gray-100">
            <div class="flex flex-wrap justify-between gap-2 py-3">
                <span class="text-sm text-gray-500">Versi Laravel</span>
                <span class="text-sm font-medium">{{ $laravelVersion }}</span>
            </div>

            <div class="flex flex-wrap justify-between gap-2 py-3">
                <span class="text-sm text-gray-500">Versi PHP</span>
                <span class="text-sm font-medium">{{ $phpVersion }}</span>
            </div>

            <div class="flex flex-wrap justify-between gap-2 py-3">
                <span class="text-sm text-gray-500">Environment</span>
                <span class="text-sm font-medium">{{ $environment }}</span>
            </div>

            <div class="flex flex-wrap justify-between gap-2 py-3">
                <span class="text-sm text-gray-500">Mode Debug</span>
                <span class="text-sm font-medium">
                    {{ $debugMode ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>

            <div class="flex flex-wrap justify-between gap-2 py-3">
                <span class="text-sm text-gray-500">File Log</span>
                <span class="text-sm font-medium">
                    @if (!$logExists)
                        Tidak ditemukan
                    @else
                        {{ is_readable(storage_path('logs/laravel.log'))
                            ? 'Tersedia dan dapat dibaca'
                            : 'Tidak dapat dibaca' }}
                    @endif
                </span>
            </div>
        </div>
    </section>



    {{-- Ringkasan error --}}
    <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">
                    Error Sistem
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Ringkasan masalah yang tercatat pada log Laravel.
                </p>
            </div>

            <span class="rounded-full px-3 py-1 text-sm font-semibold
                {{ $errorsToday > 0
                    ? 'bg-red-50 text-red-700'
                    : 'bg-green-50 text-green-700' }}">
                {{ $errorsToday }} error hari ini
            </span>
        </div>

        {{-- Filter error --}}
        <div class="mt-5 flex flex-wrap gap-2">
            <button type="button" data-error-filter="all"
                class="error-filter rounded-lg bg-[#0b1f3a] px-3 py-2 text-sm text-white">
                Semua
            </button>

            <button type="button" data-error-filter="today"
                class="error-filter rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600">
                Hari ini
            </button>

            <button type="button" data-error-filter="ERROR"
                class="error-filter rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600">
                Error
            </button>

            <button type="button" data-error-filter="CRITICAL"
                class="error-filter rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-600">
                Critical
            </button>
        </div>

        @if (count($recentErrors) > 0)
            <div id="error-list" class="mt-4 space-y-3">
                @foreach ($recentErrors as $index => $error)
                    @php
                        $message = $error['message'] ?? '';
                        $lowerMessage = strtolower($message);

                        if (
                            str_contains($lowerMessage, 'cannot end a section')
                            || str_contains($lowerMessage, 'view [')
                        ) {
                            $friendlyTitle = 'Masalah pada tampilan halaman';
                            $friendlyDescription = 'Laravel tidak dapat merender tampilan. Periksa struktur Blade dan nama file view.';
                        } elseif (
                            str_contains($lowerMessage, 'sqlstate')
                            || str_contains($lowerMessage, 'database')
                            || str_contains($lowerMessage, 'table')
                        ) {
                            $friendlyTitle = 'Masalah pada database';
                            $friendlyDescription = 'Operasi database gagal. Periksa tabel, kolom, relasi, dan migration.';
                        } elseif (
                            str_contains($lowerMessage, 'namespace declaration')
                            || str_contains($lowerMessage, 'syntax error')
                        ) {
                            $friendlyTitle = 'Kesalahan sintaks kode';
                            $friendlyDescription = 'Periksa penulisan kode PHP pada file dan baris yang disebutkan.';
                        } elseif (str_contains($lowerMessage, 'duplicate entry')) {
                            $friendlyTitle = 'Data sudah terdaftar';
                            $friendlyDescription = 'Ada nilai unik yang sudah digunakan, misalnya NIP user.';
                        } elseif (str_contains($lowerMessage, 'password')) {
                            $friendlyTitle = 'Masalah pada data password';
                            $friendlyDescription = 'Periksa proses validasi dan penyimpanan password. Jangan tampilkan nilai password.';
                        } else {
                            $friendlyTitle = 'Kesalahan aplikasi';
                            $friendlyDescription = 'Periksa pesan dan lokasi kode untuk mengetahui penyebab lebih lanjut.';
                        }

                        $errorDate = substr($error['date'] ?? '', 0, 10);
                        $errorLevel = strtoupper($error['level'] ?? 'ERROR');
                    @endphp

                    <article
                        class="error-item rounded-lg border border-gray-200"
                        data-error-date="{{ $errorDate }}"
                        data-error-level="{{ $errorLevel }}"
                    >
                        <div class="p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h3 class="font-semibold text-gray-800">
                                        {{ $friendlyTitle }}
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ $friendlyDescription }}
                                    </p>

                                    <p class="mt-2 text-xs text-gray-500">
                                        {{ $error['date'] ?? 'Waktu tidak tersedia' }}
                                    </p>
                                </div>

                                <span class="rounded-full px-3 py-1 text-xs font-semibold
                                    {{ in_array($errorLevel, ['CRITICAL', 'ALERT', 'EMERGENCY'])
                                        ? 'bg-red-100 text-red-800'
                                        : 'bg-amber-50 text-amber-800' }}">
                                    {{ $errorLevel }}
                                </span>
                            </div>

                            <details class="mt-3 border-t border-gray-100 pt-3">
                                <summary class="cursor-pointer text-sm font-medium text-blue-700">
                                    Lihat detail teknis
                                </summary>

                                <div class="mt-3 space-y-3">
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500">
                                            Pesan error asli
                                        </p>
                                        <p class="mt-1 break-words text-sm text-gray-700">
                                            {{ $message }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold text-gray-500">
                                            Lokasi kode
                                        </p>
                                        <p class="mt-1 break-all text-sm text-gray-700">
                                            {{ $error['file'] ?? 'Tidak ditemukan dalam log' }}
                                            @if (!empty($error['line']))
                                                :{{ $error['line'] }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </details>
                        </div>
                    </article>
                @endforeach
            </div>

            <p id="no-filter-results"
                class="mt-4 hidden rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                Tidak ada error yang sesuai dengan filter.
            </p>
        @else
            <div class="mt-4 rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                Belum ada entri error yang terdeteksi pada file log.
            </div>
        @endif
    </section>

    <script>
        document.querySelectorAll('.error-filter').forEach((button) => {
            button.addEventListener('click', () => {
                const filter = button.dataset.errorFilter;
                const items = document.querySelectorAll('.error-item');
                let visibleCount = 0;

                items.forEach((item) => {
                    const matches =
                        filter === 'all' ||
                        (filter === 'today' &&
                            item.dataset.errorDate ===
                                new Date().toLocaleDateString('en-CA')) ||
                        item.dataset.errorLevel === filter;

                    item.classList.toggle('hidden', !matches);

                    if (matches) visibleCount++;
                });

                document.querySelectorAll('.error-filter').forEach((other) => {
                    other.classList.remove('bg-[#0b1f3a]', 'text-white');
                    other.classList.add('border', 'border-gray-200', 'text-gray-600');
                });

                button.classList.remove('border', 'border-gray-200', 'text-gray-600');
                button.classList.add('bg-[#0b1f3a]', 'text-white');

                const emptyMessage = document.getElementById('no-filter-results');
                if (emptyMessage) {
                    emptyMessage.classList.toggle('hidden', visibleCount > 0);
                }
            });
        });
    </script>
</div>
@endsection