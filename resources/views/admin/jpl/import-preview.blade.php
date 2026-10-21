@extends('layouts.admin')

@section('title', 'Preview Import JPL')

@section('content')

@php
    $prefix = request()->routeIs('superadmin.*')
        ? 'superadmin'
        : 'admin';

    $rows = collect($rows ?? []);
    $total = $rows->count();

    $errorCount = $rows->where('status', 'error')->count();
    $duplicateCount = $rows->where('status', 'duplikat')->count();
    $newResortCount = $rows->where('status', 'baru')->count();
    $validCount = $rows->where('status', 'valid')->count();

    $readyCount = $validCount + $newResortCount;
    $skippedCount = $errorCount + $duplicateCount;

    $existingResortNames = $existingResortNames ?? [];
    $newResortNames = $newResortNames ?? [];

    // Partial import: tetap bisa konfirmasi jika ada data valid.
    $canImport = $readyCount > 0;

    // Seluruh 11 kolom sesuai urutan Excel.
    $columns = [
        'kode_jpl' => 'NO JPL',
        'km_hm' => 'KM / HM',
        'antara' => 'ANTARA',
        'status_penjagaan' => 'STATUS PENJAGAAN',
        'jenis_jalur_ka' => 'JENIS JALUR',
        'konst_jalan' => 'KONST JALAN',
        'titik_koordinat' => 'TITIK KOORDINAT',
        'lebar_jln' => 'LEBAR JLN (m)',
        'resort' => 'RESORT',
        'nama_jpl' => 'NAMA JLN / DAERAH',
        'kota_kabupaten' => 'KOTA / KABUPATEN',
    ];
@endphp

<div class="p-4 lg:p-8">

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Preview Import JPL
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Periksa seluruh data Excel sebelum disimpan ke sistem.
            </p>
        </div>

        <a
            href="{{ route($prefix . '.jpl.import') }}"
            class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
        >
            ← Kembali
        </a>

    </div>

    {{-- FLASH ERROR --}}
    @if(session('error'))

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

            <p class="font-semibold text-red-800">
                Import tidak dapat dilakukan
            </p>

            <p class="mt-1 text-sm text-red-700">
                {{ session('error') }}
            </p>

        </div>

    @endif

    {{-- WILAYAH --}}
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                    Wilayah Tujuan
                </p>

                <h2 class="mt-1 text-xl font-bold text-[#1d2d50]">
                    {{ $wilayahName }}
                </h2>

            </div>

            <div class="rounded-xl bg-blue-50 px-4 py-3">

                <p class="text-xs text-blue-600">
                    Resort dari Excel akan dihubungkan
                </p>

                <p class="mt-1 text-sm font-semibold text-blue-800">
                    ke wilayah ini
                </p>

            </div>

        </div>

    </div>

    {{-- STATISTIK --}}
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">

        {{-- TOTAL --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">

            <p class="text-sm text-gray-500">
                Total Baris
            </p>

            <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                {{ $total }}
            </p>

        </div>

        {{-- SIAP DISIMPAN --}}
        <div class="rounded-2xl border border-green-200 bg-green-50 p-5 shadow-sm">

            <p class="text-sm text-green-700">
                Siap Disimpan
            </p>

            <p class="mt-2 text-3xl font-bold text-green-700">
                {{ $readyCount }}
            </p>

        </div>

        {{-- RESORT BARU --}}
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">

            <p class="text-sm text-amber-700">
                Resort Baru
            </p>

            <p class="mt-2 text-3xl font-bold text-amber-700">
                {{ count($newResortNames) }}
            </p>

        </div>

        {{-- DUPLIKAT --}}
        <div class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm">

            <p class="text-sm text-orange-700">
                Duplikat
            </p>

            <p class="mt-2 text-3xl font-bold text-orange-700">
                {{ $duplicateCount }}
            </p>

        </div>

        {{-- ERROR --}}
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm">

            <p class="text-sm text-red-700">
                Error
            </p>

            <p class="mt-2 text-3xl font-bold text-red-700">
                {{ $errorCount }}
            </p>

        </div>

    </div>

    {{-- INFORMASI RESORT --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-2">

        {{-- RESORT EXISTING --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">

            <h2 class="font-semibold text-[#1d2d50]">
                Resort yang Sudah Tersedia
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                {{ count($existingResortNames) }} Resort ditemukan.
            </p>

            @if(count($existingResortNames) > 0)

                <div class="mt-4 flex flex-wrap gap-2">

                    @foreach($existingResortNames as $name)

                        <span class="rounded-full bg-green-50 px-3 py-1.5 text-xs font-medium text-green-700">
                            {{ $name }}
                        </span>

                    @endforeach

                </div>

            @else

                <p class="mt-4 text-sm text-gray-500">
                    Tidak ada Resort yang cocok dengan wilayah tujuan.
                </p>

            @endif

        </div>

        {{-- RESORT BARU --}}
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">

            <h2 class="font-semibold text-amber-900">
                Resort yang Akan Dibuat
            </h2>

            <p class="mt-1 text-xs text-amber-700">
                Resort baru akan dibuat otomatis saat konfirmasi.
            </p>

            @if(count($newResortNames) > 0)

                <div class="mt-4 flex flex-wrap gap-2">

                    @foreach($newResortNames as $name)

                        <span class="rounded-full bg-white px-3 py-1.5 text-xs font-medium text-amber-800 ring-1 ring-amber-200">
                            {{ $name }}
                        </span>

                    @endforeach

                </div>

            @else

                <p class="mt-4 text-sm text-amber-700">
                    Semua Resort sudah tersedia.
                </p>

            @endif

        </div>

    </div>

    {{-- INFORMASI VALIDASI --}}
    @if($errorCount > 0)

        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">

            <p class="font-semibold text-amber-800">
                ⚠ Terdapat {{ $errorCount }} baris bermasalah.
            </p>

            <p class="mt-1 text-sm text-amber-700">
                Import tetap dapat dilakukan selama terdapat data valid.
                Baris dengan status Error akan dilewati dan tidak disimpan
                ke database.
            </p>

        </div>

    @else

        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4">

            <p class="font-semibold text-green-800">
                ✓ Tidak ditemukan error validasi.
            </p>

            <p class="mt-1 text-sm text-green-700">
                {{ $readyCount }} baris diperkirakan siap disimpan.
            </p>

        </div>

    @endif

    {{-- INFORMASI DUPLIKAT --}}
    @if($duplicateCount > 0)

        <div class="mb-6 rounded-xl border border-orange-200 bg-orange-50 p-4">

            <p class="font-semibold text-orange-800">
                Perhatian: {{ $duplicateCount }} Data Duplikat
            </p>

            <p class="mt-1 text-sm text-orange-700">
                Data yang dianggap memiliki identitas sama dengan data
                di database atau baris lain dalam Excel akan dilewati.
                Data tersebut tetap ditampilkan pada preview.
            </p>

        </div>

    @endif

    {{-- RINGKASAN PARTIAL IMPORT --}}
    <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4">

        <h3 class="font-semibold text-blue-900">
            Ringkasan Import
        </h3>

        <p class="mt-2 text-sm text-blue-800">
            Dari <strong>{{ $total }}</strong> baris yang terbaca,
            <strong>{{ $readyCount }}</strong> baris memenuhi syarat
            untuk disimpan dan <strong>{{ $skippedCount }}</strong>
            baris akan dilewati.
        </p>

        <p class="mt-1 text-xs text-blue-700">
            Jumlah akhir dapat berubah jika data database berubah
            setelah preview dibuat.
        </p>

    </div>

    {{-- TABEL PREVIEW --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

        <div class="flex flex-col gap-3 border-b border-gray-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="font-semibold text-[#1d2d50]">
                    Data JPL dari Excel
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Menampilkan 11 kolom sumber beserta nomor baris dan hasil validasi.
                </p>

            </div>

            <span class="inline-flex w-fit rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600">
                {{ $total }} Baris
            </span>

        </div>

        <div class="border-b border-blue-100 bg-blue-50 px-6 py-3">

            <p class="text-xs text-blue-700">
                ↔ Geser tabel ke kanan atau kiri untuk melihat seluruh kolom.
                Tanda — menunjukkan nilai kosong.
            </p>

        </div>

        <div class="max-w-full overflow-x-auto">

            <table class="min-w-max border-collapse text-sm">

                <thead class="bg-[#f8f9fb]">

                    <tr>

                        <th class="sticky left-0 z-20 whitespace-nowrap border-b border-r border-gray-200 bg-[#f8f9fb] px-4 py-3 text-left font-semibold text-gray-600">
                            Baris Excel
                        </th>

                        @foreach($columns as $label)

                            <th class="whitespace-nowrap border-b border-gray-200 px-4 py-3 text-left font-semibold text-gray-600">
                                {{ $label }}
                            </th>

                        @endforeach

                        <th class="whitespace-nowrap border-b border-gray-200 px-4 py-3 text-left font-semibold text-gray-600">
                            Status
                        </th>

                        <th class="min-w-[280px] border-b border-gray-200 px-4 py-3 text-left font-semibold text-gray-600">
                            Keterangan
                        </th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-gray-100">

                    @forelse($rows as $row)

                        @php
                            $status = $row['status'] ?? 'error';

                            $rowBackground = match($status) {
                                'error' => 'bg-red-50/40',
                                'duplikat' => 'bg-orange-50/40',
                                'baru' => 'bg-amber-50/30',
                                default => 'bg-white',
                            };

                            $statusClass = match($status) {
                                'error' => 'bg-red-100 text-red-700',
                                'duplikat' => 'bg-orange-100 text-orange-700',
                                'baru' => 'bg-amber-100 text-amber-700',
                                default => 'bg-green-100 text-green-700',
                            };

                            $statusLabel = match($status) {
                                'error' => 'Error',
                                'duplikat' => 'Duplikat',
                                'baru' => 'Resort Baru',
                                default => 'Valid',
                            };
                        @endphp

                        <tr class="{{ $rowBackground }} hover:bg-blue-50/50">

                            {{-- NOMOR BARIS EXCEL --}}
                            <td class="sticky left-0 z-10 whitespace-nowrap border-r border-gray-200 px-4 py-3 font-semibold text-gray-700 {{ $rowBackground }}">
                                {{ $row['row'] ?? '-' }}
                            </td>

                            {{-- SELURUH KOLOM EXCEL --}}
                            @foreach($columns as $key => $label)

                                @php
                                    $value = $row[$key] ?? null;

                                    $isEmpty = $value === null
                                        || trim((string) $value) === '';
                                @endphp

                                <td class="max-w-[300px] px-4 py-3 align-top text-gray-700">

                                    @if($isEmpty)

                                        <span class="text-gray-400">
                                            —
                                        </span>

                                    @elseif(
                                        $key === 'kode_jpl'
                                        && mb_strtolower(trim((string) $value)) === 'liar'
                                    )

                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                            {{ $value }}
                                        </span>

                                    @else

                                        <span class="block min-w-[75px] break-words">
                                            {{ $value }}
                                        </span>

                                    @endif

                                </td>

                            @endforeach

                            {{-- STATUS --}}
                            <td class="whitespace-nowrap px-4 py-3 align-top">

                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>

                            </td>

                            {{-- KETERANGAN --}}
                            <td class="min-w-[280px] max-w-[380px] px-4 py-3 align-top">

                                @if(!empty($row['errors']))

                                    <ul class="space-y-1 text-xs text-red-700">

                                        @foreach($row['errors'] as $error)
                                            <li>• {{ $error }}</li>
                                        @endforeach

                                    </ul>

                                @endif

                                @if(!empty($row['notes']))

                                    <ul class="mt-1 space-y-1 text-xs text-amber-700">

                                        @foreach($row['notes'] as $note)
                                            <li>• {{ $note }}</li>
                                        @endforeach

                                    </ul>

                                @endif

                                @if(
                                    empty($row['errors'])
                                    && empty($row['notes'])
                                )

                                    <span class="text-xs text-green-600">
                                        Data siap diimport.
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="{{ count($columns) + 3 }}"
                                class="px-6 py-12 text-center text-sm text-gray-500"
                            >
                                Tidak ada data JPL yang ditemukan.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- FOOTER ACTION --}}
        <div class="border-t border-gray-200 bg-gray-50 px-6 py-5">

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                <div>

                    @if($canImport)

                        <p class="text-sm font-semibold text-gray-800">
                            {{ $readyCount }} dari {{ $total }} baris siap diimport.
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            {{ $errorCount }} error dan
                            {{ $duplicateCount }} duplikat akan dilewati.
                        </p>

                    @else

                        <p class="text-sm font-semibold text-red-700">
                            Tidak ada data valid yang dapat diimport.
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Upload ulang file dengan minimal satu baris valid.
                        </p>

                    @endif

                </div>

                <div class="flex flex-col gap-3 sm:flex-row">

                    <a
                        href="{{ route($prefix . '.jpl.import') }}"
                        class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                    >
                        Upload Ulang
                    </a>

                    @if($canImport)

                        <form
                            action="{{ route($prefix . '.jpl.import.confirm') }}"
                            method="POST"
                            onsubmit="return confirm('Lanjutkan import JPL? Hanya data valid yang akan disimpan. Data error dan duplikat akan dilewati. Resort baru akan dibuat otomatis.')"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center rounded-xl bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#14233f]"
                            >
                                Konfirmasi Import ({{ $readyCount }})
                            </button>

                        </form>

                    @else

                        <button
                            type="button"
                            disabled
                            class="inline-flex cursor-not-allowed items-center justify-center rounded-xl bg-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-500"
                        >
                            Konfirmasi Import
                        </button>

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
