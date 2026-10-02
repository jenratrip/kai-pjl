@extends('layouts.superadmin')

@section('title', 'Detail User')
@section('page-title', 'Detail User')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('superadmin.users.index') }}"
                    class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </a>

                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Detail User
                    </h1>

                    <p class="mt-1 text-sm text-gray-500">
                        Informasi lengkap pengguna sistem PJL
                    </p>
                </div>
            </div>
        </div>

        <a
            href="{{ route('superadmin.users.edit', $user) }}"
            class="inline-flex items-center gap-2 rounded-lg bg-[#0b1f3a] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50]"
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
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
                />
            </svg>

            Edit User
        </a>
    </div>


    {{-- Profile --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- Profile Header --}}
        <div class="border-b border-gray-200 bg-gray-50 px-6 py-6">
            <div class="flex items-center gap-5">

                {{-- Avatar --}}
                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-[#0b1f3a] text-2xl font-bold text-white">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>

                <div class="min-w-0">
                    <h2 class="text-xl font-bold text-gray-900">
                        {{ $user->name }}
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        NIP {{ $user->nip }}
                    </p>

                    <div class="mt-3">
                        @if($user->is_active)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                Tidak Aktif
                            </span>
                        @endif
                    </div>
                </div>

            </div>
        </div>


        {{-- Information --}}
        <div class="p-6">

            <h3 class="mb-5 text-base font-bold text-gray-900">
                Informasi Pengguna
            </h3>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                {{-- NIP --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        NIP
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $user->nip }}
                    </p>
                </div>

                {{-- Nama --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Nama Lengkap
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $user->name }}
                    </p>
                </div>

                {{-- Email --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Email
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $user->email ?: '-' }}
                    </p>
                </div>

                {{-- Phone --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        No. HP
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $user->phone ?: '-' }}
                    </p>
                </div>

                {{-- Role --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Role
                    </p>

                    <div class="mt-2">
                        @php
                            $roleLabel = match($user->role?->name) {
                                'superadmin' => 'Superadmin',
                                'admin' => 'Admin',
                                'pjl' => 'PJL',
                                default => '-',
                            };
                        @endphp

                        <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                            {{ $roleLabel }}
                        </span>
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Status
                    </p>

                    <div class="mt-2">
                        @if($user->is_active)
                            <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                Tidak Aktif
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Created --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Dibuat
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $user->created_at?->format('d M Y, H:i') ?? '-' }}
                    </p>
                </div>

                {{-- Updated --}}
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                        Diperbarui
                    </p>

                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $user->updated_at?->format('d M Y, H:i') ?? '-' }}
                    </p>
                </div>

            </div>
        </div>

    </div>


    {{-- Account Status --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

        <h3 class="text-base font-bold text-gray-900">
            Status Akun
        </h3>

        <p class="mt-1 text-sm text-gray-500">
            Pengaturan akses pengguna ke dalam sistem.
        </p>

        <div class="mt-5 flex items-center justify-between rounded-lg border border-gray-200 p-4">

            <div>
                <p class="text-sm font-semibold text-gray-900">
                    {{ $user->is_active ? 'Akun Aktif' : 'Akun Tidak Aktif' }}
                </p>

                <p class="mt-1 text-xs text-gray-500">
                    {{ $user->is_active
                        ? 'User dapat login dan menggunakan sistem.'
                        : 'User tidak dapat login ke dalam sistem.'
                    }}
                </p>
            </div>

            @if(auth()->id() !== $user->id)

                <form
                    action="{{ route('superadmin.users.toggle-status', $user) }}"
                    method="POST"
                    onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }} user ini?')"
                >
                    @csrf
                    @method('PATCH')

                    @if($user->is_active)
                        <button
                            type="submit"
                            class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-600 transition hover:bg-red-50"
                        >
                            Nonaktifkan
                        </button>
                    @else
                        <button
                            type="submit"
                            class="rounded-lg border border-green-200 px-4 py-2 text-sm font-semibold text-green-600 transition hover:bg-green-50"
                        >
                            Aktifkan
                        </button>
                    @endif
                </form>

            @else

                <span class="text-xs text-gray-400">
                    Akun yang sedang digunakan
                </span>

            @endif

        </div>

    </div>

</div>

@endsection