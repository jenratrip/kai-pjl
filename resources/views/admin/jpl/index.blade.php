@extends('layouts.admin')

@section('title', 'Master Data JPL')

@section('content')

<div class="p-6">

    {{-- =========================================================
        HEADER
    ========================================================== --}}
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">

        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Master Data JPL
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Kelola data lokasi JPL, resort, dan wilayah.
            </p>
        </div>

        <a
            href="{{ route('admin.jpl.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#08b878] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#079f69]"
        >
            <span class="text-lg leading-none">+</span>
            <span>Tambah JPL</span>
        </a>

    </div>


    {{-- =========================================================
        STATISTICS
    ========================================================== --}}
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">

        {{-- Total JPL --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                    <span class="text-lg font-bold text-[#1d2d50]">
                        J
                    </span>
                </div>

                <div>

                    <p class="text-sm text-gray-500">
                        Total JPL
                    </p>

                    <p class="mt-1 text-2xl font-bold text-[#1d2d50]">
                        {{ $totalJpl }}
                    </p>

                </div>

            </div>

        </div>


        {{-- Total Resort --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

            <div class="flex items-center gap-4">

                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                    <span class="text-lg font-bold text-[#08b878]">
                        R
                    </span>
                </div>

                <div>

                    <p class="text-sm text-gray-500">
                        Total Resort
                    </p>

                    <p class="mt-1 text-2xl font-bold text-[#1d2d50]">
                        {{ $totalResort }}
                    </p>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
        MAIN CARD
    ========================================================== --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">


        {{-- =====================================================
            FILTER & ACTION
        ====================================================== --}}
        <div class="border-b border-gray-200 p-5">

            <form
                method="GET"
                action="{{ route('admin.jpl.index') }}"
            >

                <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">

                    {{-- Filters --}}
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">

                        {{-- Search --}}
                        <div class="min-w-[230px]">

                            <label
                                for="search"
                                class="mb-1.5 block text-xs font-semibold text-gray-600"
                            >
                                Cari JPL
                            </label>

                            <input
                                id="search"
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Cari No JPL / Nama JPL..."
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                        </div>


                        {{-- Resort --}}
                        <div class="min-w-[200px]">

                            <label
                                for="resort_id"
                                class="mb-1.5 block text-xs font-semibold text-gray-600"
                            >
                                Resort
                            </label>

                            <select
                                id="resort_id"
                                name="resort_id"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-700 outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                            >

                                <option value="">
                                    Semua Resort
                                </option>

                                @foreach ($resorts as $resort)

                                    <option
                                        value="{{ $resort->id }}"
                                        @selected(request('resort_id') == $resort->id)
                                    >
                                        {{ $resort->nama_resort }}

                                        @if ($resort->wilayah)
                                            — {{ $resort->wilayah->nama_wilayah }}
                                        @endif
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Status --}}
                        <div class="min-w-[170px]">

                            <label
                                for="status"
                                class="mb-1.5 block text-xs font-semibold text-gray-600"
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
                                    value="1"
                                    @selected(request('status') === '1')
                                >
                                    Aktif
                                </option>

                                <option
                                    value="0"
                                    @selected(request('status') === '0')
                                >
                                    Tidak Aktif
                                </option>

                            </select>

                        </div>

                    </div>


                    {{-- Filter Buttons --}}
                    <div class="flex items-center gap-2">

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#16233f]"
                        >
                            Cari
                        </button>

                        <a
                            href="{{ route('admin.jpl.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
                        >
                            Reset
                        </a>

                    </div>

                </div>

            </form>

        </div>


        {{-- =====================================================
            TABLE HEADER
        ====================================================== --}}
        <div class="flex flex-col justify-between gap-2 px-5 py-4 md:flex-row md:items-center">

            <div>

                <h2 class="text-base font-bold text-[#1d2d50]">
                    Daftar JPL
                </h2>

                <p class="mt-0.5 text-xs text-gray-500">
                    Data lokasi JPL yang terdaftar dalam sistem.
                </p>

            </div>

            <div class="text-xs text-gray-500">

                @if ($jpls->total() > 0)

                    Menampilkan
                    <span class="font-semibold text-gray-700">
                        {{ $jpls->firstItem() }}–{{ $jpls->lastItem() }}
                    </span>
                    dari
                    <span class="font-semibold text-gray-700">
                        {{ $jpls->total() }}
                    </span>
                    data

                @else

                    Belum ada data

                @endif

            </div>

        </div>


        {{-- =====================================================
            TABLE
        ====================================================== --}}
        <div class="overflow-x-auto">

            <table class="min-w-full">

                <thead>

                    <tr class="border-y border-gray-200 bg-[#f7f8fa]">

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">
                            No
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">
                            No JPL
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">
                            Nama JPL
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">
                            Resort
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">
                            Wilayah
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-bold uppercase tracking-wide text-gray-600">
                            Status
                        </th>

                        <th class="whitespace-nowrap px-5 py-3 text-center text-xs font-bold uppercase tracking-wide text-gray-600">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100">

                    @forelse ($jpls as $index => $jpl)

                        <tr class="transition hover:bg-gray-50">


                            {{-- Nomor --}}
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-500">
                                {{ $jpls->firstItem() + $index }}
                            </td>


                            {{-- Kode JPL --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                <span class="font-bold text-[#1d2d50]">
                                    {{ $jpl->kode_jpl }}
                                </span>

                            </td>


                            {{-- Nama --}}
                            <td class="px-5 py-4">

                                <div class="font-semibold text-gray-800">
                                    {{ $jpl->nama_jpl }}
                                </div>

                                @if ($jpl->lokasi)

                                    <div class="mt-0.5 text-xs text-gray-400">
                                        {{ $jpl->lokasi }}
                                    </div>

                                @endif

                            </td>


                            {{-- Resort --}}
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">

                                {{ $jpl->resort?->nama_resort ?? '-' }}

                            </td>


                            {{-- Wilayah --}}
                            <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-600">

                                {{ $jpl->resort?->wilayah?->nama_wilayah ?? '-' }}

                            </td>


                            {{-- Status --}}
                            <td class="whitespace-nowrap px-5 py-4 text-center">

                                @if ($jpl->is_active)

                                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-600">
                                        Aktif
                                    </span>

                                @else

                                    <span class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-500">
                                        Non-Aktif
                                    </span>

                                @endif

                            </td>


                            {{-- Aksi --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                <div class="flex items-center justify-center gap-2">


                                    {{-- Edit --}}
                                    <a
                                        href="{{ route('admin.jpl.edit', $jpl) }}"
                                        title="Edit JPL"
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 text-[#1d2d50] transition hover:bg-gray-200"
                                    >
                                        <span class="text-sm">
                                            ✎
                                        </span>
                                    </a>


                                    {{-- Hapus --}}
                                    <form
                                        action="{{ route('admin.jpl.destroy', $jpl) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus JPL {{ $jpl->kode_jpl }}?')"
                                    >

                                        @csrf

                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            title="Hapus JPL"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-500 transition hover:bg-red-100"
                                        >
                                            <span class="text-sm">
                                                🗑
                                            </span>
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="px-5 py-16 text-center"
                            >

                                <div class="flex flex-col items-center">

                                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                        <span class="text-xl text-gray-400">
                                            J
                                        </span>
                                    </div>

                                    <p class="mt-4 text-sm font-semibold text-gray-700">
                                        Belum ada data JPL
                                    </p>

                                    <p class="mt-1 text-xs text-gray-400">
                                        Silakan tambahkan data JPL untuk mulai menggunakan modul ini.
                                    </p>

                                    <a
                                        href="{{ route('admin.jpl.create') }}"
                                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-[#08b878] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#079f69]"
                                    >
                                        <span class="text-lg leading-none">+</span>
                                        Tambah JPL
                                    </a>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- =====================================================
            PAGINATION
        ====================================================== --}}
        @if ($jpls->hasPages())

            <div class="border-t border-gray-200 px-5 py-4">

                {{ $jpls->links() }}

            </div>

        @endif

    </div>

</div>

@endsection