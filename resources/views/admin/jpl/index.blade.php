@extends('layouts.admin')

@section('title', 'Master Data JPL')

@section('content')
<div
    class="p-6"
    x-data="{
        showCreateModal: {{ $errors->any() && old('_form_type') === 'create_jpl' ? 'true' : 'false' }}
    }"
    @keydown.escape.window="showCreateModal = false"
>
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

        <div class="flex flex-wrap items-center gap-2">

            {{-- Tambah JPL Manual --}}
            <button
                type="button"
                @click="showCreateModal = true"
                class="inline-flex items-center gap-2 rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#069b66]"
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
                        d="M12 4v16m8-8H4"
                    />
                </svg>

                Tambah JPL
            </button>

            {{-- Import Excel --}}
            <a
                href="{{ route('admin.jpl.import') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#16233f]"
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
                        d="M12 16V4m0 0L7 9m5-5 5 5M5 14v5h14v-5"
                    />
                </svg>

                Import Excel
            </a>

        </div>
    </div>


    {{-- =========================================================
        NOTIFICATION
    ========================================================== --}}
    @if (session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="mt-0.5 h-5 w-5 shrink-0"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="m5 12 4 4L19 6"
                />
            </svg>

            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif


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

        {{-- FILTER --}}
        <div class="border-b border-gray-200 p-5">

            <form
                method="GET"
                action="{{ route('admin.jpl.index') }}"
            >
                <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">

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
                        <div class="min-w-[220px]">
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
                                <option value="">Semua Resort</option>

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
                                <option value="">Semua Status</option>

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

                    {{-- Filter Actions --}}
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


        {{-- TABLE HEADER --}}
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
                        {{ $jpls->firstItem() }}
                    </span>
                    -
                    <span class="font-semibold text-gray-700">
                        {{ $jpls->lastItem() }}
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
                                @if ($jpl->kode_jpl === 'Liar')
                                    <span class="font-bold text-amber-600">
                                        Liar
                                    </span>
                                @elseif ($jpl->kode_jpl)
                                    <span class="font-bold text-[#1d2d50]">
                                        {{ $jpl->kode_jpl }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            {{-- Nama JPL --}}
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

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">

                                <div class="flex flex-col items-center">

                                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                                        <span class="text-xl font-bold text-gray-400">
                                            J
                                        </span>
                                    </div>

                                    <p class="mt-4 text-sm font-semibold text-gray-700">
                                        Belum ada data JPL
                                    </p>

                                    <p class="mt-1 max-w-md text-xs text-gray-400">
                                        Tambahkan data JPL secara manual atau gunakan
                                        fitur Import Excel untuk memasukkan banyak data sekaligus.
                                    </p>

                                    <div class="mt-4 flex flex-wrap justify-center gap-2">

                                        <button
                                            type="button"
                                            @click="showCreateModal = true"
                                            class="inline-flex items-center gap-2 rounded-lg bg-[#08b878] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#069b66]"
                                        >
                                            + Tambah JPL Manual
                                        </button>

                                        <a
                                            href="{{ route('admin.jpl.import') }}"
                                            class="inline-flex items-center gap-2 rounded-lg bg-[#1d2d50] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#16233f]"
                                        >
                                            ↑ Import Excel
                                        </a>

                                    </div>

                                </div>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- PAGINATION --}}
        @if ($jpls->hasPages())
            <div class="border-t border-gray-200 px-5 py-4">
                {{ $jpls->links() }}
            </div>
        @endif

    </div>


    {{-- =========================================================
        MODAL TAMBAH JPL MANUAL
    ========================================================== --}}
    <div
        x-cloak
        x-show="showCreateModal"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto p-4"
        style="display: none;"
        @click.self="showCreateModal = false"
    >

        {{-- Modal Overlay --}}
        <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

        {{-- Modal Content --}}
        <div
            x-show="showCreateModal"
            x-transition
            class="relative my-auto w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl"
        >

            {{-- Modal Header --}}
            <div class="flex items-center justify-between border-b border-white/10 bg-[#0b1f3a] px-6 py-5">

                <div>
                    <h2 class="text-lg font-bold text-white">
                        Tambah JPL Manual
                    </h2>

                    <p class="mt-1 text-sm text-blue-200">
                        Lengkapi informasi JPL yang akan didaftarkan.
                    </p>
                </div>

                <button
                    type="button"
                    @click="showCreateModal = false"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-white/70 transition hover:bg-white/10 hover:text-white"
                    aria-label="Tutup modal"
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
                            d="M6 18 18 6M6 6l12 12"
                        />
                    </svg>
                </button>

            </div>


            {{-- Form --}}
            <form
                method="POST"
                action="{{ route('admin.jpl.store') }}"
            >
                @csrf

                <input
                    type="hidden"
                    name="_form_type"
                    value="create_jpl"
                >

                <div class="max-h-[70vh] space-y-5 overflow-y-auto p-6">

                    {{-- Kode JPL --}}
                    <div>
                        <label
                            for="modal_kode_jpl"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Kode JPL
                        </label>

                        <input
                            type="text"
                            id="modal_kode_jpl"
                            name="kode_jpl"
                            value="{{ old('_form_type') === 'create_jpl' ? old('kode_jpl') : '' }}"
                            placeholder="Contoh: 112 atau Liar"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                        >

                        @if (old('_form_type') === 'create_jpl')
                            @error('kode_jpl')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>


                    {{-- Nama JPL --}}
                    <div>
                        <label
                            for="modal_nama_jpl"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Nama JPL <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="text"
                            id="modal_nama_jpl"
                            name="nama_jpl"
                            value="{{ old('_form_type') === 'create_jpl' ? old('nama_jpl') : '' }}"
                            required
                            placeholder="Masukkan nama JPL"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                        >

                        @if (old('_form_type') === 'create_jpl')
                            @error('nama_jpl')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>


                    {{-- Lokasi --}}
                    <div>
                        <label
                            for="modal_lokasi"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Lokasi
                        </label>

                        <textarea
                            id="modal_lokasi"
                            name="lokasi"
                            rows="2"
                            placeholder="Masukkan alamat atau keterangan lokasi"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                        >{{ old('_form_type') === 'create_jpl' ? old('lokasi') : '' }}</textarea>

                        @if (old('_form_type') === 'create_jpl')
                            @error('lokasi')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>


                    {{-- Resort --}}
                    <div>
                        <label
                            for="modal_resort_id"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Resort <span class="text-red-500">*</span>
                        </label>

                        <select
                            id="modal_resort_id"
                            name="resort_id"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                        >
                            <option value="">Pilih Resort</option>

                            @foreach ($resorts as $resort)
                                <option
                                    value="{{ $resort->id }}"
                                    @selected(
                                        old('_form_type') === 'create_jpl'
                                        && old('resort_id') == $resort->id
                                    )
                                >
                                    {{ $resort->nama_resort }}

                                    @if ($resort->wilayah)
                                        — {{ $resort->wilayah->nama_wilayah }}
                                    @endif
                                </option>
                            @endforeach

                        </select>

                        @if (old('_form_type') === 'create_jpl')
                            @error('resort_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>


                    {{-- Status --}}
                    <div>
                        <label
                            for="modal_is_active"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Status
                        </label>

                        <select
                            id="modal_is_active"
                            name="is_active"
                            required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                        >
                            <option
                                value="1"
                                @selected(
                                    old('_form_type') !== 'create_jpl'
                                    || old('is_active', '1') == '1'
                                )
                            >
                                Aktif
                            </option>

                            <option
                                value="0"
                                @selected(
                                    old('_form_type') === 'create_jpl'
                                    && old('is_active') === '0'
                                )
                            >
                                Tidak Aktif
                            </option>
                        </select>

                        @if (old('_form_type') === 'create_jpl')
                            @error('is_active')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                </div>


                {{-- Modal Footer --}}
                <div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">

                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-100"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#16233f]"
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
                                d="m5 12 4 4L19 6"
                            />
                        </svg>

                        Simpan JPL
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
@endsection
