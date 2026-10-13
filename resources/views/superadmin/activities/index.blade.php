@extends('layouts.superadmin')

@section('title', 'Activity Log')
@section('page-title', 'Activity Log')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div>
        <h2 class="text-2xl font-bold text-gray-800">
            Monitoring Aktivitas
        </h2>
        <p class="mt-1 text-sm text-gray-500">
            Pantau aktivitas pengguna yang tercatat di sistem KAI-PJL.
        </p>
    </div>

    {{-- Filter --}}
    <form id="activity-filter"
        method="GET"
        action="{{ route('superadmin.activities.index') }}"
        class="rounded-xl bg-white p-5 shadow-sm">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6">

            <div class="lg:col-span-2">
                <label for="search"
                       class="mb-1 block text-sm font-medium text-gray-700">
                    Pencarian
                </label>
                <input
                    id="search"
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Nama, NIP, deskripsi..."
                    autocomplete="off"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                >
                <p class="mt-1 text-xs text-gray-400">
                    Hasil diperbarui otomatis saat kamu mengetik.
                </p>
            </div>

            <div>
                <label for="module"
                       class="mb-1 block text-sm font-medium text-gray-700">
                    Modul
                </label>
                <select
                    id="module"
                    name="module"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                >
                    <option value="">Semua modul</option>
                    @foreach ($modules as $module)
                        <option
                            value="{{ $module }}"
                            @selected(request('module') === $module)
                        >
                            {{ $module }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="action"
                       class="mb-1 block text-sm font-medium text-gray-700">
                    Aktivitas
                </label>
                <select
                    id="action"
                    name="action"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                >
                    <option value="">Semua aktivitas</option>
                    @foreach ($actions as $action)
                        <option
                            value="{{ $action }}"
                            @selected(request('action') === $action)
                        >
                            {{ ucfirst($action) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="start_date"
                       class="mb-1 block text-sm font-medium text-gray-700">
                    Dari tanggal
                </label>
                <input
                    id="start_date"
                    type="date"
                    name="start_date"
                    value="{{ request('start_date') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                >
            </div>

            <div>
                <label for="end_date"
                       class="mb-1 block text-sm font-medium text-gray-700">
                    Sampai tanggal
                </label>
                <input
                    id="end_date"
                    type="date"
                    name="end_date"
                    value="{{ request('end_date') }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                >
            </div>

        </div>

        <div class="mt-4 flex items-center gap-3">
            <a
                href="{{ route('superadmin.activities.index') }}"
                class="rounded-lg border border-gray-300 px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Reset Filter
            </a>

            <span id="filter-status"
                  class="text-xs text-gray-500"
                  aria-live="polite"></span>
        </div>
    </form>

    {{-- Tabel aktivitas --}}
    <div id="activity-results">
        @include('superadmin.activities.partials.results')
    </div>

</div>
@endsection

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('activity-filter');
    const results = document.getElementById('activity-results');
    const searchInput = document.getElementById('search');
    const filterStatus = document.getElementById('filter-status');

    if (!form || !results || !searchInput) return;

    let debounceTimer;
    let currentController = null;

    async function applyFilter(url = null) {
    if (currentController) {
        currentController.abort();
    }

    currentController = new AbortController();

    const params = new URLSearchParams(new FormData(form));
    params.delete('page');

    const queryString = params.toString();

    // Ambil URL dari atribut action form secara eksplisit.
    const baseUrl = form.getAttribute('action');
    const targetUrl = url || (
        baseUrl + (queryString ? '?' + queryString : '')
    );

    filterStatus.textContent = 'Memuat hasil...';

    try {
        const response = await fetch(targetUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            },
            signal: currentController.signal
        });

        if (!response.ok) {
            throw new Error('HTTP ' + response.status);
        }

        const html = await response.text();
        const parser = new DOMParser();
        const parsedDocument = parser.parseFromString(html, 'text/html');
        const newResults = parsedDocument.getElementById('activity-results');

        if (!newResults) {
            throw new Error('Elemen activity-results tidak ditemukan.');
        }

        results.innerHTML = newResults.innerHTML;
        window.history.replaceState({}, '', targetUrl);
        filterStatus.textContent = 'Hasil diperbarui.';
    } catch (error) {
        if (error.name === 'AbortError') return;

        filterStatus.textContent = 'Gagal memuat hasil. Coba lagi.';
        console.error('Activity Log:', error);
    }
}
    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);

        filterStatus.textContent = 'Menunggu ketikan...';

        debounceTimer = setTimeout(function () {
            applyFilter();
        }, 300);
    });

    form.querySelectorAll('select, input[type="date"]').forEach(function (control) {
        control.addEventListener('change', function () {
            clearTimeout(debounceTimer);
            applyFilter();
        });
    });

    // Pagination tetap bekerja tanpa memuat ulang seluruh halaman.
    results.addEventListener('click', function (event) {
        const link = event.target.closest('.pagination a, nav[role="navigation"] a');

        if (!link || !results.contains(link)) return;

        event.preventDefault();
        clearTimeout(debounceTimer);
        applyFilter(link.href);
    });
});
</script>