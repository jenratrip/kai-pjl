@extends('layouts.admin')

@section('title', 'Data Resort')

@section('content')

<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Data Resort
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Kelola data resort berdasarkan wilayah operasional.
            </p>
        </div>

        <a
            href="{{ route('admin.resort.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#f58220] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d96f12]"
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
                    d="M12 4v16m8-8H4"
                />
            </svg>

            Tambah Resort
        </a>

    </div>


    {{-- Flash Message --}}
    @if(session('success'))
        <div
            class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
        >
            {{ session('success') }}
        </div>
    @endif


    @if(session('error'))
        <div
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ session('error') }}
        </div>
    @endif


    {{-- Statistics --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

        {{-- Total Resort --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Total Resort
                    </p>

                    <p class="mt-2 text-2xl font-bold text-[#1d2d50]">
                        {{ $totalResort }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6"
                        />
                    </svg>
                </div>

            </div>
        </div>


        {{-- Resort Aktif --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Resort Aktif
                    </p>

                    <p class="mt-2 text-2xl font-bold text-[#1d2d50]">
                        {{ $totalAktif }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-green-50 text-green-600">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                </div>

            </div>
        </div>


        {{-- Total JPL --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">

                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Total JPL
                    </p>

                    <p class="mt-2 text-2xl font-bold text-[#1d2d50]">
                        {{ $totalJpl }}
                    </p>
                </div>

                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-orange-50 text-[#f58220]">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h7l5 5v11a2 2 0 01-2 2z"
                        />
                    </svg>
                </div>

            </div>
        </div>

    </div>


    {{-- Filter --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

        <form
            method="GET"
            action="{{ route('admin.resort.index') }}"
            class="grid grid-cols-1 gap-4 md:grid-cols-4"
        >

            {{-- Search --}}
            <div class="md:col-span-2">

                <label
                    for="search"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Cari Resort
                </label>

                <div class="relative">

                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-5 w-5 text-gray-400"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nama resort atau wilayah..."
                        class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-700 outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                    >

                </div>

            </div>


            {{-- Wilayah --}}
            <div>

                <label
                    for="wilayah_id"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Wilayah
                </label>

                <select
                    id="wilayah_id"
                    name="wilayah_id"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                >

                    <option value="">
                        Semua Wilayah
                    </option>

                    @foreach($wilayahs as $wilayah)

                        <option
                            value="{{ $wilayah->id }}"
                            @selected(request('wilayah_id') == $wilayah->id)
                        >
                            {{ $wilayah->nama_wilayah }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Status --}}
            <div>

                <label
                    for="status"
                    class="mb-1.5 block text-sm font-medium text-gray-700"
                >
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                >

                    <option value="">
                        Semua Status
                    </option>

                    <option
                        value="aktif"
                        @selected(request('status') === 'aktif')
                    >
                        Aktif
                    </option>

                    <option
                        value="nonaktif"
                        @selected(request('status') === 'nonaktif')
                    >
                        Nonaktif
                    </option>

                </select>

            </div>


            {{-- Buttons --}}
            <div class="flex items-end gap-2 md:col-span-4">

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#15233f]"
                >
                    Filter
                </button>

                <a
                    href="{{ route('admin.resort.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>


    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-gray-200">

                <thead class="bg-gray-50">

                    <tr>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            No
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Resort
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Wilayah
                        </th>

                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            JPL
                        </th>

                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>

                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100">

                    @forelse($resorts as $index => $resort)

                        <tr class="transition hover:bg-gray-50">

                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ $resorts->firstItem() + $index }}
                            </td>


                            <td class="px-6 py-4">

                                <div class="font-semibold text-[#1d2d50]">
                                    {{ $resort->nama_resort }}
                                </div>

                                @if($resort->keterangan)
                                    <div class="mt-1 max-w-xs truncate text-xs text-gray-500">
                                        {{ $resort->keterangan }}
                                    </div>
                                @endif

                            </td>


                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">

                                {{ $resort->wilayah?->nama_wilayah ?? '-' }}

                            </td>


                            <td class="whitespace-nowrap px-6 py-4 text-center">

                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    {{ $resort->jpl_count }}
                                </span>

                            </td>


                            <td class="whitespace-nowrap px-6 py-4 text-center">

                                @if($resort->is_active)

                                    <span class="inline-flex rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        Aktif
                                    </span>

                                @else

                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                        Nonaktif
                                    </span>

                                @endif

                            </td>


                            <td class="whitespace-nowrap px-6 py-4">

                                <div class="flex items-center justify-end gap-2">

                                    {{-- Detail --}}
                                    <a
                                        href="{{ route('admin.resort.show', $resort) }}"
                                        title="Detail"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                    </a>


                                    {{-- Edit --}}
                                    <a
                                        href="{{ route('admin.resort.edit', $resort) }}"
                                        title="Edit"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:border-orange-200 hover:bg-orange-50 hover:text-[#f58220]"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"
                                            />
                                        </svg>
                                    </a>


                                    {{-- Toggle Status --}}
                                    <form
                                        method="POST"
                                        action="{{ route('admin.resort.toggle-status', $resort) }}"
                                    >

                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            title="{{ $resort->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:border-green-200 hover:bg-green-50 hover:text-green-600"
                                        >

                                            @if($resort->is_active)

                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>

                                            @else

                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M12 4v16m8-8H4"
                                                    />
                                                </svg>

                                            @endif

                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="px-6 py-14 text-center"
                            >

                                <div class="flex flex-col items-center">

                                    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">

                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-7 w-7 text-gray-400"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M3 21h18M5 21V7l7-4 7 4v14"
                                            />
                                        </svg>

                                    </div>

                                    <h3 class="text-sm font-semibold text-gray-700">
                                        Belum ada data resort
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-500">
                                        Tambahkan resort pertama untuk mulai mengelola data.
                                    </p>

                                    <a
                                        href="{{ route('admin.resort.create') }}"
                                        class="mt-4 inline-flex items-center rounded-lg bg-[#f58220] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#d96f12]"
                                    >
                                        Tambah Resort
                                    </a>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if($resorts->hasPages())

            <div class="border-t border-gray-200 px-6 py-4">
                {{ $resorts->links() }}
            </div>

        @endif

    </div>

</div>

@endsection