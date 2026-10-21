@extends('layouts.admin')

@section('title', 'Preview Import Jadwal KA')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                Preview Import Jadwal KA
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Periksa data workbook Excel sebelum disimpan ke database.
            </p>
        </div>

        <a
            href="{{ route('admin.jadwal-ka.import') }}"
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
        >
            &larr; Kembali ke Import
        </a>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4">
            <h2 class="font-semibold text-red-900">
                Terjadi Kesalahan
            </h2>
            <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">
                Sheet Jadwal Terbaca
            </p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                {{ count($sheets) }}
            </p>
        </div>

        <div class="rounded-xl border border-green-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">
                Baris Siap Ditambahkan
            </p>
            <p class="mt-2 text-3xl font-bold text-green-600">
                {{ $validCount }}
            </p>
        </div>

        <div class="rounded-xl border border-red-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">
                Baris Tidak Valid
            </p>
            <p class="mt-2 text-3xl font-bold text-red-600">
                {{ $invalidCount }}
            </p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">
                Duplikat
            </p>
            <p class="mt-2 text-3xl font-bold text-amber-600">
                {{ $duplicateCount ?? 0 }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
                Akan dilewati saat konfirmasi.
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">
                Masalah Import
            </p>
            <p class="mt-2 text-3xl font-bold {{ count($importErrors) > 0 ? 'text-red-600' : 'text-gray-900' }}">
                {{ count($importErrors) }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
                Kesalahan parser atau pencocokan JPL.
            </p>
        </div>

    </div>

    {{-- Status import --}}
    @if ($canConfirm)
        <div class="rounded-xl border border-green-200 bg-green-50 p-4">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 text-green-700">
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>

                <div>
                    <h2 class="font-semibold text-green-900">
                        Preview Siap Dikonfirmasi
                    </h2>
                    <p class="mt-1 text-sm text-green-800">
                        Tidak ditemukan kesalahan parser atau ketidakcocokan JPL.
                        Periksa kembali data sebelum melanjutkan.
                        Baris duplikat akan dilewati.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-xl border border-red-200 bg-red-50 p-4">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 text-red-700">
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>

                <div>
                    <h2 class="font-semibold text-red-900">
                        Import Belum Dapat Dikonfirmasi
                    </h2>
                    <p class="mt-1 text-sm text-red-800">
                        Perbaiki kesalahan yang tercantum di bawah, lalu unggah
                        ulang workbook Excel. Jadwal belum disimpan ke database.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- Kesalahan parser dan pencocokan JPL --}}
    @if (count($importErrors) > 0)
        <section class="overflow-hidden rounded-xl border border-red-200 bg-white shadow-sm">
            <div class="border-b border-red-200 bg-red-50 px-5 py-4">
                <h2 class="font-semibold text-red-900">
                    Kesalahan Import
                </h2>
                <p class="mt-1 text-sm text-red-700">
                    {{ count($importErrors) }} masalah ditemukan.
                </p>
            </div>

            <div class="max-h-72 space-y-2 overflow-y-auto p-5">
                @foreach ($importErrors as $error)
                    <div class="flex gap-2 text-sm text-red-800">
                        <span class="shrink-0 font-bold">&bull;</span>
                        <span>{{ $error }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Peringatan --}}
    @if (count($warnings) > 0)
        <section class="overflow-hidden rounded-xl border border-amber-200 bg-white shadow-sm">
            <div class="border-b border-amber-200 bg-amber-50 px-5 py-4">
                <h2 class="font-semibold text-amber-900">
                    Peringatan
                </h2>
                <p class="mt-1 text-sm text-amber-800">
                    Sheet berikut dilewati karena header jadwal tidak terdeteksi.
                    Pastikan sheet tersebut memang bukan bagian dari data jadwal.
                </p>
            </div>

            <div class="space-y-2 p-5">
                @foreach ($warnings as $warning)
                    <div class="flex gap-2 text-sm text-amber-800">
                        <span class="shrink-0 font-bold">&bull;</span>
                        <span>{{ $warning }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Daftar sheet --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="font-semibold text-gray-900">
                Hasil Pencocokan Sheet dan JPL
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Setiap sheet jadwal harus terhubung dengan JPL yang sesuai.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Nama Sheet
                        </th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            JPL Database
                        </th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Jumlah Baris
                        </th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Kesalahan Baris
                        </th>
                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($sheets as $sheet)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-gray-900">
                                {{ $sheet['name'] }}
                            </td>

                            <td class="px-5 py-4 text-sm text-gray-600">
                                {{ $sheet['jpl_name'] ?? 'JPL tidak ditemukan' }}
                            </td>

                            <td class="px-5 py-4 text-center text-sm text-gray-700">
                                {{ $sheet['row_count'] }}
                            </td>

                            <td class="px-5 py-4 text-center text-sm {{ ($sheet['error_count'] ?? 0) > 0 ? 'font-semibold text-red-600' : 'text-gray-500' }}">
                                {{ $sheet['error_count'] ?? 0 }}
                            </td>

                            <td class="px-5 py-4 text-center">
                                @if ($sheet['jpl_found'])
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                        JPL Cocok
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800">
                                        JPL Tidak Cocok
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-gray-500">
                                Tidak ada sheet jadwal yang dapat ditampilkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Preview data --}}
    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-gray-900">
                    Preview Data Jadwal KA
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Periksa nomor KA, waktu lewat, keterangan, dan JPL tujuan.
                </p>
            </div>

            <span class="inline-flex w-fit rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                {{ count($rows) }} baris
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            No.
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Sheet / JPL
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Baris Excel
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Nomor KA
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Nama KA
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Dari
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Ke
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Jam Lewat
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Keterangan
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">
                            Status
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($rows as $index => $row)
                        <tr class="{{ !($row['valid'] ?? false) ? 'bg-red-50' : (($row['duplicate'] ?? false) ? 'bg-amber-50' : 'hover:bg-gray-50') }}">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ $index + 1 }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900">
                                    {{ $row['sheet'] }}
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ $row['jpl_name'] ?? 'JPL tidak ditemukan' }}
                                </div>
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ $row['row'] }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-gray-900">
                                {{ $row['nomor_ka'] ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                {{ $row['nama_ka'] ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                {{ $row['dari'] ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                {{ $row['ke'] ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium tabular-nums text-gray-900">
                                {{ $row['jam_lewat'] ?? '-' }}
                            </td>

                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ $row['keterangan'] ?? '-' }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                @if (!($row['valid'] ?? false))
                                    <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-800">
                                        Tidak Valid
                                    </span>

                                    @if (!empty($row['error']))
                                        <p class="mt-1 max-w-48 whitespace-normal text-xs text-red-700">
                                            {{ $row['error'] }}
                                        </p>
                                    @endif
                                @elseif ($row['duplicate'] ?? false)
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                        Duplikat
                                    </span>

                                    @if (!empty($row['error']))
                                        <p class="mt-1 max-w-48 whitespace-normal text-xs text-amber-700">
                                            {{ $row['error'] }}
                                        </p>
                                    @endif
                                @else
                                    <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                        Valid
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-10 text-center text-sm text-gray-500">
                                Tidak ada data jadwal untuk ditampilkan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-200 bg-gray-50 px-5 py-3">
            <p class="text-xs text-gray-500">
                Baris valid akan diproses untuk ditambahkan. Duplikat akan dilewati.
                Baris tidak valid memerlukan perbaikan sebelum import dapat dikonfirmasi.
            </p>
        </div>
    </section>

    {{-- Konfirmasi --}}
    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <h2 class="font-semibold text-gray-900">
            Konfirmasi Import
        </h2>

        <p class="mt-1 text-sm text-gray-500">
            Jadwal lama tidak akan dihapus. Duplikat sesuai aturan controller
            akan dilewati, sedangkan kesalahan validasi menghalangi proses import.
        </p>

        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a
                href="{{ route('admin.jadwal-ka.import') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
            >
                Batal dan Upload Ulang
            </a>

            @if ($canConfirm)
                <form
                    action="{{ route('admin.jadwal-ka.import.confirm') }}"
                    method="POST"
                    onsubmit="return confirm('Konfirmasi import jadwal KA? Data yang lolos validasi akan disimpan ke database. Duplikat akan dilewati.');"
                >
                    @csrf

                    <input
                        type="hidden"
                        name="token"
                        value="{{ $token }}"
                    >

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-auto"
                    >
                        Konfirmasi dan Simpan Jadwal
                    </button>
                </form>
            @else
                <button
                    type="button"
                    disabled
                    class="inline-flex cursor-not-allowed items-center justify-center rounded-lg bg-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600"
                >
                    Perbaiki Kesalahan Sebelum Import
                </button>
            @endif
        </div>
    </section>

</div>
@endsection
