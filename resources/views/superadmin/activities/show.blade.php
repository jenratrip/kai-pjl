@extends('layouts.superadmin')

@section('title', 'Detail Activity Log')
@section('page-title', 'Detail Activity Log')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">
                Detail Aktivitas
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Informasi lengkap aktivitas yang tercatat.
            </p>
        </div>

        <a
            href="{{ route('superadmin.activities.index') }}"
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
            Kembali
        </a>
    </div>

    <div class="rounded-xl bg-white p-6 shadow-sm">
        <dl class="grid grid-cols-1 gap-5 md:grid-cols-2">

            <div>
                <dt class="text-sm text-gray-500">Pengguna</dt>
                <dd class="mt-1 font-medium text-gray-800">
                    {{ $activity->user?->name ?? 'Pengguna tidak tersedia' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">NIP</dt>
                <dd class="mt-1 font-medium text-gray-800">
                    {{ $activity->user?->nip ?? '-' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">Jenis aktivitas</dt>
                <dd class="mt-1 font-medium text-gray-800">
                    {{ ucfirst($activity->action) }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">Modul</dt>
                <dd class="mt-1 font-medium text-gray-800">
                    {{ $activity->module ?? '-' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">Waktu</dt>
                <dd class="mt-1 font-medium text-gray-800">
                    {{ $activity->created_at?->format('d F Y, H:i:s') ?? '-' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm text-gray-500">Alamat IP</dt>
                <dd class="mt-1 font-medium text-gray-800">
                    {{ $activity->ip_address ?? '-' }}
                </dd>
            </div>

            <div class="md:col-span-2">
                <dt class="text-sm text-gray-500">Deskripsi</dt>
                <dd class="mt-1 text-gray-800">
                    {{ $activity->description ?? '-' }}
                </dd>
            </div>

            <div class="md:col-span-2">
                <dt class="text-sm text-gray-500">User Agent</dt>
                <dd class="mt-1 break-words text-sm text-gray-700">
                    {{ $activity->user_agent ?? '-' }}
                </dd>
            </div>

        </dl>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-gray-800">
                Data Sebelum Perubahan
            </h3>

            <pre class="mt-4 overflow-x-auto whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-4 text-xs text-gray-700">{{ json_encode($activity->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <h3 class="font-semibold text-gray-800">
                Data Setelah Perubahan
            </h3>

            <pre class="mt-4 overflow-x-auto whitespace-pre-wrap break-words rounded-lg bg-gray-50 p-4 text-xs text-gray-700">{{ json_encode($activity->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
        </div>

    </div>

</div>
@endsection