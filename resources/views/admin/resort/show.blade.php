@extends('layouts.admin')

@section('title', 'Detail Resort')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Detail Resort
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Informasi lengkap resort dan JPL yang berada di bawahnya.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a
                href="{{ route('admin.resort.index') }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M15 19l-7-7 7-7" />
                </svg>
                Kembali
            </a>

            <a
                href="{{ route('admin.resort.edit', $resort) }}"
                class="inline-flex items-center gap-2 rounded-lg bg-[#f58220] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#d96f12]"
            >
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
                </svg>
                Edit Resort
            </a>
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Status Resort</p>
            <div class="mt-3">
                @if ($resort->is_active)
                    <span class="inline-flex rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                        Aktif
                    </span>
                @else
                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                        Nonaktif
                    </span>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Wilayah Operasional</p>
            <p class="mt-3 text-lg font-bold text-[#1d2d50]">
                {{ $resort->wilayah?->nama_wilayah ?? '-' }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Total JPL</p>
            <p class="mt-3 text-2xl font-bold text-[#1d2d50]">
                {{ $resort->jpl->count() }}
            </p>
        </div>

    </div>

    {{-- Informasi Resort --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="mb-5 border-b border-gray-100 pb-4">
            <h2 class="text-lg font-semibold text-[#1d2d50]">
                Informasi Resort
            </h2>
        </div>

        <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">

            <div>
                <dt class="text-sm font-medium text-gray-500">
                    Nama Resort
                </dt>
                <dd class="mt-1 text-base font-semibold text-gray-800">
                    {{ $resort->nama_resort }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">
                    Wilayah
                </dt>
                <dd class="mt-1 text-base text-gray-800">
                    {{ $resort->wilayah?->nama_wilayah ?? '-' }}
                </dd>
            </div>

            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">
                    Keterangan
                </dt>
                <dd class="mt-1 whitespace-pre-line text-sm leading-6 text-gray-700">
                    {{ filled($resort->keterangan) ? $resort->keterangan : 'Tidak ada keterangan.' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">
                    Dibuat Pada
                </dt>
                <dd class="mt-1 text-sm text-gray-700">
                    {{ $resort->created_at?->format('d-m-Y H:i') ?? '-' }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500">
                    Terakhir Diperbarui
                </dt>
                <dd class="mt-1 text-sm text-gray-700">
                    {{ $resort->updated_at?->format('d-m-Y H:i') ?? '-' }}
                </dd>
            </div>

        </dl>
    </div>

    {{-- Daftar JPL --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="flex flex-col gap-2 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-[#1d2d50]">
                    Daftar JPL
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Data JPL yang terhubung dengan resort ini.
                </p>
            </div>

            <span class="inline-flex w-fit rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                {{ $resort->jpl->count() }} JPL
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">

                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            No
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Kode JPL
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Nama JPL
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Lokasi
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse ($resort->jpl as $index => $jpl)
                        <tr class="transition hover:bg-gray-50">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ $index + 1 }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-[#1d2d50]">
                                {{ $jpl->kode_jpl ?? '-' }}
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-700">
                                {{ $jpl->nama_jpl ?? '-' }}
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $jpl->lokasi ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                @if ($jpl->is_active)
                                    <span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <p class="text-sm font-semibold text-gray-700">
                                    Belum ada JPL
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    Belum ada data JPL yang terhubung dengan resort ini.
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>

    </div>

</div>
@endsection
