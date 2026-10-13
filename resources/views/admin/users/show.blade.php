@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Detail Pengguna
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Informasi lengkap akun PJL.
            </p>
        </div>

        <a
            href="{{ route('admin.users.index') }}"
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
        >
            Kembali
        </a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <p class="text-sm text-gray-500">NIP</p>
                <p class="mt-1 font-semibold text-gray-800">
                    {{ $user->nip }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Nama Lengkap</p>
                <p class="mt-1 font-semibold text-gray-800">
                    {{ $user->name }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Email</p>
                <p class="mt-1 font-semibold text-gray-800">
                    {{ $user->email ?: '-' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Nomor HP</p>
                <p class="mt-1 font-semibold text-gray-800">
                    {{ $user->phone ?: '-' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Role</p>
                <p class="mt-1 font-semibold text-gray-800">
                    {{ strtoupper($user->role->name ?? 'PJL') }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">Status Akun</p>
                <p class="mt-1">
                    @if ($user->is_active)
                        <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                            Aktif
                        </span>
                    @else
                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                            Nonaktif
                        </span>
                    @endif
                </p>
            </div>
        </div>

        <div class="mt-8 border-t border-gray-100 pt-5">
            <a
                href="{{ route('admin.users.edit', $user->id) }}"
                class="inline-flex rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#293f6b]"
            >
                Edit Pengguna
            </a>
        </div>
    </div>
</div>
@endsection