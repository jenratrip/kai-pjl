@extends('layouts.superadmin')

@section('title', 'Monitoring Sistem')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Monitoring Sistem</h1>
            <p class="mt-1 text-sm text-gray-500">Pantau koneksi database, aktivitas, dan riwayat error aplikasi.</p>
        </div>

        <a href="{{ route('superadmin.monitoring.index', request()->query()) }}"
           class="rounded-lg bg-[#0b1f3a] px-4 py-2 text-sm font-medium text-white hover:bg-blue-900">
            Muat Ulang
        </a>
    </div>

    {{-- Status utama --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Koneksi Database</p>
            <div class="mt-3 flex items-center gap-2">
                <span class="h-3 w-3 rounded-full {{ $databaseConnected ? 'bg-green-500' : 'bg-red-500' }}"></span>
                <span class="font-semibold {{ $databaseConnected ? 'text-green-700' : 'text-red-700' }}">
                    {{ $databaseConnected ? 'Terhubung' : 'Bermasalah' }}
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Aktivitas Hari Ini</p>
            <p class="mt-3 text-3xl font-bold text-gray-800">{{ number_format($activitiesToday) }}</p>
            <p class="mt-1 text-xs text-gray-500">Berdasarkan Activity Log</p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Error Hari Ini</p>
            <p class="mt-3 text-3xl font-bold {{ $errorsToday > 0 ? 'text-red-600' : 'text-green-700' }}">
                {{ number_format($errorsToday) }}
            </p>
            <p class="mt-1 text-xs text-gray-500">Level ERROR, CRITICAL, ALERT, dan EMERGENCY</p>
        </div>
    </div>

    {{-- Informasi aplikasi --}}
    <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-gray-800">Informasi Aplikasi</h2>
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
                <span class="text-sm font-medium">{{ $debugMode ? 'Aktif' : 'Nonaktif' }}</span>
            </div>
            <div class="flex flex-wrap justify-between gap-2 py-3">
                <span class="text-sm text-gray-500">File Log Laravel</span>
                <span class="text-sm font-medium">
                    @if (!$logExists)
                        Tidak ditemukan
                    @elseif (!$logReadable)
                        Ada, tetapi tidak dapat dibaca
                    @else
                        Tersedia dan dapat dibaca
                    @endif
                </span>
            </div>
        </div>
    </section>

    {{-- Riwayat error dengan filter dan paginasi server-side --}}
    <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-800">Riwayat Error Sistem</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Secara default menampilkan log tiga bulan terakhir. Hasil dipaginasi 20 entri per halaman.
                </p>
            </div>
            <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $errorsToday > 0 ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700' }}">
                {{ number_format($errorsToday) }} error hari ini
            </span>
        </div>

        <form id="error-search-form" method="GET" action="{{ route('superadmin.monitoring.index') }}"
              class="mt-5 grid grid-cols-1 items-end gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(220px,2fr)_minmax(145px,1fr)_minmax(145px,1fr)_minmax(150px,1fr)_auto]">
            <div class="min-w-0">
                <label for="search" class="mb-1 block text-sm font-medium text-gray-700">Cari pesan atau file</label>
                <input id="search" name="search" type="search" value="{{ $search }}" maxlength="200"
                       placeholder="Ketik pesan atau nama file..."
                       autocomplete="off"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="min-w-0">
                <label for="from" class="mb-1 block text-sm font-medium text-gray-700">Dari tanggal</label>
                <input id="from" name="from" type="date" value="{{ $fromDate }}"
                       class="w-full min-w-0 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="min-w-0">
                <label for="to" class="mb-1 block text-sm font-medium text-gray-700">Sampai tanggal</label>
                <input id="to" name="to" type="date" value="{{ $toDate }}"
                       class="w-full min-w-0 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>

            <div class="min-w-0">
                <label for="level" class="mb-1 block text-sm font-medium text-gray-700">Level</label>
                <select id="level" name="level"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    @foreach (['ALL' => 'Semua level', 'WARNING' => 'Warning', 'ERROR' => 'Error', 'CRITICAL' => 'Critical', 'ALERT' => 'Alert', 'EMERGENCY' => 'Emergency'] as $value => $label)
                        <option value="{{ $value }}" @selected($selectedLevel === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <a href="{{ route('superadmin.monitoring.index') }}"
                   class="inline-flex min-h-[38px] w-full items-center justify-center whitespace-nowrap rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto">
                    Reset
                </a>
            </div>
        </form>

        <p id="error-search-status" class="mt-2 hidden text-xs text-gray-500" aria-live="polite">
            Mencari error...
        </p>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.getElementById('error-search-form');
                const results = document.getElementById('error-results');
                const searchInput = document.getElementById('search');
                const dateInputs = [document.getElementById('from'), document.getElementById('to')];
                const levelInput = document.getElementById('level');
                const status = document.getElementById('error-search-status');

                if (!form || !results) return;

                let searchTimer;
                let currentController = null;

                async function applyFilter(url = null) {
                    if (currentController) currentController.abort();
                    currentController = new AbortController();

                    const params = new URLSearchParams(new FormData(form));
                    params.delete('page');

                    const queryString = params.toString();
                    const baseUrl = form.getAttribute('action');
                    const targetUrl = url || (baseUrl + (queryString ? '?' + queryString : ''));

                    if (status) {
                        status.textContent = 'Memuat hasil...';
                        status.classList.remove('hidden');
                    }

                    try {
                        const response = await fetch(targetUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html'
                            },
                            signal: currentController.signal
                        });

                        if (!response.ok) throw new Error('HTTP ' + response.status);

                        const html = await response.text();
                        const parsedDocument = new DOMParser().parseFromString(html, 'text/html');
                        const newResults = parsedDocument.getElementById('error-results');

                        if (!newResults) {
                            throw new Error('Elemen error-results tidak ditemukan.');
                        }

                        results.innerHTML = newResults.innerHTML;

                        // Sinkronkan nilai filter, termasuk nilai default setelah Reset.
                        ['search', 'from', 'to', 'level'].forEach((id) => {
                            const currentInput = document.getElementById(id);
                            const newInput = parsedDocument.getElementById(id);
                            if (currentInput && newInput) currentInput.value = newInput.value;
                        });

                        window.history.replaceState({}, '', response.url || targetUrl);

                        if (status) status.textContent = 'Hasil diperbarui.';
                    } catch (error) {
                        if (error.name === 'AbortError') return;

                        if (status) status.textContent = 'Gagal memuat hasil. Coba lagi.';
                        console.error('Monitoring Sistem:', error);
                    }
                }

                searchInput?.addEventListener('input', () => {
                    window.clearTimeout(searchTimer);
                    if (status) {
                        status.textContent = 'Menunggu ketikan...';
                        status.classList.remove('hidden');
                    }

                    searchTimer = window.setTimeout(() => applyFilter(), 300);
                });

                [...dateInputs, levelInput].forEach((input) => {
                    input?.addEventListener('change', () => {
                        window.clearTimeout(searchTimer);
                        applyFilter();
                    });
                });

                form.addEventListener('submit', (event) => {
                    event.preventDefault();
                    window.clearTimeout(searchTimer);
                    applyFilter();
                });

                // Reset filter tanpa memuat ulang seluruh halaman.
                form.querySelector('a[href]')?.addEventListener('click', (event) => {
                    const link = event.currentTarget;
                    if (!link.href) return;
                    event.preventDefault();
                    window.clearTimeout(searchTimer);
                    applyFilter(link.href);
                });

                // Pagination juga berjalan tanpa refresh halaman.
                results.addEventListener('click', (event) => {
                    const link = event.target.closest('.pagination a, nav[role="navigation"] a');
                    if (!link || !results.contains(link)) return;

                    event.preventDefault();
                    window.clearTimeout(searchTimer);
                    applyFilter(link.href);
                });
            });
        </script>

        <div id="error-results">
        @if ($errors->any())
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                @foreach ($errors->all() as $errorMessage)
                    <p>{{ $errorMessage }}</p>
                @endforeach
            </div>
        @endif

        <div class="mt-5 space-y-3">
            @forelse ($recentErrors as $error)
                @php
                    $message = $error['message'] ?? 'Pesan error tidak tersedia.';
                    $lowerMessage = strtolower($message);
                    $errorLevel = strtoupper($error['level'] ?? 'ERROR');

                    if (str_contains($lowerMessage, 'sqlstate') || str_contains($lowerMessage, 'database')) {
                        $friendlyTitle = 'Masalah pada database';
                        $friendlyDescription = 'Periksa pesan asli, query, tabel, kolom, atau koneksi yang terkait.';
                    } elseif (str_contains($lowerMessage, 'view [') || str_contains($lowerMessage, 'cannot end a section')) {
                        $friendlyTitle = 'Masalah pada tampilan halaman';
                        $friendlyDescription = 'Periksa file Blade dan nama view yang disebutkan pada detail teknis.';
                    } elseif (str_contains($lowerMessage, 'syntax error') || str_contains($lowerMessage, 'parse error')) {
                        $friendlyTitle = 'Kesalahan sintaks kode';
                        $friendlyDescription = 'Periksa penulisan kode pada file dan baris yang tertera.';
                    } else {
                        $friendlyTitle = 'Entri log ' . $errorLevel;
                        $friendlyDescription = 'Kategori khusus tidak tersedia; gunakan pesan asli dan stack trace untuk menyelidiki.';
                    }
                @endphp

                <article class="rounded-lg border border-gray-200">
                    <div class="p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-gray-800">{{ $friendlyTitle }}</h3>
                                <p class="mt-1 text-sm text-gray-600">{{ $friendlyDescription }}</p>
                                <p class="mt-2 text-xs text-gray-500">{{ $error['date'] ?? 'Waktu tidak tersedia' }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-semibold
                                {{ in_array($errorLevel, ['CRITICAL', 'ALERT', 'EMERGENCY'], true) ? 'bg-red-100 text-red-800' : ($errorLevel === 'WARNING' ? 'bg-yellow-50 text-yellow-800' : 'bg-amber-50 text-amber-800') }}">
                                {{ $errorLevel }}
                            </span>
                        </div>

                        <details class="mt-3 border-t border-gray-100 pt-3">
                            <summary class="cursor-pointer text-sm font-medium text-blue-700">Lihat detail teknis</summary>
                            <div class="mt-3 space-y-3">
                                <div>
                                    <p class="text-xs font-semibold text-gray-500">Pesan error asli</p>
                                    <p class="mt-1 break-words whitespace-pre-wrap text-sm text-gray-700">{{ $message }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-500">Lokasi kode</p>
                                    <p class="mt-1 break-all text-sm text-gray-700">
                                        {{ $error['file'] ?? 'Tidak ditemukan dalam log' }}@if (!empty($error['line'])):{{ $error['line'] }}@endif
                                    </p>
                                </div>
                                @if (!empty($error['stack_trace']))
                                    <div>
                                        <p class="text-xs font-semibold text-gray-500">Stack trace / konteks log</p>
                                        <pre class="mt-1 max-h-80 overflow-auto whitespace-pre-wrap break-words rounded-lg bg-gray-900 p-3 text-xs text-gray-100">{{ $error['stack_trace'] }}</pre>
                                    </div>
                                @endif
                            </div>
                        </details>
                    </div>
                </article>
            @empty
                <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                    Tidak ada entri log yang cocok dengan rentang tanggal dan filter yang dipilih.
                    @if (!$logExists)
                        File log Laravel belum ditemukan pada folder <code>storage/logs</code>.
                    @elseif (!$logReadable)
                        File log ditemukan, tetapi tidak dapat dibaca oleh aplikasi.
                    @endif
                </div>
            @endforelse
        </div>

        @if ($recentErrors->total() > 0)
            <div class="mt-5 border-t border-gray-100 pt-4">
                <p class="mb-3 text-sm text-gray-500">
                    Menampilkan {{ $recentErrors->firstItem() }}–{{ $recentErrors->lastItem() }} dari {{ number_format($recentErrors->total()) }} entri yang cocok.
                </p>
                {{ $recentErrors->onEachSide(1)->links() }}
            </div>
        @endif
        </div>
    </section>
</div>
@endsection
