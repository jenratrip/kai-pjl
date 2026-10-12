@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-[#1d2d50]">
            Edit Pengguna
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            Perbarui informasi akun PJL.
        </p>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @if ($errors->any())
            <div class="mb-5 rounded-lg bg-red-50 p-4 text-sm text-red-700">
                <p class="font-semibold">Periksa kembali data berikut:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('admin.users.update', $user->id) }}"
            method="POST"
            class="space-y-5"
        >
            @csrf
            @method('PUT')

            <div>
                <label for="nip" class="mb-2 block text-sm font-medium text-gray-700">
                    NIP
                </label>
                <input
                    id="nip"
                    name="nip"
                    type="text"
                    value="{{ old('nip', $user->nip) }}"
                    required
                    maxlength="50"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none"
                >
            </div>

            <div>
                <label for="name" class="mb-2 block text-sm font-medium text-gray-700">
                    Nama Lengkap
                </label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name', $user->name) }}"
                    required
                    maxlength="255"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none"
                >
            </div>

            <div>
                <label for="email" class="mb-2 block text-sm font-medium text-gray-700">
                    Email
                </label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email', $user->email) }}"
                    maxlength="255"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none"
                >
            </div>

            <div>
                <label for="phone" class="mb-2 block text-sm font-medium text-gray-700">
                    Nomor HP
                </label>
                <input
                    id="phone"
                    name="phone"
                    type="text"
                    value="{{ old('phone', $user->phone) }}"
                    maxlength="20"
                    class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-blue-500 focus:outline-none"
                >
            </div>

            <div>
                <label for="role" class="mb-2 block text-sm font-medium text-gray-700">
                    Role Pengguna
                </label>

                <select
                    id="role"
                    name="role"
                    required
                    class="w-full rounded-lg border border-gray-300 px-4 py-3"
                >
                    <option value="pjl"
                        {{ old('role', $user->role->name ?? '') === 'pjl' ? 'selected' : '' }}>
                        PJL
                    </option>

                    <option value="admin"
                        {{ old('role', $user->role->name ?? '') === 'admin' ? 'selected' : '' }}>
                        Admin
                    </option>
                </select>
            </div>

            <div class="flex flex-wrap gap-3 border-t border-gray-100 pt-5">
                <button
                    type="submit"
                    class="rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#293f6b]"
                >
                    Simpan Perubahan
                </button>

                <a
                    href="{{ route('admin.users.show', $user->id) }}"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50"
                >
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection