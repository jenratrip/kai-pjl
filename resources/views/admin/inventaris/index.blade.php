@extends('layouts.admin')

@section('title', 'Master Inventaris')

@section('content')

<div class="px-6 py-6">

    {{-- =========================================================
        HEADER HALAMAN
    ========================================================== --}}
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">

        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Master Inventaris
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Kelola data barang dan penempatan inventaris pada setiap JPL.
            </p>
        </div>

        <a
            href="{{ route('admin.inventaris.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#14213d]"
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
                    d="M12 5v14M5 12h14"
                />
            </svg>

            Tambah Inventaris
        </a>

    </div>


    {{-- =========================================================
        NOTIFIKASI
    ========================================================== --}}
    @if (session('success'))
        <div class="mb-5 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="mt-0.5 h-5 w-5 shrink-0"
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

            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="mb-2 font-semibold">
                Terdapat kesalahan:
            </p>

            <ul class="list-inside list-disc space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- =========================================================
        STATISTIK INVENTARIS
    ========================================================== --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

        {{-- Total Barang --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-[#1d2d50]">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M4 7h16M6 7v12h12V7M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                        />
                    </svg>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Total Jenis Inventaris
                    </p>

                    <p class="mt-1 text-2xl font-bold text-[#1d2d50]">
                        {{ number_format($inventaris->total()) }}
                    </p>
                </div>

            </div>
        </div>


        {{-- Inventaris Aktif --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-green-50 text-green-600">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
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
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Inventaris Aktif
                    </p>

                    <p class="mt-1 text-2xl font-bold text-green-600">
                        {{ number_format($inventaris->getCollection()->where('is_active', true)->count()) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-400">
                        Pada halaman ini
                    </p>
                </div>

            </div>
        </div>


        {{-- Total Unit --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-center gap-4">

                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-yellow-50 text-[#d99a00]">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="1.8"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M8 7h8M8 12h8M8 17h5M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"
                        />
                    </svg>
                </div>

                <div>
                    <p class="text-sm font-medium text-gray-500">
                        Unit pada Halaman Ini
                    </p>

                    <p class="mt-1 text-2xl font-bold text-[#1d2d50]">
                        {{ number_format($inventaris->getCollection()->sum('jumlah')) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-400">
                        Akumulasi jumlah barang
                    </p>
                </div>

            </div>
        </div>

    </div>


    {{-- =========================================================
        TABEL MASTER INVENTARIS
    ========================================================== --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        {{-- Header Tabel --}}
        <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-base font-bold text-[#1d2d50]">
                    Daftar Inventaris
                </h2>

                <p class="mt-1 text-xs text-gray-500">
                    Data barang yang digunakan dalam operasional PJL.
                </p>
            </div>

            <div class="text-sm text-gray-500">
                Halaman
                <span class="font-semibold text-[#1d2d50]">
                    {{ $inventaris->currentPage() }}
                </span>
                dari
                <span class="font-semibold text-[#1d2d50]">
                    {{ max($inventaris->lastPage(), 1) }}
                </span>
            </div>

        </div>


        {{-- Isi Tabel --}}
        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-gray-200">

                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Barang
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Kategori
                        </th>

                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Lokasi / JPL
                        </th>

                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Jumlah
                        </th>

                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>

                        <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Aksi
                        </th>
                    </tr>
                </thead>


                <tbody class="divide-y divide-gray-100 bg-white">

                    @forelse ($inventaris as $item)

                        <tr class="transition hover:bg-gray-50">

                            {{-- Informasi Barang --}}
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">

                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-sm font-bold text-[#1d2d50]">
                                        {{ strtoupper(substr($item->nama_barang, 0, 1)) }}
                                    </div>

                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">
                                            {{ $item->nama_barang }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500">
                                            Kode:
                                            {{ $item->kode_barang ?: '-' }}
                                        </p>
                                    </div>

                                </div>
                            </td>


                            {{-- Kategori --}}
                            <td class="whitespace-nowrap px-6 py-4">
                                @switch($item->kategori)

                                    @case('perangkat_pengamanan')
                                        <span class="inline-flex rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700">
                                            Perangkat Pengamanan
                                        </span>
                                        @break

                                    @case('alat_pendukung')
                                        <span class="inline-flex rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">
                                            Alat Pendukung
                                        </span>
                                        @break

                                    @default
                                        <span class="inline-flex rounded-full bg-purple-50 px-3 py-1.5 text-xs font-semibold text-purple-700">
                                            Inventaris PJL
                                        </span>

                                @endswitch
                            </td>


                            {{-- Lokasi dan JPL --}}
                            <td class="px-6 py-4">
                                <p class="text-sm font-medium text-gray-800">
                                    {{ $item->jpl?->kode_jpl ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $item->jpl?->nama_jpl ?? $item->lokasi ?? 'Belum ditentukan' }}
                                </p>
                            </td>


                            {{-- Jumlah --}}
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <span class="text-sm font-semibold text-gray-800">
                                    {{ number_format($item->jumlah ?? 0) }}
                                </span>

                                <span class="text-xs text-gray-500">
                                    unit
                                </span>
                            </td>


                            {{-- Status --}}
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                @if ($item->is_active)
                                    <span class="inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>


                            {{-- Aksi --}}
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center justify-center gap-2">

                                    {{-- Edit --}}
                                    <a
                                        href="{{ route('admin.inventaris.edit', $item->id) }}"
                                        title="Edit inventaris"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-[#1d2d50] transition hover:border-blue-200 hover:bg-blue-50"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M16.862 4.487l2.651 2.651M8 16l8.5-8.5a2.121 2.121 0 0 1 3 3L11 19H8v-3Z"
                                            />
                                        </svg>
                                    </a>


                                    {{-- Hapus --}}
                                    <form
                                        action="{{ route('admin.inventaris.destroy', $item->id) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus atau menonaktifkan inventaris ini?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            title="Hapus inventaris"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-red-600 transition hover:border-red-200 hover:bg-red-50"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M6 7h12M9 7V4h6v3m-7 0 1 13h6l1-13M10 11v5m4-5v5"
                                                />
                                            </svg>
                                        </button>
                                    </form>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">

                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-7 w-7"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.8"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M4 7h16M6 7v12h12V7M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                                        />
                                    </svg>
                                </div>

                                <p class="mt-4 text-sm font-semibold text-gray-700">
                                    Belum ada data inventaris
                                </p>

                                <p class="mt-1 text-sm text-gray-500">
                                    Tambahkan barang untuk mulai mengelola inventaris PJL.
                                </p>

                                <a
                                    href="{{ route('admin.inventaris.create') }}"
                                    class="mt-4 inline-flex items-center rounded-lg bg-[#1d2d50] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#14213d]"
                                >
                                    + Tambah Inventaris
                                </a>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        @if ($inventaris->hasPages())
            <div class="border-t border-gray-200 px-6 py-4">
                {{ $inventaris->links() }}
            </div>
        @endif

    </div>

</div>

@endsection
