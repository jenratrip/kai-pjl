@extends('layouts.admin')

@section('title', 'Preview Import Wilayah')

@section('content')

<div class="px-4 py-6 sm:px-6 lg:px-8">

    {{-- ============================================================
         HEADER
    ============================================================= --}}

    <div class="mb-6">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-[#1d2d50]">
                    Preview Import Wilayah
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Periksa data Excel sebelum disimpan ke database.
                </p>
            </div>

            <a
                href="{{ route('admin.wilayah.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                    />
                </svg>

                Kembali
            </a>

        </div>

    </div>


    {{-- ============================================================
         FLASH ERROR
    ============================================================= --}}

    @if(session('error'))

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

            <div class="flex items-start gap-3">

                <svg
                    class="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.36h15.6a2 2 0 001.73-3L13.71 3.86a2 2 0 00-3.42 0z"
                    />
                </svg>

                <div>
                    <p class="font-semibold text-red-800">
                        Import tidak dapat dilanjutkan
                    </p>

                    <p class="mt-1 text-sm text-red-700">
                        {{ session('error') }}
                    </p>
                </div>

            </div>

        </div>

    @endif


    {{-- ============================================================
         AMBIL DATA SESSION
    ============================================================= --}}

    @php

        $import = session('wilayah_import', []);

        $rows = $import['rows'] ?? [];

        $validRows = collect($rows)
            ->where('status', 'valid')
            ->values();

        $errorRows = collect($rows)
            ->where('status', 'error')
            ->values();

        $totalRows = $validRows->count() + $errorRows->count();

    @endphp


    {{-- ============================================================
         SUMMARY CARDS
    ============================================================= --}}

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">

        {{-- Total --}}

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <p class="text-sm font-medium text-gray-500">
                Total Data
            </p>

            <p class="mt-2 text-2xl font-bold text-[#1d2d50]">
                {{ $totalRows }}
            </p>

            <p class="mt-1 text-xs text-gray-500">
                Data dari file Excel
            </p>

        </div>


        {{-- Valid --}}

        <div class="rounded-xl border border-green-200 bg-green-50 p-5">

            <p class="text-sm font-medium text-green-700">
                Data Valid
            </p>

            <p class="mt-2 text-2xl font-bold text-green-700">
                {{ $validRows->count() }}
            </p>

            <p class="mt-1 text-xs text-green-600">
                Siap disimpan
            </p>

        </div>


        {{-- Error --}}

        <div class="rounded-xl border border-red-200 bg-red-50 p-5">

            <p class="text-sm font-medium text-red-700">
                Data Bermasalah
            </p>

            <p class="mt-2 text-2xl font-bold text-red-700">
                {{ $errorRows->count() }}
            </p>

            <p class="mt-1 text-xs text-red-600">
                Perlu diperbaiki
            </p>

        </div>

    </div>


    {{-- ============================================================
         INFORMATION
    ============================================================= --}}

    @if($errorRows->count() > 0)

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

            <div class="flex items-start gap-3">

                <svg
                    class="mt-0.5 h-5 w-5 shrink-0 text-red-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.36h15.6a2 2 0 001.73-3L13.71 3.86a2 2 0 00-3.42 0z"
                    />
                </svg>

                <div>

                    <p class="font-semibold text-red-800">
                        Terdapat data yang tidak valid
                    </p>

                    <p class="mt-1 text-sm text-red-700">
                        Perbaiki data pada file Excel kemudian upload kembali.
                        Data tidak dapat disimpan selama masih terdapat error.
                    </p>

                </div>

            </div>

        </div>

    @else

        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4">

            <div class="flex items-start gap-3">

                <svg
                    class="mt-0.5 h-5 w-5 shrink-0 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                    />
                </svg>

                <div>

                    <p class="font-semibold text-green-800">
                        Semua data valid
                    </p>

                    <p class="mt-1 text-sm text-green-700">
                        Data dapat disimpan ke database.
                    </p>

                </div>

            </div>

        </div>

    @endif


    {{-- ============================================================
         TABLE
    ============================================================= --}}

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- Table Header --}}

        <div class="border-b border-gray-200 px-5 py-4">

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <h2 class="font-semibold text-[#1d2d50]">
                        Data Import
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        Berikut hasil pembacaan dan validasi file Excel.
                    </p>

                </div>

                <div class="text-xs text-gray-500">
                    {{ $totalRows }} data
                </div>

            </div>

        </div>


        {{-- Desktop Table --}}

        <div class="hidden overflow-x-auto md:block">

            <table class="min-w-full text-sm">

                <thead class="bg-gray-50">

                    <tr>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Baris
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Nama Wilayah
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Keterangan
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Status Aktif
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Validasi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100">

                    @forelse($rows as $row)

                        <tr class="hover:bg-gray-50">

                            {{-- Baris --}}

                            <td class="px-5 py-4 font-medium text-gray-600">
                                {{ $row['row'] }}
                            </td>


                            {{-- Nama --}}

                            <td class="px-5 py-4">

                                <div class="font-semibold text-[#1d2d50]">
                                    {{ $row['nama_wilayah'] ?: '-' }}
                                </div>

                            </td>


                            {{-- Keterangan --}}

                            <td class="max-w-md px-5 py-4 text-gray-600">

                                {{ $row['keterangan'] ?: '-' }}

                            </td>


                            {{-- Status --}}

                            <td class="px-5 py-4 text-center">

                                @if((string) $row['is_active'] === '1')

                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        Aktif
                                    </span>

                                @elseif((string) $row['is_active'] === '0')

                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                        Nonaktif
                                    </span>

                                @else

                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                        Tidak valid
                                    </span>

                                @endif

                            </td>


                            {{-- Validation --}}

                            <td class="px-5 py-4">

                                @if($row['status'] === 'valid')

                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">

                                        <svg
                                            class="h-3.5 w-3.5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>

                                        Valid

                                    </span>

                                @else

                                    <div class="space-y-1">

                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">

                                            <svg
                                                class="h-3.5 w-3.5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>

                                            Error

                                        </span>

                                        @foreach($row['errors'] ?? [] as $error)

                                            <p class="text-xs text-red-600">
                                                {{ $error }}
                                            </p>

                                        @endforeach

                                    </div>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="px-5 py-12 text-center"
                            >

                                <div class="text-gray-500">

                                    <p class="font-medium">
                                        Tidak ada data
                                    </p>

                                    <p class="mt-1 text-sm">
                                        File Excel tidak memiliki data yang dapat ditampilkan.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- ========================================================
             MOBILE CARDS
        ========================================================= --}}

        <div class="divide-y divide-gray-100 md:hidden">

            @forelse($rows as $row)

                <div class="p-4">

                    <div class="flex items-start justify-between gap-3">

                        <div>

                            <p class="text-xs font-medium text-gray-400">
                                Baris Excel {{ $row['row'] }}
                            </p>

                            <h3 class="mt-1 font-semibold text-[#1d2d50]">
                                {{ $row['nama_wilayah'] ?: '-' }}
                            </h3>

                        </div>


                        @if($row['status'] === 'valid')

                            <span class="shrink-0 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                Valid
                            </span>

                        @else

                            <span class="shrink-0 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                Error
                            </span>

                        @endif

                    </div>


                    <div class="mt-4 space-y-3">

                        <div>

                            <p class="text-xs font-medium text-gray-400">
                                Keterangan
                            </p>

                            <p class="mt-1 text-sm text-gray-600">
                                {{ $row['keterangan'] ?: '-' }}
                            </p>

                        </div>


                        <div>

                            <p class="text-xs font-medium text-gray-400">
                                Status
                            </p>

                            <div class="mt-1">

                                @if((string) $row['is_active'] === '1')

                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        Aktif
                                    </span>

                                @elseif((string) $row['is_active'] === '0')

                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                        Nonaktif
                                    </span>

                                @else

                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                        Tidak valid
                                    </span>

                                @endif

                            </div>

                        </div>


                        @if($row['status'] === 'error')

                            <div>

                                <p class="text-xs font-medium text-gray-400">
                                    Kesalahan
                                </p>

                                <div class="mt-1 space-y-1">

                                    @foreach($row['errors'] ?? [] as $error)

                                        <p class="text-sm text-red-600">
                                            {{ $error }}
                                        </p>

                                    @endforeach

                                </div>

                            </div>

                        @endif

                    </div>

                </div>

            @empty

                <div class="px-4 py-12 text-center">

                    <p class="font-medium text-gray-500">
                        Tidak ada data
                    </p>

                    <p class="mt-1 text-sm text-gray-400">
                        File Excel tidak memiliki data.
                    </p>

                </div>

            @endforelse

        </div>


        {{-- ========================================================
             FOOTER ACTION
        ========================================================= --}}

        <div class="border-t border-gray-200 bg-gray-50 px-5 py-4">

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">

                <a
                    href="{{ route('admin.wilayah.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Batalkan
                </a>


                @if($totalRows > 0 && $errorRows->count() === 0)

                    <form
                        method="POST"
                        action="{{ route('admin.wilayah.import.confirm') }}"
                    >

                        @csrf

                        <button
                            type="submit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#f5a400] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#df9500] sm:w-auto"
                        >

                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>

                            Konfirmasi & Simpan

                        </button>

                    </form>

                @else

                    <button
                        type="button"
                        disabled
                        class="inline-flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-gray-300 px-5 py-2.5 text-sm font-bold text-gray-500 sm:w-auto"
                    >

                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20.36h15.6a2 2 0 001.73-3L13.71 3.86a2 2 0 00-3.42 0z"
                            />
                        </svg>

                        Tidak Dapat Disimpan

                    </button>

                @endif

            </div>

        </div>

    </div>

</div>

@endsection