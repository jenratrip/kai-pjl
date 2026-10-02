@extends('layouts.superadmin')

@section('title', 'Tambah Pengguna')

@section('page-title', 'Tambah Pengguna')

@section('content')

<div class="max-w-5xl mx-auto">

    {{-- Page Header --}}
    <div class="mb-6">

        <div class="flex items-center gap-3">

            <a
                href="{{ route('superadmin.users.index') }}"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-[#0b1f3a]"
                title="Kembali"
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
                        d="M15 19l-7-7 7-7"
                    />
                </svg>
            </a>

            <div>
                <h1 class="text-2xl font-bold text-[#1b2b4b]">
                    Tambah Pengguna
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Tambahkan pengguna baru dan tentukan hak aksesnya.
                </p>
            </div>

        </div>

    </div>


    {{-- Validation Error --}}
    @if ($errors->any())

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

            <div class="flex gap-3">

                <div class="flex-shrink-0">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-red-500"
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

                </div>

                <div>

                    <h3 class="text-sm font-semibold text-red-700">
                        Terdapat kesalahan pada data yang dimasukkan.
                    </h3>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            </div>

        </div>

    @endif


    {{-- Form --}}
    <form
        action="{{ route('superadmin.users.store') }}"
        method="POST"
    >

        @csrf


        {{-- Informasi Pengguna --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-5">

                <div class="flex items-center gap-3">

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600">

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
                                d="M15 19a6 6 0 00-12 0"
                            />

                            <circle
                                cx="9"
                                cy="7"
                                r="4"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19 8v6"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16 11h6"
                            />
                        </svg>

                    </div>

                    <div>

                        <h2 class="text-lg font-semibold text-[#1b2b4b]">
                            Informasi Pengguna
                        </h2>

                        <p class="mt-0.5 text-sm text-gray-500">
                            Masukkan informasi dasar pengguna.
                        </p>

                    </div>

                </div>

            </div>


            <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">

                {{-- NIP --}}
                <div>

                    <label
                        for="nip"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        NIP
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="nip"
                        name="nip"
                        value="{{ old('nip') }}"
                        placeholder="Masukkan NIP"
                        required
                        autofocus
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#0b1f3a] focus:ring-2 focus:ring-blue-100"
                    >

                    @error('nip')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Nama Lengkap --}}
                <div>

                    <label
                        for="name"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Nama Lengkap
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Masukkan nama lengkap"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#0b1f3a] focus:ring-2 focus:ring-blue-100"
                    >

                    @error('name')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

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
                        value="{{ old('email') }}"
                        placeholder="contoh@email.com"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#0b1f3a] focus:ring-2 focus:ring-blue-100"
                    >

                    @error('email')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- No HP --}}
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
                        value="{{ old('phone') }}"
                        placeholder="08xxxxxxxxxx"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#0b1f3a] focus:ring-2 focus:ring-blue-100"
                    >

                    @error('phone')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Role --}}
                <div>

                    <label
                        for="role_id"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Role
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="role_id"
                        name="role_id"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#0b1f3a] focus:ring-2 focus:ring-blue-100"
                    >

                        <option value="">
                            Pilih Role
                        </option>

                        @foreach ($roles as $role)

                            <option
                                value="{{ $role->id }}"
                                @selected(old('role_id') == $role->id)
                            >
                                {{ ucfirst($role->name) }}
                            </option>

                        @endforeach

                    </select>

                    @error('role_id')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Status --}}
                <div>

                    <label
                        for="is_active"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Status
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="is_active"
                        name="is_active"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition focus:border-[#0b1f3a] focus:ring-2 focus:ring-blue-100"
                    >

                        <option
                            value="1"
                            @selected(old('is_active', '1') == '1')
                        >
                            Aktif
                        </option>

                        <option
                            value="0"
                            @selected(old('is_active') === '0')
                        >
                            Nonaktif
                        </option>

                    </select>

                    @error('is_active')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>

        </div>


        {{-- Keamanan Akun --}}
        <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

            <div class="border-b border-gray-200 px-6 py-5">

                <div class="flex items-center gap-3">

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50 text-orange-500">

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <rect
                                x="3"
                                y="11"
                                width="18"
                                height="10"
                                rx="2"
                            />

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M7 11V7a5 5 0 0110 0v4"
                            />
                        </svg>

                    </div>

                    <div>

                        <h2 class="text-lg font-semibold text-[#1b2b4b]">
                            Keamanan Akun
                        </h2>

                        <p class="mt-0.5 text-sm text-gray-500">
                            Tentukan password untuk akun pengguna.
                        </p>

                    </div>

                </div>

            </div>


            <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">

                {{-- Password --}}
                <div>

                    <label
                        for="password"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Password
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimal 8 karakter"
                        minlength="8"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#0b1f3a] focus:ring-2 focus:ring-blue-100"
                    >

                    @error('password')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- Konfirmasi Password --}}
                <div>

                    <label
                        for="password_confirmation"
                        class="mb-2 block text-sm font-semibold text-gray-700"
                    >
                        Konfirmasi Password
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        placeholder="Ulangi password"
                        minlength="8"
                        required
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 outline-none transition placeholder:text-gray-400 focus:border-[#0b1f3a] focus:ring-2 focus:ring-blue-100"
                    >

                </div>

            </div>

        </div>


        {{-- Action --}}
        <div class="mt-6 flex items-center justify-end gap-3">

            <a
                href="{{ route('superadmin.users.index') }}"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                Batal
            </a>

            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-[#0b1f3a] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#142d50]"
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
                        d="M5 12l4 4L19 6"
                    />
                </svg>

                Simpan Pengguna

            </button>

        </div>

    </form>

</div>

@endsection