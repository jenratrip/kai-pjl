@extends('layouts.admin')

@section('title', 'Master Data JPL')

@section('content')
<div
    class="p-6"
    x-data="{
        showCreateModal: {{
            ($errors->any() && old('_form_type') === 'create_jpl')
            || session('open_create_modal')
                ? 'true'
                : 'false'
        }}
    }"
    @keydown.escape.window="showCreateModal = false"
>
    {{-- HEADER --}}
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Master Data JPL
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Kelola data lokasi dan karakteristik operasional JPL.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                @click="showCreateModal = true"
                class="inline-flex items-center gap-2 rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#069b66]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                    fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 4v16m8-8H4" />
                </svg>
                Tambah JPL
            </button>

            <a
                href="{{ route('admin.jpl.import') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16233f]"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4"
                    fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5" />
                </svg>
                Import Excel
            </a>
        </div>
    </div>

    {{-- NOTIFICATIONS --}}
    @if (session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any() && old('_form_type') !== 'create_jpl')
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-semibold">Data belum dapat diproses.</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- STATISTICS --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Total JPL</p>
            <p class="mt-1 text-2xl font-bold text-[#1d2d50]">
                {{ $totalJpl }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">JPL Aktif</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600">
                {{ $totalJplAktif }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">JPL Nonaktif</p>
            <p class="mt-1 text-2xl font-bold text-red-500">
                {{ $totalJplNonaktif }}
            </p>
        </div>
    </div>

    {{-- MAIN CARD --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- FILTER --}}
        <div class="border-b border-gray-200 p-5">
            <form
                id="jpl-filter-form"
                method="GET"
                action="{{ route('admin.jpl.index') }}"
            >
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">

                    {{-- SEARCH --}}
                    <div>
                        <label for="search"
                            class="mb-1.5 block text-xs font-semibold text-gray-600">
                            Cari Data JPL
                        </label>

                        <input
                            id="search"
                            type="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Kode, nama, KM/HM, lokasi..."
                            autocomplete="off"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                        >
                    </div>

                    {{-- RESORT --}}
                    <div>
                        <label for="resort_id"
                            class="mb-1.5 block text-xs font-semibold text-gray-600">
                            Resort
                        </label>

                        <select
                            id="resort_id"
                            name="resort_id"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                        >
                            <option value="">Semua Resort</option>

                            @foreach ($resorts as $resort)
                                <option
                                    value="{{ $resort->id }}"
                                    @selected((string) request('resort_id') === (string) $resort->id)
                                >
                                    {{ $resort->nama_resort }}
                                    @if ($resort->wilayah)
                                        — {{ $resort->wilayah->nama_wilayah }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- STATUS --}}
                    <div>
                        <label for="status"
                            class="mb-1.5 block text-xs font-semibold text-gray-600">
                            Status JPL
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                        >
                            <option value="">Semua Status</option>
                            <option value="1" @selected(request('status') === '1')>
                                Aktif
                            </option>
                            <option value="0" @selected(request('status') === '0')>
                                Nonaktif
                            </option>
                        </select>
                    </div>

                    {{-- ACTIONS --}}
                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            id="jpl-search-button"
                            class="rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#16233f]"
                        >
                            Cari
                        </button>

                        <button
                            type="button"
                            id="reset-jpl-filter"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                        >
                            Reset
                        </button>

                        <span
                            id="jpl-search-status"
                            class="pb-2.5 text-xs text-gray-400"
                            aria-live="polite"
                        ></span>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE TITLE --}}
        <div class="flex flex-col justify-between gap-2 px-5 py-4 md:flex-row md:items-center">
            <div>
                <h2 class="text-base font-bold text-[#1d2d50]">
                    Daftar JPL
                </h2>
                <p class="mt-0.5 text-xs text-gray-500">
                    Informasi lokasi dan karakteristik jalur perlintasan.
                </p>
            </div>

            <div class="text-xs text-gray-500">
                @if ($jpls->total() > 0)
                    Menampilkan {{ $jpls->firstItem() }}–{{ $jpls->lastItem() }}
                    dari {{ $jpls->total() }} data
                @else
                    Tidak ada data yang cocok
                @endif
            </div>
        </div>

        {{-- TABLE --}}
        <div class="overflow-x-auto">
            <table class="min-w-[1800px] w-full">
                <thead>
                    <tr class="border-y border-gray-200 bg-[#f7f8fa]">
                        @foreach ([
                            'No',
                            'No JPL',
                            'KM / HM',
                            'Antara',
                            'Status Penjagaan',
                            'Jenis Jalur KA',
                            'Konst. Jalan',
                            'Titik Koordinat',
                            'Lebar Jln (m)',
                            'Resort',
                            'Nama Jln / Daerah',
                            'Kota / Kabupaten',
                            'Status',
                            'Aksi',
                        ] as $heading)
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">
                                {{ $heading }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($jpls as $index => $jpl)
                        <tr class="transition hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500">
                                {{ $jpls->firstItem() + $index }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-sm font-bold {{ $jpl->kode_jpl === 'Liar' ? 'text-amber-600' : 'text-[#1d2d50]' }}">
                                {{ $jpl->kode_jpl ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $jpl->km_hm ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $jpl->antara ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $jpl->status_penjagaan ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $jpl->jenis_jalur_ka ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $jpl->konst_jalan ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $jpl->titik_koordinat ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $jpl->lebar_jln !== null ? $jpl->lebar_jln : '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $jpl->resort?->nama_resort ?? '-' }}
                            </td>

                            <td class="px-4 py-4 text-sm text-gray-700">
                                <div class="min-w-[180px] font-medium">
                                    {{ $jpl->lokasi ?: '-' }}
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600">
                                {{ $jpl->kota_kabupaten ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-4">
                                @if ($jpl->is_active)
                                    <span class="inline-flex rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            {{-- ACTIONS --}}
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center gap-2">

                                    <a
                                        href="{{ route('admin.jpl.show', $jpl->id) }}"
                                        title="Detail JPL"
                                        aria-label="Detail JPL"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-200 p-2 text-[#1d2d50] transition hover:bg-blue-50"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 12s3.5-7 9.75-7 9.75 7 9.75 7-3.5 7-9.75 7-9.75-7-9.75-7Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </a>

                                    <a
                                        href="{{ route('admin.jpl.edit', $jpl->id) }}"
                                        title="Edit JPL"
                                        aria-label="Edit JPL"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-200 p-2 text-amber-600 transition hover:bg-amber-50"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="m16.862 4.487 2.651 2.651M5 19l4.5-1 10-10a1.875 1.875 0 0 0-2.65-2.65l-10 10L5 19Z" />
                                        </svg>
                                    </a>

                                    @if ($jpl->is_active)
                                        {{-- NONAKTIFKAN --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.jpl.destroy', $jpl->id) }}"
                                            onsubmit="return confirm('Yakin ingin menonaktifkan JPL ini?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                title="Nonaktifkan JPL"
                                                aria-label="Nonaktifkan JPL"
                                                class="inline-flex items-center justify-center rounded-lg border border-gray-200 p-2 text-red-600 transition hover:bg-red-50"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18 18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </form>
                                    @else
                                        {{-- AKTIFKAN KEMBALI --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.jpl.activate', $jpl->id) }}"
                                            onsubmit="return confirm('Yakin ingin mengaktifkan kembali JPL ini?')"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                title="Aktifkan Kembali"
                                                aria-label="Aktifkan Kembali"
                                                class="inline-flex items-center justify-center rounded-lg border border-gray-200 p-2 text-emerald-600 transition hover:bg-emerald-50"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="m5 12 4 4L19 6" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="px-5 py-16 text-center">
                                <p class="text-sm font-semibold text-gray-700">
                                    Tidak ada data JPL yang cocok
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    Coba kata kunci lain atau reset filter.
                                </p>

                                <button
                                    type="button"
                                    @click="showCreateModal = true"
                                    class="mt-4 rounded-lg bg-[#1d2d50] px-4 py-2 text-sm font-semibold text-white hover:bg-[#16233f]"
                                >
                                    + Tambah JPL
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if ($jpls->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $jpls->links() }}
            </div>
        @endif
    </div>

    {{-- MODAL TAMBAH JPL --}}
    <div
        x-cloak
        x-show="showCreateModal"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto p-4"
        style="display: none;"
        @click.self="showCreateModal = false"
    >
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

        <div
            x-show="showCreateModal"
            x-transition
            class="relative my-auto w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl"
        >
            <div class="flex items-center justify-between bg-[#0b1f3a] px-6 py-5">
                <div>
                    <h2 class="text-lg font-bold text-white">
                        Tambah JPL Manual
                    </h2>
                    <p class="mt-1 text-sm text-blue-200">
                        Lengkapi informasi lokasi dan karakteristik JPL.
                    </p>
                </div>

                <button
                    type="button"
                    @click="showCreateModal = false"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/10 hover:text-white"
                    aria-label="Tutup modal"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                        fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('admin.jpl.store') }}">
                @csrf
                <input type="hidden" name="_form_type" value="create_jpl">

                <div class="max-h-[70vh] space-y-5 overflow-y-auto p-6">

                    {{-- KODE DAN NAMA JPL --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="modal_kode_jpl"
                                class="mb-2 block text-sm font-semibold text-gray-700">
                                No. JPL / Kode JPL
                            </label>

                            <input
                                id="modal_kode_jpl"
                                type="text"
                                name="kode_jpl"
                                value="{{ old('_form_type') === 'create_jpl' ? old('kode_jpl') : '' }}"
                                placeholder="Contoh: 636 atau Liar"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                            @if (old('_form_type') === 'create_jpl')
                                @error('kode_jpl')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label for="modal_nama_jpl"
                                class="mb-2 block text-sm font-semibold text-gray-700">
                                Nama JPL <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="modal_nama_jpl"
                                type="text"
                                name="nama_jpl"
                                required
                                value="{{ old('_form_type') === 'create_jpl' ? old('nama_jpl') : '' }}"
                                placeholder="Contoh: Plandi"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                            @if (old('_form_type') === 'create_jpl')
                                @error('nama_jpl')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    {{-- RESORT --}}
                    <div>
                        <label for="modal_resort_id"
                            class="mb-2 block text-sm font-semibold text-gray-700">
                            Resort <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="modal_resort_id"
                            name="resort_id"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                        >
                            <option value="">Pilih Resort</option>

                            @foreach ($resorts as $resort)
                                <option
                                    value="{{ $resort->id }}"
                                    @selected(old('_form_type') === 'create_jpl' && (string) old('resort_id') === (string) $resort->id)
                                >
                                    {{ $resort->nama_resort }}
                                    @if ($resort->wilayah)
                                        — {{ $resort->wilayah->nama_wilayah }}
                                    @endif
                                </option>
                            @endforeach
                        </select>

                        @if (old('_form_type') === 'create_jpl')
                            @error('resort_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    {{-- KM/HM DAN ANTARA --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="modal_km_hm"
                                class="mb-2 block text-sm font-semibold text-gray-700">
                                KM / HM
                            </label>

                            <input
                                id="modal_km_hm"
                                type="text"
                                name="km_hm"
                                value="{{ old('_form_type') === 'create_jpl' ? old('km_hm') : '' }}"
                                placeholder="Contoh: 491+140"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                            @if (old('_form_type') === 'create_jpl')
                                @error('km_hm')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label for="modal_antara"
                                class="mb-2 block text-sm font-semibold text-gray-700">
                                Antara
                            </label>

                            <input
                                id="modal_antara"
                                type="text"
                                name="antara"
                                value="{{ old('_form_type') === 'create_jpl' ? old('antara') : '' }}"
                                placeholder="Contoh: Kta-Jn"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                            @if (old('_form_type') === 'create_jpl')
                                @error('antara')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    {{-- STATUS PENJAGAAN DAN JALUR --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="modal_status_penjagaan"
                                class="mb-2 block text-sm font-semibold text-gray-700">
                                Status Penjagaan
                            </label>

                            <input
                                id="modal_status_penjagaan"
                                type="text"
                                name="status_penjagaan"
                                value="{{ old('_form_type') === 'create_jpl' ? old('status_penjagaan') : '' }}"
                                placeholder="Contoh: Tidak Dijaga"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                            @if (old('_form_type') === 'create_jpl')
                                @error('status_penjagaan')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label for="modal_jenis_jalur_ka"
                                class="mb-2 block text-sm font-semibold text-gray-700">
                                Jenis Jalur KA
                            </label>

                            <input
                                id="modal_jenis_jalur_ka"
                                type="text"
                                name="jenis_jalur_ka"
                                value="{{ old('_form_type') === 'create_jpl' ? old('jenis_jalur_ka') : '' }}"
                                placeholder="Contoh: Double Track"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                            @if (old('_form_type') === 'create_jpl')
                                @error('jenis_jalur_ka')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    {{-- KONSTRUKSI DAN LEBAR JALAN --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="modal_konst_jalan"
                                class="mb-2 block text-sm font-semibold text-gray-700">
                                Konstruksi Jalan
                            </label>

                            <input
                                id="modal_konst_jalan"
                                type="text"
                                name="konst_jalan"
                                value="{{ old('_form_type') === 'create_jpl' ? old('konst_jalan') : '' }}"
                                placeholder="Masukkan konstruksi jalan"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                            @if (old('_form_type') === 'create_jpl')
                                @error('konst_jalan')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label for="modal_lebar_jln"
                                class="mb-2 block text-sm font-semibold text-gray-700">
                                Lebar Jalan (m)
                            </label>

                            <input
                                id="modal_lebar_jln"
                                type="number"
                                name="lebar_jln"
                                min="0"
                                step="0.01"
                                value="{{ old('_form_type') === 'create_jpl' ? old('lebar_jln') : '' }}"
                                placeholder="Contoh: 4.50"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                            @if (old('_form_type') === 'create_jpl')
                                @error('lebar_jln')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    {{-- LOKASI --}}
                    <div>
                        <label for="modal_lokasi"
                            class="mb-2 block text-sm font-semibold text-gray-700">
                            Nama Jalan / Daerah
                        </label>

                        <textarea
                            id="modal_lokasi"
                            name="lokasi"
                            rows="2"
                            placeholder="Masukkan nama jalan atau daerah"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                        >{{ old('_form_type') === 'create_jpl' ? old('lokasi') : '' }}</textarea>

                        @if (old('_form_type') === 'create_jpl')
                            @error('lokasi')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    {{-- KOTA DAN PROVINSI --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="modal_kota_kabupaten"
                                class="mb-2 block text-sm font-semibold text-gray-700">
                                Kota / Kabupaten
                            </label>

                            <input
                                id="modal_kota_kabupaten"
                                type="text"
                                name="kota_kabupaten"
                                value="{{ old('_form_type') === 'create_jpl' ? old('kota_kabupaten') : '' }}"
                                placeholder="Contoh: Purworejo"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                            @if (old('_form_type') === 'create_jpl')
                                @error('kota_kabupaten')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>

                        <div>
                            <label for="modal_provinsi"
                                class="mb-2 block text-sm font-semibold text-gray-700">
                                Provinsi
                            </label>

                            <input
                                id="modal_provinsi"
                                type="text"
                                name="provinsi"
                                value="{{ old('_form_type') === 'create_jpl' ? old('provinsi') : '' }}"
                                placeholder="Contoh: Jawa Tengah"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                            @if (old('_form_type') === 'create_jpl')
                                @error('provinsi')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>

                    {{-- KOORDINAT --}}
                    <div>
                        <label for="modal_titik_koordinat"
                            class="mb-2 block text-sm font-semibold text-gray-700">
                            Titik Koordinat
                        </label>

                        <input
                            id="modal_titik_koordinat"
                            type="text"
                            name="titik_koordinat"
                            value="{{ old('_form_type') === 'create_jpl' ? old('titik_koordinat') : '' }}"
                            placeholder="Contoh: 7°47'36.0&quot;S 109°59'33.5&quot;E"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                        >

                        @if (old('_form_type') === 'create_jpl')
                            @error('titik_koordinat')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    {{-- KETERANGAN --}}
                    <div>
                        <label for="modal_keterangan"
                            class="mb-2 block text-sm font-semibold text-gray-700">
                            Keterangan
                        </label>

                        <textarea
                            id="modal_keterangan"
                            name="keterangan"
                            rows="3"
                            placeholder="Keterangan tambahan (opsional)"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                        >{{ old('_form_type') === 'create_jpl' ? old('keterangan') : '' }}</textarea>

                        @if (old('_form_type') === 'create_jpl')
                            @error('keterangan')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    {{-- STATUS JPL --}}
                    <div>
                        <label for="modal_is_active"
                            class="mb-2 block text-sm font-semibold text-gray-700">
                            Status JPL
                        </label>

                        <select
                            id="modal_is_active"
                            name="is_active"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-[#1d2d50] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/10"
                        >
                            <option
                                value="1"
                                @selected(old('_form_type') !== 'create_jpl' || old('is_active', '1') == '1')
                            >
                                Aktif
                            </option>

                            <option
                                value="0"
                                @selected(old('_form_type') === 'create_jpl' && old('is_active') === '0')
                            >
                                Nonaktif
                            </option>
                        </select>

                        @if (old('_form_type') === 'create_jpl')
                            @error('is_active')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>

                {{-- MODAL FOOTER --}}
                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#16233f]"
                    >
                        Simpan JPL
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- SCRIPT PENCARIAN OTOMATIS: JEDA 5 DETIK --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('jpl-filter-form');
    const search = document.getElementById('search');
    const resort = document.getElementById('resort_id');
    const status = document.getElementById('status');
    const reset = document.getElementById('reset-jpl-filter');
    const indicator = document.getElementById('jpl-search-status');

    if (!form || !search || !resort || !status) {
        return;
    }

    let timer = null;

    // Pencarian menunggu 5 detik setelah pengguna berhenti mengetik.
    function scheduleSearch() {
        clearTimeout(timer);

        if (indicator) {
            indicator.textContent = 'Menunggu input...';
        }

        timer = setTimeout(function () {
            if (indicator) {
                indicator.textContent = 'Memuat hasil...';
            }

            form.requestSubmit();
        }, 3000);
    }

    // Ketik bebas; pencarian otomatis dijalankan setelah 3detik.
    search.addEventListener('input', scheduleSearch);

    // Tekan Enter untuk mencari langsung tanpa menunggu 3detik.
    search.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            clearTimeout(timer);

            if (indicator) {
                indicator.textContent = 'Memuat hasil...';
            }

            form.requestSubmit();
        }
    });

    // Perubahan filter langsung menjalankan pencarian.
    resort.addEventListener('change', function () {
        clearTimeout(timer);

        if (indicator) {
            indicator.textContent = 'Memuat hasil...';
        }

        form.requestSubmit();
    });

    status.addEventListener('change', function () {
        clearTimeout(timer);

        if (indicator) {
            indicator.textContent = 'Memuat hasil...';
        }

        form.requestSubmit();
    });

    // Reset seluruh filter.
    if (reset) {
        reset.addEventListener('click', function () {
            clearTimeout(timer);

            search.value = '';
            resort.value = '';
            status.value = '';

            if (indicator) {
                indicator.textContent = 'Memuat ulang data...';
            }

            form.requestSubmit();
        });
    }

    // Jika pengguna menekan tombol Cari, batalkan timer otomatis.
    form.addEventListener('submit', function () {
        clearTimeout(timer);
    });
});
</script>
@endsection
