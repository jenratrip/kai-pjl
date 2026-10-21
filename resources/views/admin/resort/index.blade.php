@extends('layouts.admin')

@section('title', 'Manajemen Resort')

@section('content')

<div class="w-full space-y-6 p-4 sm:p-6">

    {{-- =====================================================
        HEADER
    ====================================================== --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">

        <div class="min-w-0">
            <h2 class="text-xl font-bold text-[#1d2d50] sm:text-2xl">
                Manajemen Resort
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Kelola data resort berdasarkan wilayah operasional PJL.
            </p>
        </div>

        <a
            href="{{ route('admin.resort.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0b1f3a] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50]"
        >
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="h-4 w-4"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M12 4v16m8-8H4"/>
            </svg>

            Tambah Resort
        </a>

    </div>


    {{-- =====================================================
        STATISTIK
    ====================================================== --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- TOTAL RESORT --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">
                Total Resort
            </p>

            <div class="mt-2 flex items-center justify-between">
                <p class="text-2xl font-bold text-[#1d2d50]">
                    {{ $totalResort }}
                </p>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M3 21h18M5 21V5l7-3 7 3v16M9 9h1m4 0h1m-6 4h1m4 0h1"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- RESORT AKTIF --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">
                Resort Aktif
            </p>

            <div class="mt-2 flex items-center justify-between">
                <p class="text-2xl font-bold text-green-600">
                    {{ $totalAktif }}
                </p>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- RESORT NONAKTIF --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">
                Resort Nonaktif
            </p>

            <div class="mt-2 flex items-center justify-between">
                <p class="text-2xl font-bold text-red-600">
                    {{ $totalResort - $totalAktif }}
                </p>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- TOTAL JPL --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">
                Total JPL
            </p>

            <div class="mt-2 flex items-center justify-between">
                <p class="text-2xl font-bold text-[#1d2d50]">
                    {{ $totalJpl }}
                </p>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-50 text-yellow-600">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

    </div>


    {{-- =====================================================
        FLASH MESSAGE
    ====================================================== --}}
    @if(session('success'))
        <div role="status"
             class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div role="alert"
             class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div role="alert"
             class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
            <p class="text-sm font-semibold text-red-800">
                Terdapat kesalahan:
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- =====================================================
        TABLE CARD
    ====================================================== --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- SEARCH DAN FILTER --}}
        <div class="border-b border-gray-200 p-4 sm:p-5">

            <form
                method="GET"
                action="{{ route('admin.resort.index') }}"
                class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1fr)_auto_auto_auto]"
            >

                {{-- SEARCH --}}
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="h-4 w-4 text-gray-400"
                             fill="none"
                             viewBox="0 0 24 24"
                             stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                        </svg>
                    </div>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nama resort atau wilayah..."
                        aria-label="Cari resort"
                        class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                    >
                </div>

                {{-- FILTER WILAYAH --}}
                <select
                    name="wilayah_id"
                    aria-label="Filter wilayah"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 outline-none focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                >
                    <option value="">Semua Wilayah</option>

                    @foreach($wilayahs as $wilayah)
                        <option
                            value="{{ $wilayah->id }}"
                            @selected((string) request('wilayah_id') === (string) $wilayah->id)
                        >
                            {{ $wilayah->nama_wilayah }}
                        </option>
                    @endforeach
                </select>

                {{-- FILTER STATUS --}}
                <select
                    name="status"
                    aria-label="Filter status"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 outline-none focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                >
                    <option value="">Semua Status</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>
                        Aktif
                    </option>
                    <option value="nonaktif" @selected(request('status') === 'nonaktif')>
                        Nonaktif
                    </option>
                </select>

                {{-- BUTTON --}}
                <div class="flex gap-2">
                    <button
                        type="submit"
                        class="flex-1 rounded-lg bg-[#0b1f3a] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50] lg:flex-none"
                    >
                        Cari
                    </button>

                    @if(
                        request()->filled('search') ||
                        request()->filled('wilayah_id') ||
                        request()->filled('status')
                    )
                        <a
                            href="{{ route('admin.resort.index') }}"
                            class="flex flex-1 items-center justify-center rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 lg:flex-none"
                        >
                            Reset
                        </a>
                    @endif
                </div>

            </form>
        </div>


        {{-- =================================================
            DESKTOP TABLE
        ================================================== --}}
        <div class="hidden overflow-x-auto md:block">

            <table class="w-full">

                <thead class="bg-[#0b1f3a] text-left text-xs font-semibold uppercase text-white">
                    <tr>
                        <th class="w-16 px-5 py-4 text-center">No</th>
                        <th class="px-5 py-4">Nama Resort</th>
                        <th class="px-5 py-4">Wilayah</th>
                        <th class="px-5 py-4 text-center">JPL</th>
                        <th class="px-5 py-4 text-center">Status</th>
                        <th class="px-5 py-4 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">

                    @forelse($resorts as $index => $resort)

                        <tr class="transition hover:bg-gray-50">

                            {{-- NOMOR --}}
                            <td class="px-5 py-4 text-center text-sm text-gray-500">
                                {{ $resorts->firstItem() + $index }}
                            </td>

                            {{-- NAMA RESORT --}}
                            <td class="px-5 py-4">
                                <p class="font-semibold text-[#1d2d50]">
                                    {{ $resort->nama_resort }}
                                </p>

                                @if($resort->keterangan)
                                    <p class="mt-1 max-w-xs truncate text-xs text-gray-500"
                                       title="{{ $resort->keterangan }}">
                                        {{ $resort->keterangan }}
                                    </p>
                                @endif
                            </td>

                            {{-- WILAYAH --}}
                            <td class="px-5 py-4 text-sm text-gray-600">
                                {{ $resort->wilayah?->nama_wilayah ?? '—' }}
                            </td>

                            {{-- JUMLAH JPL --}}
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                    {{ $resort->jpl_count }} JPL
                                </span>
                            </td>

                            {{-- STATUS --}}
                            <td class="px-5 py-4 text-center">

                                @if($resort->is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                        Nonaktif
                                    </span>
                                @endif

                            </td>

                            {{-- AKSI --}}
                            <td class="px-5 py-4">

                                <div class="flex justify-center gap-2">

                                    {{-- DETAIL --}}
                                    <a
                                        href="{{ route('admin.resort.show', $resort) }}"
                                        title="Detail Resort"
                                        aria-label="Detail Resort {{ $resort->nama_resort }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                             class="h-4 w-4"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                             stroke="currentColor"
                                             stroke-width="2">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    {{-- EDIT --}}
                                    <a
                                        href="{{ route('admin.resort.edit', $resort) }}"
                                        title="Edit Resort"
                                        aria-label="Edit Resort {{ $resort->nama_resort }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                             class="h-4 w-4"
                                             fill="none"
                                             viewBox="0 0 24 24"
                                             stroke="currentColor"
                                             stroke-width="2">
                                            <path stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.805.805-2.685a4.5 4.5 0 0 1 1.13-1.897L16.862 4.487Z"/>
                                        </svg>
                                    </a>

                                    {{-- STATUS --}}
                                    <form
                                        method="POST"
                                        action="{{ route('admin.resort.toggle-status', $resort) }}"
                                        onsubmit="return confirm('Yakin ingin {{ $resort->is_active ? 'menonaktifkan' : 'mengaktifkan' }} resort ini?')"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            title="{{ $resort->is_active ? 'Nonaktifkan Resort' : 'Aktifkan Resort' }}"
                                            aria-label="{{ $resort->is_active ? 'Nonaktifkan' : 'Aktifkan' }} Resort {{ $resort->nama_resort }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:border-yellow-200 hover:bg-yellow-50 hover:text-yellow-600"
                                        >
                                            @if($resort->is_active)
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                     class="h-4 w-4"
                                                     fill="none"
                                                     viewBox="0 0 24 24"
                                                     stroke="currentColor"
                                                     stroke-width="2">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          d="M15 12H9m12 0a9 9 0 1 1-18 0c0-4.97 4.03-9 9-9s9 4.03 9 9Z"/>
                                                </svg>
                                            @else
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                     class="h-4 w-4"
                                                     fill="none"
                                                     viewBox="0 0 24 24"
                                                     stroke="currentColor"
                                                     stroke-width="2">
                                                    <path stroke-linecap="round"
                                                          stroke-linejoin="round"
                                                          d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-14 text-center">
                                <p class="font-semibold text-gray-700">
                                    {{ request()->filled('search') || request()->filled('wilayah_id') || request()->filled('status')
                                        ? 'Tidak ada resort yang sesuai'
                                        : 'Belum ada data resort' }}
                                </p>

                                <p class="mt-1 text-sm text-gray-500">
                                    {{ request()->filled('search') || request()->filled('wilayah_id') || request()->filled('status')
                                        ? 'Coba ubah pencarian atau filter yang digunakan.'
                                        : 'Tambahkan resort untuk mulai mengelola data.' }}
                                </p>
                            </td>
                        </tr>
                    @endforelse

                </tbody>

            </table>
        </div>


        {{-- =================================================
            MOBILE CARDS
        ================================================== --}}
        <div class="space-y-3 p-4 md:hidden">

            @forelse($resorts as $index => $resort)

                <div class="rounded-xl border border-gray-200 bg-white p-4">

                    <div class="flex items-start justify-between gap-3">

                        <div class="min-w-0">
                            <p class="text-xs text-gray-400">
                                #{{ $resorts->firstItem() + $index }}
                            </p>

                            <h3 class="mt-1 break-words font-semibold text-[#1d2d50]">
                                {{ $resort->nama_resort }}
                            </h3>
                        </div>

                        @if($resort->is_active)
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                Nonaktif
                            </span>
                        @endif

                    </div>

                    <div class="mt-4 space-y-3">

                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <span class="text-sm text-gray-500">Wilayah</span>
                            <span class="text-right text-sm font-semibold text-[#1d2d50]">
                                {{ $resort->wilayah?->nama_wilayah ?? '—' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                            <span class="text-sm text-gray-500">Jumlah JPL</span>
                            <span class="font-semibold text-[#1d2d50]">
                                {{ $resort->jpl_count }}
                            </span>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Keterangan</p>
                            <p class="mt-1 break-words text-sm text-gray-700">
                                {{ $resort->keterangan ?: '—' }}
                            </p>
                        </div>

                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2 border-t border-gray-100 pt-4">

                        <a
                            href="{{ route('admin.resort.show', $resort) }}"
                            class="flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                        >
                            Detail
                        </a>

                        <a
                            href="{{ route('admin.resort.edit', $resort) }}"
                            class="flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                        >
                            Edit
                        </a>

                        <form
                            method="POST"
                            action="{{ route('admin.resort.toggle-status', $resort) }}"
                            onsubmit="return confirm('Yakin ingin {{ $resort->is_active ? 'menonaktifkan' : 'mengaktifkan' }} resort ini?')"
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="w-full rounded-lg bg-[#0b1f3a] px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50]"
                            >
                                Status
                            </button>
                        </form>

                    </div>

                </div>

            @empty

                <div class="py-12 text-center">
                    <p class="font-semibold text-gray-700">
                        {{ request()->filled('search') || request()->filled('wilayah_id') || request()->filled('status')
                            ? 'Tidak ada resort yang sesuai'
                            : 'Belum ada data resort' }}
                    </p>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ request()->filled('search') || request()->filled('wilayah_id') || request()->filled('status')
                            ? 'Coba ubah pencarian atau filter yang digunakan.'
                            : 'Tambahkan resort untuk mulai mengelola data.' }}
                    </p>

                    @if(!request()->filled('search') && !request()->filled('wilayah_id') && !request()->filled('status'))
                        <a
                            href="{{ route('admin.resort.create') }}"
                            class="mt-4 inline-flex rounded-lg bg-[#0b1f3a] px-4 py-2.5 text-sm font-semibold text-white"
                        >
                            Tambah Resort
                        </a>
                    @endif
                </div>

            @endforelse

        </div>


        {{-- =================================================
            PAGINATION
        ================================================== --}}
        @if($resorts->total() > 0)

            <div class="border-t border-gray-200 px-4 py-4 sm:px-5">

                <div class="flex flex-col gap-4">

                    <p class="text-center text-sm text-gray-500 sm:text-left">
                        Menampilkan
                        <span class="font-medium text-gray-700">
                            {{ $resorts->firstItem() ?? 0 }}
                        </span>
                        -
                        <span class="font-medium text-gray-700">
                            {{ $resorts->lastItem() ?? 0 }}
                        </span>
                        dari
                        <span class="font-medium text-gray-700">
                            {{ $resorts->total() }}
                        </span>
                        resort
                    </p>

                    @if($resorts->hasPages())
                        <div class="overflow-x-auto">
                            <div class="flex justify-center sm:justify-end">
                                {{ $resorts->withQueryString()->links() }}
                            </div>
                        </div>
                    @endif

                </div>

            </div>

        @endif

    </div>

</div>

@endsection
