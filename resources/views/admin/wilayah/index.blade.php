@extends('layouts.admin')

@section('title', 'Manajemen Wilayah')

@section('content')

<div
    x-data="wilayahPage()"
    class="w-full space-y-6 p-4 sm:p-6"
>

    {{-- =========================================================
        HEADER
    ========================================================== --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">

        <div class="min-w-0">

            <h2 class="text-xl font-bold text-[#1d2d50] sm:text-2xl">
                Manajemen Wilayah
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Kelola data wilayah operasional PJL.
            </p>

        </div>


        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">

            {{-- IMPORT --}}
            <button
                type="button"
                @click="openImportModal()"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14"
                    />
                </svg>

                Import Excel

            </button>


            {{-- TAMBAH --}}
            <button
                type="button"
                @click="openCreateModal()"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0b1f3a] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50]"
            >

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 4v16m8-8H4"
                    />
                </svg>

                Tambah Wilayah

            </button>

        </div>

    </div>


    {{-- =========================================================
        STATISTICS
    ========================================================== --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- TOTAL WILAYAH --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <p class="text-sm font-medium text-gray-500">
                Total Wilayah
            </p>

            <div class="mt-2 flex items-center justify-between">

                <p class="text-2xl font-bold text-[#1d2d50]">
                    {{ $totalWilayah }}
                </p>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 21s8-4.5 8-10a8 8 0 10-16 0c0 5.5 8 10 8 10z"
                        />

                        <circle
                            cx="12"
                            cy="11"
                            r="2.5"
                        />

                    </svg>

                </div>

            </div>

        </div>


        {{-- AKTIF --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <p class="text-sm font-medium text-gray-500">
                Wilayah Aktif
            </p>

            <div class="mt-2 flex items-center justify-between">

                <p class="text-2xl font-bold text-green-600">
                    {{ $totalAktif }}
                </p>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 text-green-600">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>

                </div>

            </div>

        </div>


        {{-- NONAKTIF --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <p class="text-sm font-medium text-gray-500">
                Wilayah Nonaktif
            </p>

            <div class="mt-2 flex items-center justify-between">

                <p class="text-2xl font-bold text-red-600">
                    {{ $totalWilayah - $totalAktif }}
                </p>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>

                </div>

            </div>

        </div>


        {{-- RESORT --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <p class="text-sm font-medium text-gray-500">
                Total Resort
            </p>

            <div class="mt-2 flex items-center justify-between">

                <p class="text-2xl font-bold text-[#1d2d50]">
                    {{ $totalResort }}
                </p>

                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-50 text-yellow-600">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 21h18M5 21V5l7-3 7 3v16M9 9h1m4 0h1m-6 4h1m4 0h1m-6 4h1m4 0h1"
                        />
                    </svg>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
        FLASH MESSAGE
    ========================================================== --}}

    @if(session('success'))

        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>

    @endif


    @if(session('error'))

        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>

    @endif


    {{-- VALIDATION ERROR --}}
    @if($errors->any())

        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">

            <p class="text-sm font-semibold text-red-800">
                Terdapat kesalahan:
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- =========================================================
        TABLE CARD
    ========================================================== --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- SEARCH --}}
        <div class="border-b border-gray-200 p-4 sm:p-5">

            <form
                method="GET"
                action="{{ route('admin.wilayah.index') }}"
                class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_auto_auto]"
            >

                {{-- SEARCH --}}
                <div class="relative">

                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"
                            />
                        </svg>

                    </div>


                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nama wilayah atau keterangan..."
                        class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                    >

                </div>


                {{-- STATUS --}}
                <select
                    name="status"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-700 outline-none focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                >

                    <option value="">
                        Semua Status
                    </option>

                    <option
                        value="aktif"
                        @selected(request('status') === 'aktif')
                    >
                        Aktif
                    </option>

                    <option
                        value="nonaktif"
                        @selected(request('status') === 'nonaktif')
                    >
                        Nonaktif
                    </option>

                </select>


                {{-- ACTION --}}
                <div class="flex gap-2">

                    <button
                        type="submit"
                        class="flex-1 rounded-lg bg-[#0b1f3a] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50] md:flex-none"
                    >
                        Cari
                    </button>


                    @if(request()->filled('search') || request()->filled('status'))

                        <a
                            href="{{ route('admin.wilayah.index') }}"
                            class="flex flex-1 items-center justify-center rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 md:flex-none"
                        >
                            Reset
                        </a>

                    @endif

                </div>

            </form>

        </div>


        {{-- =====================================================
            DESKTOP TABLE
        ====================================================== --}}
        <div class="hidden overflow-x-auto md:block">

            <table class="w-full">

                <thead class="bg-[#0b1f3a] text-left text-xs font-semibold uppercase text-white">

                    <tr>

                        <th class="w-16 px-5 py-4 text-center">
                            No
                        </th>

                        <th class="px-5 py-4">
                            Nama Wilayah
                        </th>

                        <th class="px-5 py-4 text-center">
                            Resort
                        </th>

                        <th class="px-5 py-4">
                            Keterangan
                        </th>

                        <th class="px-5 py-4 text-center">
                            Status
                        </th>

                        <th class="w-32 px-5 py-4 text-center">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100">

                    @forelse($wilayahs as $index => $wilayah)

                        <tr class="transition hover:bg-gray-50">

                            <td class="px-5 py-4 text-center text-sm text-gray-500">
                                {{ $wilayahs->firstItem() + $index }}
                            </td>


                            <td class="px-5 py-4">

                                <p class="font-semibold text-[#1d2d50]">
                                    {{ $wilayah->nama_wilayah }}
                                </p>

                            </td>


                            <td class="px-5 py-4 text-center">

                                <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">

                                    {{ $wilayah->resorts_count }}

                                    Resort

                                </span>

                            </td>


                            <td class="max-w-md px-5 py-4 text-sm text-gray-600">

                                @if($wilayah->keterangan)

                                    {{ $wilayah->keterangan }}

                                @else

                                    <span class="text-gray-400">
                                        —
                                    </span>

                                @endif

                            </td>


                            <td class="px-5 py-4 text-center">

                                @if($wilayah->is_active)

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


                            <td class="px-5 py-4">

                                <div class="flex justify-center gap-2">

                                    {{-- EDIT --}}
                                    <button
                                        type="button"
                                        @click="openEditModal({
                                            id: {{ $wilayah->id }},
                                            nama_wilayah: @js($wilayah->nama_wilayah),
                                            keterangan: @js($wilayah->keterangan),
                                            is_active: {{ $wilayah->is_active ? 'true' : 'false' }}
                                        })"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                                        title="Edit"
                                    >

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.805.805-2.685a4.5 4.5 0 0 1 1.13-1.897L16.862 4.487Z"
                                            />
                                        </svg>

                                    </button>


                                    {{-- STATUS --}}
                                    <button
                                        type="button"
                                        @click="openStatusModal({
                                            id: {{ $wilayah->id }},
                                            nama_wilayah: @js($wilayah->nama_wilayah),
                                            is_active: {{ $wilayah->is_active ? 'true' : 'false' }}
                                        })"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:border-yellow-200 hover:bg-yellow-50 hover:text-yellow-600"
                                        title="Ubah Status"
                                    >

                                        @if($wilayah->is_active)

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M15 12H9m12 0a9 9 0 1 1-18 0c0-4.97 4.03-9 9-9s9 4.03 9 9Z"
                                                />
                                            </svg>

                                        @else

                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                                />
                                            </svg>

                                        @endif

                                    </button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="px-5 py-14 text-center"
                            >

                                <p class="font-semibold text-gray-700">
                                    Belum ada data wilayah
                                </p>

                                <p class="mt-1 text-sm text-gray-500">
                                    Tambahkan wilayah untuk mulai mengelola data.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- =====================================================
            MOBILE CARDS
        ====================================================== --}}
        <div class="space-y-3 p-4 md:hidden">

            @forelse($wilayahs as $index => $wilayah)

                <div class="rounded-xl border border-gray-200 bg-white p-4">

                    <div class="flex items-start justify-between gap-3">

                        <div class="min-w-0">

                            <p class="text-xs text-gray-400">
                                #{{ $wilayahs->firstItem() + $index }}
                            </p>

                            <h3 class="mt-1 break-words font-semibold text-[#1d2d50]">
                                {{ $wilayah->nama_wilayah }}
                            </h3>

                        </div>


                        @if($wilayah->is_active)

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

                            <span class="text-sm text-gray-500">
                                Resort
                            </span>

                            <span class="font-semibold text-[#1d2d50]">
                                {{ $wilayah->resorts_count }}
                            </span>

                        </div>


                        <div>

                            <p class="text-sm text-gray-500">
                                Keterangan
                            </p>

                            <p class="mt-1 break-words text-sm text-gray-700">
                                {{ $wilayah->keterangan ?: '—' }}
                            </p>

                        </div>

                    </div>


                    <div class="mt-4 flex gap-2 border-t border-gray-100 pt-4">

                        {{-- EDIT --}}
                        <button
                            type="button"
                            @click="openEditModal({
                                id: {{ $wilayah->id }},
                                nama_wilayah: @js($wilayah->nama_wilayah),
                                keterangan: @js($wilayah->keterangan),
                                is_active: {{ $wilayah->is_active ? 'true' : 'false' }}
                            })"
                            class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13l-2.685.805a4.5 4.5 0 0 1 1.13-1.897L16.862 4.487Z"
                                />
                            </svg>

                            Edit

                        </button>


                        {{-- STATUS --}}
                        <button
                            type="button"
                            @click="openStatusModal({
                                id: {{ $wilayah->id }},
                                nama_wilayah: @js($wilayah->nama_wilayah),
                                is_active: {{ $wilayah->is_active ? 'true' : 'false' }}
                            })"
                            class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-[#0b1f3a] px-3 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50]"
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 6v12m6-6H6"
                                />
                            </svg>

                            Status

                        </button>

                    </div>

                </div>

            @empty

                <div class="py-12 text-center">

                    <p class="font-semibold text-gray-700">
                        Belum ada data wilayah
                    </p>

                    <p class="mt-1 text-sm text-gray-500">
                        Tambahkan wilayah untuk mulai mengelola data.
                    </p>

                    <button
                        type="button"
                        @click="openCreateModal()"
                        class="mt-4 rounded-lg bg-[#0b1f3a] px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        Tambah Wilayah
                    </button>

                </div>

            @endforelse

        </div>


        {{-- =====================================================
            PAGINATION
        ====================================================== --}}
        @if($wilayahs->total() > 0)

            <div class="border-t border-gray-200 px-4 py-4 sm:px-5">

                <div class="flex flex-col gap-4">

                    <p class="text-center text-sm text-gray-500 sm:text-left">

                        Menampilkan

                        <span class="font-medium text-gray-700">
                            {{ $wilayahs->firstItem() ?? 0 }}
                        </span>

                        -

                        <span class="font-medium text-gray-700">
                            {{ $wilayahs->lastItem() ?? 0 }}
                        </span>

                        dari

                        <span class="font-medium text-gray-700">
                            {{ $wilayahs->total() }}
                        </span>

                        wilayah

                    </p>


                    <div class="overflow-x-auto">

                        <div class="flex justify-center sm:justify-end">

                            {{ $wilayahs->links() }}

                        </div>

                    </div>

                </div>

            </div>

        @endif

    </div>


    {{-- =========================================================
        MODAL TAMBAH
    ========================================================== --}}
    <div
        x-show="showCreateModal"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-black/50 p-4"
        @keydown.escape.window="showCreateModal = false"
    >

        <div
            x-show="showCreateModal"
            x-transition.scale
            @click.outside="showCreateModal = false"
            class="my-auto w-full max-w-lg overflow-hidden rounded-xl bg-white shadow-xl"
        >

            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 sm:px-6">

                <div>

                    <h3 class="text-lg font-bold text-[#1d2d50]">
                        Tambah Wilayah
                    </h3>

                    <p class="mt-1 text-xs text-gray-500">
                        Tambahkan wilayah operasional baru.
                    </p>

                </div>


                <button
                    type="button"
                    @click="showCreateModal = false"
                    class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"
                >
                    ✕
                </button>

            </div>


            <form
                method="POST"
                action="{{ route('admin.wilayah.store') }}"
            >

                @csrf

                <div class="space-y-5 px-5 py-5 sm:px-6">

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-gray-700">
                            Nama Wilayah
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="text"
                            name="nama_wilayah"
                            value="{{ old('nama_wilayah') }}"
                            required
                            maxlength="255"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-gray-700">
                            Keterangan
                        </label>

                        <textarea
                            name="keterangan"
                            rows="4"
                            class="w-full resize-none rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                        >{{ old('keterangan') }}</textarea>

                    </div>

                </div>


                <div class="flex flex-col-reverse gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">

                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-[#0b1f3a] px-5 py-2.5 text-sm font-semibold text-white"
                    >
                        Simpan Wilayah
                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- =========================================================
        MODAL EDIT
    ========================================================== --}}
    <div
        x-show="showEditModal"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-black/50 p-4"
        @keydown.escape.window="showEditModal = false"
    >

        <div
            x-show="showEditModal"
            x-transition.scale
            @click.outside="showEditModal = false"
            class="my-auto w-full max-w-lg overflow-hidden rounded-xl bg-white shadow-xl"
        >

            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 sm:px-6">

                <div>

                    <h3 class="text-lg font-bold text-[#1d2d50]">
                        Edit Wilayah
                    </h3>

                    <p class="mt-1 text-xs text-gray-500">
                        Perbarui informasi wilayah.
                    </p>

                </div>


                <button
                    type="button"
                    @click="showEditModal = false"
                    class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"
                >
                    ✕
                </button>

            </div>


            <form
                method="POST"
                :action="'{{ url('/admin/wilayah') }}/' + editData.id"
            >

                @csrf

                @method('PUT')


                <div class="space-y-5 px-5 py-5 sm:px-6">

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-gray-700">
                            Nama Wilayah
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="text"
                            name="nama_wilayah"
                            x-model="editData.nama_wilayah"
                            required
                            maxlength="255"
                            class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-gray-700">
                            Keterangan
                        </label>

                        <textarea
                            name="keterangan"
                            rows="4"
                            x-model="editData.keterangan"
                            class="w-full resize-none rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                        ></textarea>

                    </div>

                </div>


                <div class="flex flex-col-reverse gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">

                    <button
                        type="button"
                        @click="showEditModal = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="rounded-lg bg-[#0b1f3a] px-5 py-2.5 text-sm font-semibold text-white"
                    >
                        Simpan Perubahan
                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- =========================================================
        MODAL STATUS
    ========================================================== --}}
    <div
        x-show="showStatusModal"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-black/50 p-4"
        @keydown.escape.window="showStatusModal = false"
    >

        <div
            x-show="showStatusModal"
            x-transition.scale
            @click.outside="showStatusModal = false"
            class="my-auto w-full max-w-md overflow-hidden rounded-xl bg-white shadow-xl"
        >

            <div class="px-5 py-6 text-center sm:px-6">

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-yellow-50 text-yellow-600">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-7 w-7"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 9v3.75m0 3h.008M10.29 3.86 1.82 18a2 2 0 0 0 1.72 3h16.92a2 2 0 0 0 1.72 3h16.92a2 2 0 0 0 1.72-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
                        />
                    </svg>

                </div>


                <h3 class="mt-4 text-lg font-bold text-[#1d2d50]">
                    Ubah Status Wilayah?
                </h3>


                <p class="mt-2 text-sm leading-6 text-gray-500">

                    Status wilayah

                    <span
                        class="font-semibold text-gray-700"
                        x-text="statusData.nama_wilayah"
                    ></span>

                    akan diubah menjadi

                    <span
                        class="font-semibold"
                        :class="statusData.is_active ? 'text-red-600' : 'text-green-600'"
                        x-text="statusData.is_active ? 'Nonaktif' : 'Aktif'"
                    ></span>.

                </p>

            </div>


            <form
                method="POST"
                :action="'{{ url('/admin/wilayah') }}/' + statusData.id + '/toggle-status'"
            >

                @csrf

                @method('PATCH')


                <div class="flex flex-col-reverse gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">

                    <button
                        type="button"
                        @click="showStatusModal = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700"
                    >
                        Batal
                    </button>


                    <button
                        type="submit"
                        class="rounded-lg bg-[#0b1f3a] px-5 py-2.5 text-sm font-semibold text-white"
                    >
                        Ya, Ubah Status
                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- =========================================================
        MODAL IMPORT EXCEL
    ========================================================== --}}
    <div
        x-show="showImportModal"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[60] flex items-center justify-center overflow-y-auto bg-black/50 p-4"
        @keydown.escape.window="showImportModal = false"
    >

        <div
            x-show="showImportModal"
            x-transition.scale
            @click.outside="showImportModal = false"
            class="my-auto w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-xl"
        >

            {{-- HEADER --}}
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 sm:px-6">

                <div>

                    <h3 class="text-lg font-bold text-[#1d2d50]">
                        Import Data Wilayah
                    </h3>

                    <p class="mt-1 text-xs text-gray-500">
                        Import data wilayah menggunakan file Excel.
                    </p>

                </div>


                <button
                    type="button"
                    @click="showImportModal = false"
                    class="rounded-lg p-2 text-gray-400 hover:bg-gray-100"
                >
                    ✕
                </button>

            </div>


            {{-- FORM IMPORT --}}
            <form
                method="POST"
                action="{{ route('admin.wilayah.import.preview.store') }}"
                enctype="multipart/form-data"
            >

                @csrf


                <div class="space-y-5 px-5 py-6 sm:px-6">

                    {{-- TEMPLATE --}}
                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">

                        <p class="text-sm font-semibold text-blue-800">
                            Gunakan template Excel
                        </p>

                        <p class="mt-1 text-xs leading-5 text-blue-700">
                            Pastikan format kolom sesuai dengan template sebelum melakukan import.
                        </p>


                        <a
                            href="{{ route('admin.wilayah.import.template') }}"
                            class="mt-3 inline-flex text-xs font-semibold text-blue-700 hover:text-blue-900"
                        >
                            Download Template Excel →
                        </a>

                    </div>


                    {{-- FILE --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-gray-700">

                            File Excel

                            <span class="text-red-500">
                                *
                            </span>

                        </label>


                        <div class="rounded-lg border-2 border-dashed border-gray-300 p-5 text-center transition hover:border-[#0b1f3a] sm:p-6">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="mx-auto h-10 w-10 text-gray-400"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 16V4m0 0 4 4m-4-4L8 8m-5 4v6a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-6"
                                />

                            </svg>


                            <p class="mt-3 text-sm font-medium text-gray-700">
                                Pilih file Excel
                            </p>

                            <p class="mt-1 text-xs text-gray-500">
                                Format .xlsx atau .xls, maksimal 5 MB
                            </p>


                            <input
                                type="file"
                                name="file"
                                accept=".xlsx,.xls"
                                required
                                class="mt-4 block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-[#0b1f3a] file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-[#142d50]"
                            >

                        </div>

                    </div>


                    {{-- FLOW --}}
                    <div class="rounded-lg bg-gray-50 p-4">

                        <p class="text-xs font-semibold text-gray-700">
                            Proses Import
                        </p>

                        <ol class="mt-2 space-y-1 text-xs leading-5 text-gray-500">

                            <li>
                                1. Download template Excel.
                            </li>

                            <li>
                                2. Isi data wilayah.
                            </li>

                            <li>
                                3. Upload file Excel.
                            </li>

                            <li>
                                4. Sistem melakukan validasi.
                            </li>

                            <li>
                                5. Periksa preview data.
                            </li>

                            <li>
                                6. Konfirmasi untuk menyimpan data valid.
                            </li>

                        </ol>

                    </div>

                </div>


                {{-- FOOTER --}}
                <div class="flex flex-col-reverse gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">

                    <button
                        type="button"
                        @click="showImportModal = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
                    >
                        Batal
                    </button>


                    <button
                        type="submit"
                        class="rounded-lg bg-[#0b1f3a] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50]"
                    >
                        Preview Import
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


{{-- =========================================================
    ALPINE
========================================================= --}}
<script>

    function wilayahPage() {

        return {

            showCreateModal: false,

            showEditModal: false,

            showStatusModal: false,

            showImportModal: false,


            editData: {
                id: null,
                nama_wilayah: '',
                keterangan: '',
                is_active: true,
            },


            statusData: {
                id: null,
                nama_wilayah: '',
                is_active: true,
            },


            openCreateModal() {

                this.showCreateModal = true;

            },


            openEditModal(data) {

                this.editData = {
                    id: data.id,
                    nama_wilayah: data.nama_wilayah ?? '',
                    keterangan: data.keterangan ?? '',
                    is_active: data.is_active,
                };

                this.showEditModal = true;

            },


            openStatusModal(data) {

                this.statusData = {
                    id: data.id,
                    nama_wilayah: data.nama_wilayah ?? '',
                    is_active: data.is_active,
                };

                this.showStatusModal = true;

            },


            openImportModal() {

                this.showImportModal = true;

            },

        };

    }

</script>

@endsection