@extends('layouts.superadmin')

@section('title', 'Master Data JPL')

@section('page-title', 'Master Data JPL')

@section('content')

    {{-- Notifikasi --}}
    @if (session('success'))
        <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Statistik --}}
    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <p class="text-sm text-gray-500">Total Data JPL</p>
            <p class="mt-2 text-3xl font-bold text-gray-800">
                {{ $totalJpl }}
            </p>
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm">
            <p class="text-sm text-gray-500">Total Resort</p>
            <p class="mt-2 text-3xl font-bold text-gray-800">
                {{ $totalResort }}
            </p>
        </div>

    </div>

    {{-- Pencarian dan Filter --}}
    <div class="mb-6 rounded-xl bg-white p-6 shadow-sm">

        <form
            method="GET"
            action="{{ route('superadmin.jpl.index') }}"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4"
        >

            <div>
                <label for="search" class="mb-2 block text-sm font-medium text-gray-700">
                    Cari JPL
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Kode, nama, atau lokasi"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
            </div>

            <div>
                <label for="resort_id" class="mb-2 block text-sm font-medium text-gray-700">
                    Resort
                </label>

                <select
                    id="resort_id"
                    name="resort_id"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                >
                    <option value="">Semua Resort</option>

                    @foreach ($resorts as $resort)
                        <option
                            value="{{ $resort->id }}"
                            @selected(request('resort_id') == $resort->id)
                        >
                            {{ $resort->nama_resort }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="mb-2 block text-sm font-medium text-gray-700">
                    Status JPL
                </label>

                <select
                    id="status"
                    name="status"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                >
                    <option value="">Semua Status</option>
                    <option value="1" @selected(request('status') === '1')>
                        Aktif
                    </option>
                    <option value="0" @selected(request('status') === '0')>
                        Nonaktif
                    </option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button
                    type="submit"
                    class="rounded-lg bg-[#0b1f3a] px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-900"
                >
                    Cari
                </button>

                <a
                    href="{{ route('superadmin.jpl.index') }}"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50"
                >
                    Reset
                </a>
            </div>

        </form>

    </div>

    {{-- Tabel Master Data JPL --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm">

        <div class="flex flex-col gap-4 border-b border-gray-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h3 class="text-lg font-semibold text-gray-800">
                    Daftar Master Data JPL
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Data perlintasan untuk pemantauan Superadmin.
                </p>
            </div>

        </div>


        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-gray-200">

                <thead class="bg-gray-50">
                    <tr>
                        <th class="whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            No.
                        </th>

                        <th class="whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Kode JPL
                        </th>

                        <th class="whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Nama JPL
                        </th>

                        <th class="whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Resort
                        </th>

                        <th class="whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Wilayah
                        </th>

                        <th class="whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Lokasi
                        </th>

                        <th class="whitespace-nowrap px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>

                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-white">

                    @forelse ($jpls as $jpl)
                        <tr class="transition hover:bg-gray-50">

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ $jpls->firstItem() + $loop->index }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-800">
                                {{ $jpl->kode_jpl ?: '-' }}
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-800">
                                {{ $jpl->nama_jpl }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                {{ $jpl->resort?->nama_resort ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                {{ $jpl->resort?->wilayah?->nama_wilayah ?? '-' }}
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ $jpl->lokasi ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4">
                                @if ($jpl->is_active)
                                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                        </tr>

                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <p class="text-sm font-medium text-gray-600">
                                    Data JPL tidak ditemukan.
                                </p>

                                <p class="mt-1 text-xs text-gray-400">
                                    Coba ubah kata kunci atau filter pencarian.
                                </p>
                            </td>
                        </tr>
                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- Pagination --}}
        <div class="border-t border-gray-200 px-6 py-4">
            {{ $jpls->links() }}
        </div>

    </div>

@endsection
