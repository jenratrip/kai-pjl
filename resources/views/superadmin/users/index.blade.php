@extends('layouts.superadmin')

@section('title', 'Manajemen Pengguna')

@section('page-title', 'Manajemen Pengguna')

@section('content')

    {{-- Page Header --}}
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
            <button
                type="button"
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
            </button>

        </div>

    </div>


    {{-- Statistics --}}
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
                    <p class="text-2xl font-bold text-[#1b2b4b]">
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
                    <p class="text-2xl font-bold text-[#1b2b4b]">
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
                    <p class="text-2xl font-bold text-[#1b2b4b]">
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
                    <p class="text-2xl font-bold text-[#1b2b4b]">
                        {{ $totalActive }}
                    </p>

                    <p class="text-sm text-gray-500">
                        User Aktif
                    </p>
                </div>

            </div>

        </div>

    </div>


    {{-- User Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- Filter --}}
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
                        type="text"
                        placeholder="Cari NIP atau nama user..."
                        class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-1 focus:ring-[#0b1f3a]"
                    >

                </div>


                {{-- Filters --}}
                <div class="flex flex-wrap gap-3">

                    <select
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-600 outline-none focus:border-[#0b1f3a]"
                    >
                        <option>Semua Role</option>
                        <option>Superadmin</option>
                        <option>Admin</option>
                        <option>PJL</option>
                    </select>

                    <select
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-600 outline-none focus:border-[#0b1f3a]"
                    >
                        <option>Semua Status</option>
                        <option>Aktif</option>
                        <option>Nonaktif</option>
                    </select>

                </div>

            </div>

        </div>


        {{-- Table --}}
        <div class="overflow-x-auto">

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

                <tbody class="divide-y divide-gray-100">

                    @forelse ($users as $index => $user)

                        <tr class="transition hover:bg-gray-50">

                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">
                                {{ $users->firstItem() + $index }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-[#1b2b4b]">
                                {{ $user->nip }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-[#1b2b4b]">
                                {{ $user->name }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500">
                                {{ $user->email ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">

                                @php
                                    $role = $user->role?->name;
                                @endphp

                                @if ($role === 'superadmin')

                                    <span class="inline-flex rounded-md bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700">
                                        Superadmin
                                    </span>

                                @elseif ($role === 'admin')

                                    <span class="inline-flex rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                        Admin
                                    </span>

                                @else

                                    <span class="inline-flex rounded-md bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-600">
                                        PJL
                                    </span>

                                @endif

                            </td>

                            <td class="whitespace-nowrap px-5 py-4">

                                @if ($user->is_active)

                                    <span class="inline-flex rounded-md bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-600">
                                        Aktif
                                    </span>

                                @else

                                    <span class="inline-flex rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-600">
                                        Nonaktif
                                    </span>

                                @endif

                            </td>

                            <td class="whitespace-nowrap px-5 py-4">

                                <div class="flex justify-center gap-2">

                                    <button
                                        type="button"
                                        class="rounded-lg bg-gray-50 p-2 text-gray-600 transition hover:bg-gray-100"
                                        title="Detail"
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
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6z"
                                            />
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-lg bg-gray-50 p-2 text-gray-600 transition hover:bg-gray-100"
                                        title="Edit"
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
                                                d="M12 20h9"
                                            />

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M16.5 3.5a2.12 2.12 0 013 3L8 18l-4 1 1-4L16.5 3.5z"
                                            />
                                        </svg>
                                    </button>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="px-5 py-12 text-center text-sm text-gray-500"
                            >
                                Belum ada data pengguna.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if ($users->hasPages())

            <div class="border-t border-gray-200 px-5 py-4">

                {{ $users->links() }}

            </div>

        @endif

    </div>

@endsection