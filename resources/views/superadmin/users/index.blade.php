@extends('layouts.superadmin')

@section('title', 'Manajemen Pengguna')

@section('page-title', 'Manajemen Pengguna')

@section('content')

    {{-- =========================================================
         PAGE HEADER
    ========================================================== --}}
    <div class="mb-6 flex items-start justify-between">

        <div>
            <h1 class="text-2xl font-bold text-[#1b2b4b]">
                Manajemen Pengguna
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Kelola data pengguna dan hak akses sistem PJL.
            </p>
        </div>

        <div class="flex items-center gap-3">

            {{-- Import Excel --}}
            <button
                type="button"
                id="open-import-modal"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-[#1b2b4b] transition hover:bg-gray-50"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M12 16V4"
                    />

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M8 8l4-4 4 4"
                    />

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M5 12v6a2 2 0 002 2h10a2 2 0 002-2v-6"
                    />
                </svg>

                Import Excel
            </button>

            {{-- Tambah User --}}
            <a
                href="{{ route('superadmin.users.create') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-[#0b1f3a] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50]"
            >
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
                        d="M12 5v14M5 12h14"
                    />
                </svg>

                Tambah User
            </a>

        </div>

    </div>


    {{-- =========================================================
         STATISTICS
    ========================================================== --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- Total User --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-[#0b1f3a]">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"
                        />

                        <circle
                            cx="9"
                            cy="7"
                            r="4"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M22 21v-2a4 4 0 00-3-3.87"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16 3.13a4 4 0 010 7.75"
                        />
                    </svg>

                </div>

                <div>

                    <p
                        id="total-users"
                        class="text-2xl font-bold text-[#1b2b4b]"
                    >
                        {{ $totalUsers }}
                    </p>

                    <p class="text-sm text-gray-500">
                        Total User
                    </p>

                </div>

            </div>

        </div>


        {{-- Admin --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-50 text-blue-600">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 14l9-5-9-5-9 5 9 5z"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 12v5c3 2 11 2 14 0v-5"
                        />
                    </svg>

                </div>

                <div>

                    <p
                        id="total-admins"
                        class="text-2xl font-bold text-[#1b2b4b]"
                    >
                        {{ $totalAdmins }}
                    </p>

                    <p class="text-sm text-gray-500">
                        Admin
                    </p>

                </div>

            </div>

        </div>


        {{-- PJL --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-orange-50 text-orange-500">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 14l9-5-9-5-9 5 9 5z"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 14v7"
                        />
                    </svg>

                </div>

                <div>

                    <p
                        id="total-pjl"
                        class="text-2xl font-bold text-[#1b2b4b]"
                    >
                        {{ $totalPjl }}
                    </p>

                    <p class="text-sm text-gray-500">
                        PJL
                    </p>

                </div>

            </div>

        </div>


        {{-- Active --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-green-50 text-green-500">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>

                </div>

                <div>

                    <p
                        id="total-active"
                        class="text-2xl font-bold text-[#1b2b4b]"
                    >
                        {{ $totalActive }}
                    </p>

                    <p class="text-sm text-gray-500">
                        User Aktif
                    </p>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         FLASH MESSAGE
    ========================================================== --}}
    @if (session('success'))

        <div
            id="flash-success"
            class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3"
        >
            <p class="text-sm font-medium text-green-700">
                {{ session('success') }}
            </p>
        </div>

    @endif


    @if (session('error'))

        <div
            id="flash-error"
            class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3"
        >
            <p class="text-sm font-medium text-red-700">
                {{ session('error') }}
            </p>
        </div>

    @endif


    {{-- =========================================================
         USER TABLE CARD
    ========================================================== --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- SEARCH & FILTER --}}
        <div class="border-b border-gray-200 p-5">

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">

                {{-- Search --}}
                <div class="relative w-full lg:max-w-sm">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-4.35-4.35"
                        />

                        <circle
                            cx="11"
                            cy="11"
                            r="7"
                        />
                    </svg>

                    <input
                        id="user-search"
                        type="search"
                        autocomplete="off"
                        value="{{ request('search') }}"
                        placeholder="Cari NIP, nama, atau email..."
                        class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-10 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                    >

                    <div
                        id="search-loading"
                        class="absolute right-3 top-1/2 hidden -translate-y-1/2"
                    >

                        <svg
                            class="h-4 w-4 animate-spin text-[#0b1f3a]"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            />
                        </svg>

                    </div>

                </div>


                {{-- Filters --}}
                <div class="flex flex-wrap items-center gap-3">

                    <select
                        id="role-filter"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                    >

                        <option value="">
                            Semua Role
                        </option>

                        <option
                            value="superadmin"
                            {{ request('role') === 'superadmin' ? 'selected' : '' }}
                        >
                            Superadmin
                        </option>

                        <option
                            value="admin"
                            {{ request('role') === 'admin' ? 'selected' : '' }}
                        >
                            Admin
                        </option>

                        <option
                            value="pjl"
                            {{ request('role') === 'pjl' ? 'selected' : '' }}
                        >
                            PJL
                        </option>

                    </select>


                    <select
                        id="status-filter"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-600 outline-none transition focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                    >

                        <option value="">
                            Semua Status
                        </option>

                        <option
                            value="active"
                            {{ request('status') === 'active' ? 'selected' : '' }}
                        >
                            Aktif
                        </option>

                        <option
                            value="inactive"
                            {{ request('status') === 'inactive' ? 'selected' : '' }}
                        >
                            Nonaktif
                        </option>

                    </select>


                    <button
                        id="reset-filter"
                        type="button"
                        class="hidden rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                    >
                        Reset
                    </button>

                </div>

            </div>

        </div>


        {{-- TABLE --}}
        <div class="relative overflow-x-auto">

            <div
                id="table-loading"
                class="absolute inset-0 z-10 hidden items-center justify-center bg-white/70 backdrop-blur-[1px]"
            >

                <div class="flex items-center gap-3 rounded-lg bg-white px-4 py-3 shadow-sm">

                    <svg
                        class="h-5 w-5 animate-spin text-[#0b1f3a]"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />

                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                        />
                    </svg>

                    <span class="text-sm text-gray-600">
                        Memuat data...
                    </span>

                </div>

            </div>


            <table class="min-w-full">

                <thead class="bg-[#0b1f3a] text-left text-xs font-semibold uppercase text-white">

                    <tr>

                        <th class="px-5 py-4">
                            No
                        </th>

                        <th class="px-5 py-4">
                            NIP
                        </th>

                        <th class="px-5 py-4">
                            Nama Lengkap
                        </th>

                        <th class="px-5 py-4">
                            Email
                        </th>

                        <th class="px-5 py-4">
                            Role
                        </th>

                        <th class="px-5 py-4">
                            Status
                        </th>

                        <th class="px-5 py-4 text-center">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody id="users-table-body">

                    @include('superadmin.users.partials.table', [
                        'users' => $users,
                    ])

                </tbody>

            </table>

        </div>


        {{-- PAGINATION --}}
        <div id="users-pagination">

            @include('superadmin.users.partials.pagination', [
                'users' => $users,
            ])

        </div>

    </div>


    {{-- =========================================================
         IMPORT EXCEL MODAL
    ========================================================== --}}
    <div
        id="import-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6"
    >

        <div
            class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl"
            onclick="event.stopPropagation()"
        >

            {{-- Modal Header --}}
            <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-6 py-4">

                <div>

                    <h2 class="text-lg font-bold text-[#1b2b4b]">
                        Import Data Pengguna
                    </h2>

                    <p class="mt-1 text-xs text-gray-500">
                        Upload file Excel untuk memeriksa data pengguna sebelum dimasukkan ke sistem.
                    </p>

                </div>


                <button
                    type="button"
                    id="close-import-modal"
                    class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>

                </button>

            </div>


            {{-- Modal Body --}}
            <div class="min-h-0 overflow-y-auto p-6">

                {{-- =================================================
                     STEP 1
                ================================================== --}}
                <div
                    id="import-upload-section"
                    class="space-y-5"
                >

                    <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">

                        <div class="flex gap-3">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="mt-0.5 h-5 w-5 shrink-0 text-blue-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 11v5"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 8h.01"
                                />
                            </svg>


                            <div>

                                <p class="text-sm font-semibold text-blue-800">
                                    Gunakan template Excel
                                </p>

                                <p class="mt-1 text-xs leading-5 text-blue-700">
                                    Pastikan nama kolom dan format data sesuai dengan template agar proses validasi berjalan dengan benar.
                                </p>

                                <a
                                    href="{{ route('superadmin.users.import.template') }}"
                                    class="mt-3 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700"
                                >

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 3v12"
                                        />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M8 11l4 4 4-4"
                                        />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M5 21h14"
                                        />
                                    </svg>

                                    Download Template

                                </a>

                            </div>

                        </div>

                    </div>


                    {{-- DROPZONE --}}
                    <div
                        id="excel-dropzone"
                        class="cursor-pointer rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center transition hover:border-[#0b1f3a] hover:bg-gray-100"
                    >

                        <input
                            type="file"
                            id="excel-file"
                            class="hidden"
                            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                        >


                        <div id="dropzone-empty">

                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-[#0b1f3a] shadow-sm">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-7 w-7"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 16V4"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M8 8l4-4 4 4"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M5 12v6a2 2 0 002 2h10a2 2 0 002-2v-6"
                                    />
                                </svg>

                            </div>


                            <p class="mt-4 text-sm font-semibold text-gray-700">
                                Drag & drop file Excel di sini
                            </p>

                            <p class="mt-1 text-xs text-gray-500">
                                atau klik untuk memilih file
                            </p>

                            <p class="mt-2 text-xs text-gray-400">
                                Format .xlsx • Maksimal 5 MB
                            </p>

                        </div>


                        {{-- Selected File --}}
                        <div
                            id="selected-file"
                            class="hidden"
                        >

                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-green-50 text-green-600">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-7 w-7"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M14 2v6h6"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M8 13h8M8 17h6"
                                    />

                                </svg>

                            </div>


                            <p
                                id="selected-file-name"
                                class="mt-4 break-all text-sm font-semibold text-gray-700"
                            ></p>


                            <p
                                id="selected-file-size"
                                class="mt-1 text-xs text-gray-500"
                            ></p>


                            <button
                                type="button"
                                id="change-file-button"
                                class="mt-3 text-xs font-semibold text-red-600 hover:text-red-700"
                            >
                                Ganti File
                            </button>

                        </div>

                    </div>


                    {{-- Client Error --}}
                    <div
                        id="import-client-error"
                        class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3"
                    >

                        <p
                            id="import-client-error-text"
                            class="text-sm font-medium text-red-700"
                        ></p>

                    </div>


                    {{-- Upload Button --}}
                    <div class="flex justify-end">

                        <button
                            type="button"
                            id="upload-excel-button"
                            disabled
                            class="inline-flex items-center gap-2 rounded-lg bg-[#0b1f3a] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50] disabled:cursor-not-allowed disabled:bg-gray-300"
                        >

                            <svg
                                id="upload-excel-icon"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 16V4"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M8 8l4-4 4 4"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 12v6a2 2 0 002 2h10a2 2 0 002-2v-6"
                                />
                            </svg>


                            <svg
                                id="upload-excel-loading"
                                class="hidden h-5 w-5 animate-spin"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle
                                    class="opacity-25"
                                    cx="12"
                                    cy="12"
                                    r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                />

                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                />
                            </svg>


                            <span id="upload-excel-text">
                                Upload & Periksa
                            </span>

                        </button>

                    </div>

                </div>


                {{-- =================================================
                     STEP 2
                ================================================== --}}
                <div
                    id="import-preview-section"
                    class="hidden space-y-5"
                >

                    {{-- Preview Header --}}
                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="text-base font-bold text-[#1b2b4b]">
                                Hasil Pemeriksaan
                            </h3>

                            <p class="mt-1 text-xs text-gray-500">
                                Periksa kembali data sebelum melanjutkan proses import.
                            </p>

                        </div>


                        <span
                            id="preview-file-name"
                            class="max-w-xs truncate rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600"
                        ></span>

                    </div>


                    {{-- Summary --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">

                            <p class="text-xs font-medium text-gray-500">
                                Total Baris
                            </p>

                            <p
                                id="preview-total"
                                class="mt-1 text-2xl font-bold text-[#1b2b4b]"
                            >
                                0
                            </p>

                        </div>


                        <div class="rounded-xl border border-green-200 bg-green-50 p-4">

                            <p class="text-xs font-medium text-green-700">
                                Data Valid
                            </p>

                            <p
                                id="preview-valid"
                                class="mt-1 text-2xl font-bold text-green-700"
                            >
                                0
                            </p>

                        </div>


                        <div class="rounded-xl border border-red-200 bg-red-50 p-4">

                            <p class="text-xs font-medium text-red-700">
                                Data Bermasalah
                            </p>

                            <p
                                id="preview-invalid"
                                class="mt-1 text-2xl font-bold text-red-700"
                            >
                                0
                            </p>

                        </div>

                    </div>


                    {{-- Validation Errors --}}
                    <div
                        id="preview-errors-section"
                        class="hidden"
                    >

                        <div class="mb-3 flex items-center justify-between">

                            <h4 class="text-sm font-bold text-red-700">
                                Detail Validasi
                            </h4>

                            <span
                                id="preview-error-count"
                                class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700"
                            >
                                0
                            </span>

                        </div>


                        <div
                            id="preview-errors"
                            class="max-h-56 overflow-y-auto rounded-lg border border-red-200"
                        ></div>

                    </div>


                    {{-- Valid Data --}}
                    <div>

                        <div class="mb-3 flex items-center justify-between">

                            <h4 class="text-sm font-bold text-[#1b2b4b]">
                                Data yang Siap Diimport
                            </h4>

                            <span
                                id="preview-valid-count"
                                class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700"
                            >
                                0 data
                            </span>

                        </div>


                        <div class="overflow-hidden rounded-lg border border-gray-200">

                            <div class="max-h-72 overflow-auto">

                                <table class="min-w-full text-left text-sm">

                                    <thead class="sticky top-0 bg-[#0b1f3a] text-xs font-semibold uppercase text-white">

                                        <tr>

                                            <th class="px-4 py-3">
                                                No
                                            </th>

                                            <th class="px-4 py-3">
                                                NIP
                                            </th>

                                            <th class="px-4 py-3">
                                                Nama
                                            </th>

                                            <th class="px-4 py-3">
                                                Email
                                            </th>

                                            <th class="px-4 py-3">
                                                Role
                                            </th>

                                            <th class="px-4 py-3">
                                                Status
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody
                                        id="preview-valid-table"
                                        class="divide-y divide-gray-100"
                                    ></tbody>

                                </table>

                            </div>

                        </div>

                    </div>


                    {{-- Warning --}}
                    <div
                        id="preview-warning"
                        class="hidden rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3"
                    >

                        <div class="flex gap-3">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="mt-0.5 h-5 w-5 shrink-0 text-yellow-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.8"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v4"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 17h.01"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M10.29 3.86l-7.82 14a2 2 0 001.74 3h15.58a2 2 0 001.74-3l-7.82-14a2 2 0 00-3.42 0z"
                                />
                            </svg>


                            <p
                                id="preview-warning-text"
                                class="text-xs leading-5 text-yellow-800"
                            ></p>

                        </div>

                    </div>


                    {{-- No Valid Data --}}
                    <div
                        id="preview-no-valid"
                        class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3"
                    >

                        <p class="text-sm font-medium text-red-700">
                            Tidak ada data valid yang dapat diimport.
                            Perbaiki data pada file Excel kemudian upload kembali.
                        </p>

                    </div>

                </div>

            </div>


            {{-- Modal Footer --}}
            <div class="flex shrink-0 items-center justify-between border-t border-gray-200 px-6 py-4">

                <button
                    type="button"
                    id="cancel-import-modal"
                    class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                >
                    Tutup
                </button>


                <div class="flex items-center gap-3">

                    <button
                        type="button"
                        id="back-import-button"
                        class="hidden rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                    >
                        Upload File Lain
                    </button>


                    <button
                        type="button"
                        id="confirm-import-button"
                        disabled
                        class="hidden inline-flex items-center gap-2 rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:bg-gray-300"
                    >

                        <svg
                            id="confirm-import-icon"
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>


                        <svg
                            id="confirm-import-loading"
                            class="hidden h-5 w-5 animate-spin"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />

                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                            />
                        </svg>


                        <span id="confirm-import-text">
                            Konfirmasi Import
                        </span>

                    </button>

                </div>

            </div>

        </div>

    </div>

@endsection


{{-- =============================================================
     JAVASCRIPT
============================================================= --}}
@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Elements
    |--------------------------------------------------------------------------
    */

    const searchInput =
        document.getElementById('user-search');

    const roleFilter =
        document.getElementById('role-filter');

    const statusFilter =
        document.getElementById('status-filter');

    const resetButton =
        document.getElementById('reset-filter');

    const tableBody =
        document.getElementById('users-table-body');

    const paginationContainer =
        document.getElementById('users-pagination');

    const tableLoading =
        document.getElementById('table-loading');

    const searchLoading =
        document.getElementById('search-loading');

    const totalUsers =
        document.getElementById('total-users');

    const totalAdmins =
        document.getElementById('total-admins');

    const totalPjl =
        document.getElementById('total-pjl');

    const totalActive =
        document.getElementById('total-active');


    /*
    |--------------------------------------------------------------------------
    | Import Elements
    |--------------------------------------------------------------------------
    */

    const importModal =
        document.getElementById('import-modal');

    const openImportModal =
        document.getElementById('open-import-modal');

    const closeImportModal =
        document.getElementById('close-import-modal');

    const cancelImportModal =
        document.getElementById('cancel-import-modal');

    const backImportButton =
        document.getElementById('back-import-button');

    const excelDropzone =
        document.getElementById('excel-dropzone');

    const excelFile =
        document.getElementById('excel-file');

    const dropzoneEmpty =
        document.getElementById('dropzone-empty');

    const selectedFile =
        document.getElementById('selected-file');

    const selectedFileName =
        document.getElementById('selected-file-name');

    const selectedFileSize =
        document.getElementById('selected-file-size');

    const changeFileButton =
        document.getElementById('change-file-button');

    const uploadExcelButton =
        document.getElementById('upload-excel-button');

    const uploadExcelIcon =
        document.getElementById('upload-excel-icon');

    const uploadExcelLoading =
        document.getElementById('upload-excel-loading');

    const uploadExcelText =
        document.getElementById('upload-excel-text');

    const importClientError =
        document.getElementById('import-client-error');

    const importClientErrorText =
        document.getElementById('import-client-error-text');

    const importUploadSection =
        document.getElementById('import-upload-section');

    const importPreviewSection =
        document.getElementById('import-preview-section');

    const previewFileName =
        document.getElementById('preview-file-name');

    const previewTotal =
        document.getElementById('preview-total');

    const previewValid =
        document.getElementById('preview-valid');

    const previewInvalid =
        document.getElementById('preview-invalid');

    const previewValidCount =
        document.getElementById('preview-valid-count');

    const previewErrorsSection =
        document.getElementById('preview-errors-section');

    const previewErrors =
        document.getElementById('preview-errors');

    const previewErrorCount =
        document.getElementById('preview-error-count');

    const previewValidTable =
        document.getElementById('preview-valid-table');

    const previewWarning =
        document.getElementById('preview-warning');

    const previewWarningText =
        document.getElementById('preview-warning-text');

    const previewNoValid =
        document.getElementById('preview-no-valid');

    const confirmImportButton =
        document.getElementById('confirm-import-button');

    const confirmImportIcon =
        document.getElementById('confirm-import-icon');

    const confirmImportLoading =
        document.getElementById('confirm-import-loading');

    const confirmImportText =
        document.getElementById('confirm-import-text');


    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    let debounceTimer = null;

    let currentController = null;

    let selectedImportFile = null;

    let importPreviewToken = null;

    let isConfirmingImport = false;


    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    const csrfToken =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');


    /*
    |--------------------------------------------------------------------------
    | Loading Table
    |--------------------------------------------------------------------------
    */

    function setLoading(isLoading) {

        if (isLoading) {

            tableLoading.classList.remove('hidden');
            tableLoading.classList.add('flex');

            searchLoading.classList.remove('hidden');

        } else {

            tableLoading.classList.add('hidden');
            tableLoading.classList.remove('flex');

            searchLoading.classList.add('hidden');

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Reset Button
    |--------------------------------------------------------------------------
    */

    function updateResetButton() {

        const hasSearch =
            searchInput.value.trim() !== '';

        const hasRole =
            roleFilter.value !== '';

        const hasStatus =
            statusFilter.value !== '';


        if (
            hasSearch ||
            hasRole ||
            hasStatus
        ) {

            resetButton.classList.remove('hidden');

        } else {

            resetButton.classList.add('hidden');

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Build Query
    |--------------------------------------------------------------------------
    */

    function buildParams(page = null) {

        const params =
            new URLSearchParams();

        const search =
            searchInput.value.trim();

        const role =
            roleFilter.value;

        const status =
            statusFilter.value;


        if (search !== '') {

            params.set(
                'search',
                search
            );

        }


        if (role !== '') {

            params.set(
                'role',
                role
            );

        }


        if (status !== '') {

            params.set(
                'status',
                status
            );

        }


        if (page) {

            params.set(
                'page',
                page
            );

        }


        return params;

    }


    /*
    |--------------------------------------------------------------------------
    | AJAX Load Users
    |--------------------------------------------------------------------------
    */

    async function loadUsers(
        page = 1,
        customUrl = null
    ) {

        if (currentController) {

            currentController.abort();

        }


        currentController =
            new AbortController();


        let requestUrl;


        if (customUrl) {

            requestUrl =
                customUrl;

        } else {

            const params =
                buildParams(page);

            const baseUrl =
                "{{ route('superadmin.users.index') }}";

            requestUrl =
                `${baseUrl}?${params.toString()}`;

        }


        setLoading(true);


        try {

            const response =
                await fetch(
                    requestUrl,
                    {
                        method: 'GET',

                        headers: {

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'Accept':
                                'application/json',

                        },

                        signal:
                            currentController.signal,

                    }
                );


            if (!response.ok) {

                throw new Error(
                    `Request gagal (${response.status})`
                );

            }


            const data =
                await response.json();


            if (!data.success) {

                throw new Error(
                    data.message ||
                    'Response server tidak valid.'
                );

            }


            tableBody.innerHTML =
                data.table;

            paginationContainer.innerHTML =
                data.pagination;


            if (
                typeof data.count !== 'undefined' &&
                totalUsers
            ) {

                totalUsers.textContent =
                    data.count;

            }


            const url =
                new URL(requestUrl);


            window.history.replaceState(
                {},
                '',
                url.pathname +
                url.search
            );


            updateResetButton();


        } catch (error) {

            if (
                error.name === 'AbortError'
            ) {

                return;

            }


            console.error(
                'AJAX User Error:',
                error
            );


            tableBody.innerHTML = `

                <tr>

                    <td
                        colspan="7"
                        class="px-5 py-12 text-center"
                    >

                        <div class="flex flex-col items-center justify-center">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="mb-3 h-10 w-10 text-red-300"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="1.5"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 9v4"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 17h.01"
                                />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M10.29 3.86l-7.82 14a2 2 0 001.74 3h15.58a2 2 0 001.74-3l-7.82-14a2 2 0 00-3.42 0z"
                                />

                            </svg>

                            <p class="text-sm font-medium text-red-600">
                                Gagal memuat data pengguna.
                            </p>

                            <p class="mt-1 text-xs text-gray-400">
                                Silakan coba lagi.
                            </p>

                        </div>

                    </td>

                </tr>

            `;


            paginationContainer.innerHTML = '';

        } finally {

            setLoading(false);

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    searchInput.addEventListener(
        'input',
        function () {

            clearTimeout(
                debounceTimer
            );


            debounceTimer =
                setTimeout(
                    function () {

                        loadUsers(1);

                    },
                    500
                );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    roleFilter.addEventListener(
        'change',
        function () {

            loadUsers(1);

        }
    );


    statusFilter.addEventListener(
        'change',
        function () {

            loadUsers(1);

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Reset Filter
    |--------------------------------------------------------------------------
    */

    resetButton.addEventListener(
        'click',
        function () {

            searchInput.value = '';

            roleFilter.value = '';

            statusFilter.value = '';


            updateResetButton();

            loadUsers(1);

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    paginationContainer.addEventListener(
        'click',
        function (event) {

            const link =
                event.target.closest(
                    '.ajax-pagination-link'
                );


            if (!link) {

                return;

            }


            event.preventDefault();


            loadUsers(
                null,
                link.href
            );

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Toggle User Status
    |--------------------------------------------------------------------------
    */

    window.toggleUserStatus =
        async function (
            userId,
            userName
        ) {

            const confirmed =
                confirm(
                    `Apakah Anda yakin ingin mengubah status user "${userName}"?`
                );


            if (!confirmed) {

                return;

            }


            try {

                const response =
                    await fetch(
                        `/superadmin/users/${userId}/toggle-status`,
                        {
                            method: 'PATCH',

                            headers: {

                                'X-CSRF-TOKEN':
                                    csrfToken,

                                'X-Requested-With':
                                    'XMLHttpRequest',

                                'Accept':
                                    'application/json',

                            },

                        }
                    );


                const data =
                    await response.json();


                if (!response.ok) {

                    throw new Error(
                        data.message ||
                        'Gagal mengubah status user.'
                    );

                }


                const currentUrl =
                    new URL(
                        window.location.href
                    );


                await loadUsers(
                    currentUrl.searchParams.get('page') || 1
                );


            } catch (error) {

                console.error(
                    'Toggle Status Error:',
                    error
                );


                alert(
                    error.message ||
                    'Terjadi kesalahan saat mengubah status user.'
                );

            }

        };


    /*
    |--------------------------------------------------------------------------
    | Import Modal
    |--------------------------------------------------------------------------
    */

    function showImportModal() {

        resetImportModal();


        importModal.classList.remove(
            'hidden'
        );

        importModal.classList.add(
            'flex'
        );

    }


    function hideImportModal() {

        if (isConfirmingImport) {

            return;

        }


        importModal.classList.add(
            'hidden'
        );

        importModal.classList.remove(
            'flex'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Reset Import Modal
    |--------------------------------------------------------------------------
    */

    function resetImportModal() {

        selectedImportFile = null;

        importPreviewToken = null;

        isConfirmingImport = false;


        excelFile.value = '';


        dropzoneEmpty.classList.remove(
            'hidden'
        );


        selectedFile.classList.add(
            'hidden'
        );


        selectedFileName.textContent =
            '';

        selectedFileSize.textContent =
            '';


        uploadExcelButton.disabled =
            true;


        importClientError.classList.add(
            'hidden'
        );


        importClientErrorText.textContent =
            '';


        importUploadSection.classList.remove(
            'hidden'
        );


        importPreviewSection.classList.add(
            'hidden'
        );


        backImportButton.classList.add(
            'hidden'
        );


        confirmImportButton.classList.add(
            'hidden'
        );


        confirmImportButton.disabled =
            true;


        previewErrorsSection.classList.add(
            'hidden'
        );


        previewWarning.classList.add(
            'hidden'
        );


        previewNoValid.classList.add(
            'hidden'
        );


        previewErrors.innerHTML =
            '';


        previewValidTable.innerHTML =
            '';


        previewFileName.textContent =
            '';


        previewTotal.textContent =
            '0';


        previewValid.textContent =
            '0';


        previewInvalid.textContent =
            '0';


        previewValidCount.textContent =
            '0 data';


        previewErrorCount.textContent =
            '0';


        setImportUploading(
            false
        );


        setConfirmingImport(
            false
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Modal Open / Close
    |--------------------------------------------------------------------------
    */

    openImportModal.addEventListener(
        'click',
        showImportModal
    );


    closeImportModal.addEventListener(
        'click',
        hideImportModal
    );


    cancelImportModal.addEventListener(
        'click',
        hideImportModal
    );


    importModal.addEventListener(
        'click',
        function (event) {

            if (
                event.target ===
                importModal
            ) {

                hideImportModal();

            }

        }
    );


    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                !importModal.classList.contains('hidden')
            ) {

                hideImportModal();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | File Helpers
    |--------------------------------------------------------------------------
    */

    function formatFileSize(bytes) {

        if (bytes === 0) {

            return '0 Bytes';

        }


        const units = [
            'Bytes',
            'KB',
            'MB',
            'GB'
        ];


        const index =
            Math.floor(
                Math.log(bytes) /
                Math.log(1024)
            );


        return (
            parseFloat(
                (
                    bytes /
                    Math.pow(
                        1024,
                        index
                    )
                ).toFixed(2)
            ) +
            ' ' +
            units[index]
        );

    }


    function escapeHtml(value) {

        const div =
            document.createElement('div');


        div.textContent =
            value ?? '';


        return div.innerHTML;

    }


    /*
    |--------------------------------------------------------------------------
    | Import Error
    |--------------------------------------------------------------------------
    */

    function showImportError(message) {

        importClientErrorText.textContent =
            message;


        importClientError.classList.remove(
            'hidden'
        );

    }


    function hideImportError() {

        importClientError.classList.add(
            'hidden'
        );


        importClientErrorText.textContent =
            '';

    }


    /*
    |--------------------------------------------------------------------------
    | Validate File
    |--------------------------------------------------------------------------
    */

    function validateImportFile(file) {

        if (!file) {

            showImportError(
                'Silakan pilih file Excel terlebih dahulu.'
            );

            return false;

        }


        const fileName =
            file.name.toLowerCase();


        if (
            !fileName.endsWith('.xlsx')
        ) {

            showImportError(
                'File harus berformat .xlsx.'
            );

            return false;

        }


        const maxSize =
            5 * 1024 * 1024;


        if (
            file.size > maxSize
        ) {

            showImportError(
                'Ukuran file maksimal 5 MB.'
            );

            return false;

        }


        hideImportError();


        return true;

    }


    /*
    |--------------------------------------------------------------------------
    | Set Import File
    |--------------------------------------------------------------------------
    */

    function setImportFile(file) {

        if (
            !validateImportFile(file)
        ) {

            return;

        }


        selectedImportFile =
            file;


        selectedFileName.textContent =
            file.name;


        selectedFileSize.textContent =
            formatFileSize(
                file.size
            );


        dropzoneEmpty.classList.add(
            'hidden'
        );


        selectedFile.classList.remove(
            'hidden'
        );


        uploadExcelButton.disabled =
            false;

    }


    /*
    |--------------------------------------------------------------------------
    | File Input
    |--------------------------------------------------------------------------
    */

    excelFile.addEventListener(
        'change',
        function () {

            const file =
                this.files?.[0];


            if (file) {

                setImportFile(
                    file
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Dropzone
    |--------------------------------------------------------------------------
    */

    excelDropzone.addEventListener(
        'click',
        function () {

            excelFile.click();

        }
    );


    changeFileButton.addEventListener(
        'click',
        function (event) {

            event.stopPropagation();

            excelFile.click();

        }
    );


    excelDropzone.addEventListener(
        'dragover',
        function (event) {

            event.preventDefault();


            excelDropzone.classList.add(
                'border-[#0b1f3a]',
                'bg-gray-100'
            );

        }
    );


    excelDropzone.addEventListener(
        'dragleave',
        function () {

            excelDropzone.classList.remove(
                'border-[#0b1f3a]',
                'bg-gray-100'
            );

        }
    );


    excelDropzone.addEventListener(
        'drop',
        function (event) {

            event.preventDefault();


            excelDropzone.classList.remove(
                'border-[#0b1f3a]',
                'bg-gray-100'
            );


            const file =
                event.dataTransfer.files?.[0];


            if (file) {

                setImportFile(
                    file
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Upload Loading
    |--------------------------------------------------------------------------
    */

    function setImportUploading(
        isUploading
    ) {

        uploadExcelButton.disabled =
            isUploading;


        if (isUploading) {

            uploadExcelIcon.classList.add(
                'hidden'
            );

            uploadExcelLoading.classList.remove(
                'hidden'
            );

            uploadExcelText.textContent =
                'Memeriksa...';

        } else {

            uploadExcelIcon.classList.remove(
                'hidden'
            );

            uploadExcelLoading.classList.add(
                'hidden'
            );

            uploadExcelText.textContent =
                'Upload & Periksa';


            uploadExcelButton.disabled =
                !selectedImportFile;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Confirm Loading
    |--------------------------------------------------------------------------
    */

    function setConfirmingImport(
        isConfirming
    ) {

        isConfirmingImport =
            isConfirming;


        if (isConfirming) {

            confirmImportButton.disabled =
                true;


            confirmImportIcon.classList.add(
                'hidden'
            );


            confirmImportLoading.classList.remove(
                'hidden'
            );


            confirmImportText.textContent =
                'Mengimport...';

        } else {

            confirmImportIcon.classList.remove(
                'hidden'
            );


            confirmImportLoading.classList.add(
                'hidden'
            );


            confirmImportText.textContent =
                'Konfirmasi Import';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Upload & Preview
    |--------------------------------------------------------------------------
    */

    uploadExcelButton.addEventListener(
        'click',
        async function () {

            if (!selectedImportFile) {

                showImportError(
                    'Silakan pilih file Excel terlebih dahulu.'
                );

                return;

            }


            if (
                !validateImportFile(
                    selectedImportFile
                )
            ) {

                return;

            }


            hideImportError();


            setImportUploading(
                true
            );


            const formData =
                new FormData();


            formData.append(
                'file',
                selectedImportFile
            );


            try {

                const response =
                    await fetch(
                        "{{ route('superadmin.users.import.preview') }}",
                        {
                            method: 'POST',

                            headers: {

                                'X-CSRF-TOKEN':
                                    csrfToken,

                                'X-Requested-With':
                                    'XMLHttpRequest',

                                'Accept':
                                    'application/json',

                            },

                            body:
                                formData,

                        }
                    );


                let data = null;


                try {

                    data =
                        await response.json();

                } catch (error) {

                    throw new Error(
                        'Server mengembalikan response yang tidak valid.'
                    );

                }


                if (!response.ok) {

                    let message =
                        'File gagal diperiksa.';


                    if (data?.message) {

                        message =
                            data.message;

                    }


                    if (
                        data?.errors?.file?.length
                    ) {

                        message =
                            data.errors.file[0];

                    }


                    throw new Error(
                        message
                    );

                }


                if (!data.success) {

                    throw new Error(
                        data.message ||
                        'File gagal diperiksa.'
                    );

                }


                importPreviewToken =
                    data.token || null;


                renderImportPreview(
                    data
                );


            } catch (error) {

                console.error(
                    'Import Preview Error:',
                    error
                );


                showImportError(
                    error.message ||
                    'Terjadi kesalahan saat memeriksa file.'
                );

            } finally {

                setImportUploading(
                    false
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Render Preview
    |--------------------------------------------------------------------------
    */

    function renderImportPreview(data) {

        importUploadSection.classList.add(
            'hidden'
        );


        importPreviewSection.classList.remove(
            'hidden'
        );


        backImportButton.classList.remove(
            'hidden'
        );


        previewFileName.textContent =
            selectedImportFile?.name ||
            'File Excel';


        previewTotal.textContent =
            data.total ?? 0;


        previewValid.textContent =
            data.valid ?? 0;


        previewInvalid.textContent =
            data.invalid ?? 0;


        previewValidCount.textContent =
            `${data.valid ?? 0} data`;


        /*
        |----------------------------------------------------------------------
        | Errors
        |----------------------------------------------------------------------
        */

        const errors =
            Array.isArray(data.errors)
                ? data.errors
                : [];


        if (errors.length > 0) {

            previewErrorsSection.classList.remove(
                'hidden'
            );


            previewErrorCount.textContent =
                errors.length;


            previewErrors.innerHTML =
                errors
                    .map(function (item) {

                        const row =
                            escapeHtml(
                                item.row
                            );


                        const errorList =
                            Array.isArray(
                                item.errors
                            )
                                ? item.errors
                                : [];


                        const dataRow =
                            item.data || {};


                        return `

                            <div class="border-b border-red-100 px-4 py-3 last:border-b-0">

                                <div>

                                    <p class="text-xs font-bold text-red-700">
                                        Baris Excel ${row}
                                    </p>

                                    <p class="mt-1 text-sm font-medium text-gray-700">
                                        ${escapeHtml(dataRow.name || '-')}
                                    </p>

                                    <p class="text-xs text-gray-500">
                                        NIP: ${escapeHtml(dataRow.nip || '-')}
                                    </p>

                                </div>

                                <ul class="mt-2 space-y-1">

                                    ${
                                        errorList
                                            .map(function (error) {

                                                return `

                                                    <li class="flex gap-2 text-xs text-red-600">

                                                        <span>•</span>

                                                        <span>
                                                            ${escapeHtml(error)}
                                                        </span>

                                                    </li>

                                                `;

                                            })
                                            .join('')
                                    }

                                </ul>

                            </div>

                        `;

                    })
                    .join('');

        } else {

            previewErrorsSection.classList.add(
                'hidden'
            );


            previewErrors.innerHTML =
                '';

        }


        /*
        |----------------------------------------------------------------------
        | Valid Rows
        |----------------------------------------------------------------------
        */

        const validRows =
            Array.isArray(data.valid_rows)
                ? data.valid_rows
                : [];


        if (validRows.length > 0) {

            previewValidTable.innerHTML =
                validRows
                    .map(function (item, index) {

                        const row =
                            item.data || {};


                        const role =
                            String(
                                row.role || ''
                            ).toLowerCase();


                        const status =
                            String(
                                row.status || ''
                            ).toLowerCase();


                        let roleBadge =
                            '';


                        if (
                            role ===
                            'superadmin'
                        ) {

                            roleBadge = `

                                <span class="inline-flex rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700">
                                    Superadmin
                                </span>

                            `;

                        } else if (
                            role ===
                            'admin'
                        ) {

                            roleBadge = `

                                <span class="inline-flex rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    Admin
                                </span>

                            `;

                        } else {

                            roleBadge = `

                                <span class="inline-flex rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">
                                    PJL
                                </span>

                            `;

                        }


                        const statusBadge =
                            status === 'aktif'

                                ? `

                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        Aktif
                                    </span>

                                `

                                : `

                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                        Nonaktif
                                    </span>

                                `;


                        return `

                            <tr class="transition hover:bg-gray-50">

                                <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
                                    ${index + 1}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-700">
                                    ${escapeHtml(row.nip || '-')}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    ${escapeHtml(row.name || '-')}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                    ${escapeHtml(row.email || '-')}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3">
                                    ${roleBadge}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3">
                                    ${statusBadge}
                                </td>

                            </tr>

                        `;

                    })
                    .join('');

        } else {

            previewValidTable.innerHTML = `

                <tr>

                    <td
                        colspan="6"
                        class="px-4 py-8 text-center text-sm text-gray-500"
                    >
                        Tidak ada data valid yang siap diimport.
                    </td>

                </tr>

            `;

        }


        /*
        |----------------------------------------------------------------------
        | Warning
        |----------------------------------------------------------------------
        */

        if (
            Number(data.invalid || 0) > 0
        ) {

            previewWarning.classList.remove(
                'hidden'
            );


            previewWarningText.textContent =
                'Terdapat data yang bermasalah. Data yang valid tetap dapat diproses untuk import, sedangkan data bermasalah tidak akan dimasukkan.';

        } else {

            previewWarning.classList.add(
                'hidden'
            );


            previewWarningText.textContent =
                '';

        }


        /*
        |----------------------------------------------------------------------
        | No Valid Data
        |----------------------------------------------------------------------
        */

        if (
            Number(data.valid || 0) === 0
        ) {

            previewNoValid.classList.remove(
                'hidden'
            );

        } else {

            previewNoValid.classList.add(
                'hidden'
            );

        }


        /*
        |----------------------------------------------------------------------
        | Confirm Button
        |----------------------------------------------------------------------
        */

        confirmImportButton.classList.remove(
            'hidden'
        );


        confirmImportButton.disabled =
            validRows.length === 0;

    }


    /*
    |--------------------------------------------------------------------------
    | Upload File Lain
    |--------------------------------------------------------------------------
    */

    backImportButton.addEventListener(
        'click',
        function () {

            resetImportModal();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Confirm Import
    |--------------------------------------------------------------------------
    */

    confirmImportButton.addEventListener(
        'click',
        async function () {

            if (
                !importPreviewToken
            ) {

                alert(
                    'Preview import tidak ditemukan. Silakan upload file kembali.'
                );

                return;

            }


            if (
                confirmImportButton.disabled
            ) {

                return;

            }


            const validCount =
                Number(
                    previewValid.textContent ||
                    0
                );


            const confirmed =
                confirm(
                    `Anda akan mengimport ${validCount} data pengguna valid. Lanjutkan?`
                );


            if (!confirmed) {

                return;

            }


            setConfirmingImport(
                true
            );


            try {

                const response =
                    await fetch(
                        "{{ route('superadmin.users.import.confirm') }}",
                        {
                            method: 'POST',

                            headers: {

                                'Content-Type':
                                    'application/json',

                                'X-CSRF-TOKEN':
                                    csrfToken,

                                'X-Requested-With':
                                    'XMLHttpRequest',

                                'Accept':
                                    'application/json',

                            },

                            body:
                                JSON.stringify({
                                    token:
                                        importPreviewToken
                                }),

                        }
                    );


                let data = null;


                try {

                    data =
                        await response.json();

                } catch (error) {

                    throw new Error(
                        'Server mengembalikan response yang tidak valid.'
                    );

                }


                if (!response.ok) {

                    throw new Error(
                        data?.message ||
                        'Import gagal dilakukan.'
                    );

                }


                if (!data.success) {

                    throw new Error(
                        data.message ||
                        'Import gagal dilakukan.'
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Import berhasil
                |--------------------------------------------------------------------------
                */

                hideImportModal();


                alert(
                    data.message ||
                    'Data pengguna berhasil diimport.'
                );


                /*
                |--------------------------------------------------------------------------
                | Refresh table
                |--------------------------------------------------------------------------
                */

                await loadUsers(1);


            } catch (error) {

                console.error(
                    'Confirm Import Error:',
                    error
                );


                alert(
                    error.message ||
                    'Terjadi kesalahan saat melakukan import.'
                );

            } finally {

                setConfirmingImport(
                    false
                );

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Initial State
    |--------------------------------------------------------------------------
    */

    updateResetButton();

});

</script>

@endpush