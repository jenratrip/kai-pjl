@extends('layouts.superadmin')

@section('title', 'Edit User')
@section('page-title', 'Edit User')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">

        <a
            href="{{ route('superadmin.users.show', $user) }}"
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
                Edit User
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Perbarui informasi dan hak akses pengguna
            </p>
        </div>

    </div>


    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">

            <div class="flex gap-3">

                <svg
                    class="mt-0.5 h-5 w-5 shrink-0 text-red-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4m0 4h.01M10.29 3.86l-7.82 13.5A2 2 0 004.2 20h15.6a2 2 0 001.73-2.64l-7.82-13.5a2 2 0 00-3.42 0z"
                    />
                </svg>

                <div>
                    <p class="text-sm font-semibold text-red-800">
                        Terdapat kesalahan:
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>

            </div>

        </div>
    @endif


    {{-- Form --}}
    <form
        action="{{ route('superadmin.users.update', $user) }}"
        method="POST"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        {{-- Data Personal --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-5">
                <h2 class="text-base font-bold text-gray-900">
                    Informasi Pengguna
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Data identitas pengguna sistem.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">

                {{-- NIP --}}
                <div>
                    <label
                        for="nip"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        NIP <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="nip"
                        name="nip"
                        value="{{ old('nip', $user->nip) }}"
                        required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-2 focus:ring-[#0b1f3a]/10"
                    >
                </div>


                {{-- Nama --}}
                <div>
                    <label
                        for="name"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Nama Lengkap <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-2 focus:ring-[#0b1f3a]/10"
                    >
                </div>


                {{-- Email --}}
                <div>
                    <label
                        for="email"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-2 focus:ring-[#0b1f3a]/10"
                    >
                </div>


                {{-- Phone --}}
                <div>
                    <label
                        for="phone"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        No. HP
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        value="{{ old('phone', $user->phone) }}"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-2 focus:ring-[#0b1f3a]/10"
                    >
                </div>

            </div>
        </div>


        {{-- Role & Status --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-5">
                <h2 class="text-base font-bold text-gray-900">
                    Akses & Status
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Atur role dan status akun pengguna.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">

                {{-- Role --}}
                <div>
                    <label
                        for="role_id"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Role <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="role_id"
                        name="role_id"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-2 focus:ring-[#0b1f3a]/10"
                    >
                        @foreach($roles as $role)
                            <option
                                value="{{ $role->id }}"
                                @selected(old('role_id', $user->role_id) == $role->id)
                            >
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>


                {{-- Status --}}
                <div>
                    <label
                        for="is_active"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Status <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="is_active"
                        name="is_active"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-2 focus:ring-[#0b1f3a]/10"
                    >
                        <option
                            value="1"
                            @selected(old('is_active', $user->is_active) == 1)
                        >
                            Aktif
                        </option>

                        <option
                            value="0"
                            @selected(old('is_active', $user->is_active) == 0)
                        >
                            Tidak Aktif
                        </option>
                    </select>
                </div>

            </div>
        </div>


        {{-- Password --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-5">
                <h2 class="text-base font-bold text-gray-900">
                    Password
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Kosongkan jika password tidak ingin diubah.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">

                {{-- Password --}}
                <div>
                    <label
                        for="password"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Password Baru
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-2 focus:ring-[#0b1f3a]/10"
                    >

                    <p class="mt-1.5 text-xs text-gray-500">
                        Minimal 8 karakter.
                    </p>
                </div>


                {{-- Confirmation --}}
                <div>
                    <label
                        for="password_confirmation"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Konfirmasi Password
                    </label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-[#0b1f3a] focus:ring-2 focus:ring-[#0b1f3a]/10"
                    >
                </div>

            </div>
        </div>


        {{-- Buttons --}}
        <div class="flex items-center justify-end gap-3">

            <a
                href="{{ route('superadmin.users.show', $user) }}"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Batal
            </a>

            <button
                type="submit"
                class="rounded-lg bg-[#0b1f3a] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50]"
            >
                Simpan Perubahan
            </button>

        </div>

    </form>

</div>

@endsection