@extends('layouts.admin')

@section('title', 'Preview Import JPL')

@section('content')

<div class="p-6 lg:p-8">

    {{-- Header --}}
    <div class="mb-6">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h1 class="text-2xl font-bold text-[#1d2d50]">
                    Preview Import JPL
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Periksa data sebelum dimasukkan ke sistem.
                </p>

            </div>

            <a
                href="{{ route('admin.jpl.import') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
            >
                Kembali
            </a>

        </div>

    </div>


    {{-- Flash Error --}}
    @if(session('error'))

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

            <div class="flex gap-3">

                <div class="text-red-600">
                    ⚠
                </div>

                <div>

                    <p class="font-semibold text-red-800">
                        Import tidak dapat dilakukan
                    </p>

                    <p class="mt-1 text-sm text-red-700">
                        {{ session('error') }}
                    </p>

                </div>

            </div>

        </div>

    @endif


    {{-- Wilayah --}}
    <div class="mb-6 rounded-2xl border border-[#1d2d50]/10 bg-white p-6 shadow-sm">

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                    Wilayah Tujuan
                </p>

                <h2 class="mt-1 text-xl font-bold text-[#1d2d50]">
                    {{ $wilayahName }}
                </h2>

            </div>

            <div class="rounded-xl bg-blue-50 px-4 py-3">

                <p class="text-xs text-blue-500">
                    Seluruh Resort dari Excel
                </p>

                <p class="mt-1 text-sm font-semibold text-blue-800">
                    akan terhubung ke Wilayah ini
                </p>

            </div>

        </div>

    </div>


    {{-- Statistik --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- Total JPL --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">

            <p class="text-sm text-gray-500">
                Total JPL
            </p>

            <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                {{ $total }}
            </p>

        </div>


        {{-- Resort Existing --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">

            <p class="text-sm text-gray-500">
                Resort Ditemukan
            </p>

            <p class="mt-2 text-3xl font-bold text-green-600">
                {{ count($existingResortNames) }}
            </p>

        </div>


        {{-- Resort Baru --}}
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">

            <p class="text-sm text-amber-700">
                Resort Baru
            </p>

            <p class="mt-2 text-3xl font-bold text-amber-700">
                {{ count($newResortNames) }}
            </p>

        </div>


        {{-- Error --}}
        <div class="
            rounded-2xl border p-5 shadow-sm
            {{ $errorCount > 0
                ? 'border-red-200 bg-red-50'
                : 'border-green-200 bg-green-50'
            }}
        ">

            <p class="
                text-sm
                {{ $errorCount > 0
                    ? 'text-red-700'
                    : 'text-green-700'
                }}
            ">
                Data Bermasalah
            </p>

            <p class="
                mt-2 text-3xl font-bold
                {{ $errorCount > 0
                    ? 'text-red-700'
                    : 'text-green-700'
                }}
            ">
                {{ $errorCount }}
            </p>

        </div>

    </div>


    {{-- Resort yang akan digunakan --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-2">

        {{-- Existing --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">

            <h2 class="font-semibold text-[#1d2d50]">
                Resort yang sudah tersedia
            </h2>

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
                    Tidak ada Resort yang sudah tersedia pada Wilayah ini.
                </p>

            @endif

        </div>


        {{-- New --}}
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">

            <h2 class="font-semibold text-amber-900">
                Resort yang akan dibuat otomatis
            </h2>

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
                    Tidak ada Resort baru.
                    Semua Resort sudah tersedia.
                </p>

            @endif

        </div>

    </div>


    {{-- Status --}}
    @if($errorCount > 0)

        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

            <div class="flex gap-3">

                <div class="text-red-600">
                    ⚠
                </div>

                <div>

                    <p class="font-semibold text-red-800">
                        Terdapat {{ $errorCount }} data yang perlu diperbaiki.
                    </p>

                    <p class="mt-1 text-sm text-red-700">
                        Import belum dapat dilakukan sampai seluruh data valid.
                    </p>

                </div>

            </div>

        </div>

    @else

        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4">

            <div class="flex gap-3">

                <div class="text-green-600">
                    ✓
                </div>

                <div>

                    <p class="font-semibold text-green-800">
                        Semua data valid dan siap diimport.
                    </p>

                    <p class="mt-1 text-sm text-green-700">
                        Resort yang belum tersedia akan dibuat otomatis
                        di bawah Wilayah <strong>{{ $wilayahName }}</strong>.
                    </p>

                </div>

            </div>

        </div>

    @endif


    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

        <div class="border-b border-gray-200 px-6 py-5">

            <h2 class="font-semibold text-[#1d2d50]">
                Data JPL
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Menampilkan hasil pembacaan dari file Excel.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead class="bg-[#f8f9fb]">

                    <tr>

                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-600">
                            No
                        </th>

                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-600">
                            Resort
                        </th>

                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-600">
                            Antara
                        </th>

                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-600">
                            KM / HM
                        </th>

                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-600">
                            No JPL
                        </th>

                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-600">
                            Nama JPL
                        </th>

                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-600">
                            Kota / Kabupaten
                        </th>

                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-600">
                            Status
                        </th>

                        <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-600">
                            Keterangan
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-gray-100">

                    @forelse($rows as $row)

                        <tr class="hover:bg-gray-50">

                            <td class="whitespace-nowrap px-4 py-3 text-gray-500">
                                {{ $row['row'] ?? '-' }}
                            </td>


                            <td class="whitespace-nowrap px-4 py-3">

                                <span class="font-medium text-gray-800">
                                    {{ $row['resort'] ?? '-' }}
                                </span>

                            </td>


                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                {{ $row['antara'] ?? '-' }}
                            </td>


                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                {{ $row['km_hm'] ?? '-' }}
                            </td>


                            <td class="whitespace-nowrap px-4 py-3">

                                @if(($row['kode_jpl'] ?? null) === 'Liar')

                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                        Liar
                                    </span>

                                @else

                                    <span class="font-medium text-gray-800">
                                        {{ $row['kode_jpl'] ?? '-' }}
                                    </span>

                                @endif

                            </td>


                            <td class="min-w-[220px] px-4 py-3 text-gray-700">
                                {{ $row['nama_jpl'] ?? '-' }}
                            </td>


                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                {{ $row['kota_kabupaten'] ?? '-' }}
                            </td>


                            <td class="whitespace-nowrap px-4 py-3">

                                @if(($row['status'] ?? '') === 'error')

                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                        Error
                                    </span>

                                @elseif(($row['status'] ?? '') === 'baru')

                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                        Resort Baru
                                    </span>

                                @else

                                    <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">
                                        Valid
                                    </span>

                                @endif

                            </td>


                            <td class="min-w-[280px] px-4 py-3">

                                @if(!empty($row['errors']))

                                    <ul class="space-y-1 text-xs text-red-600">

                                        @foreach($row['errors'] as $error)

                                            <li>
                                                • {{ $error }}
                                            </li>

                                        @endforeach

                                    </ul>

                                @elseif(!empty($row['notes']))

                                    <ul class="space-y-1 text-xs text-amber-700">

                                        @foreach($row['notes'] as $note)

                                            <li>
                                                • {{ $note }}
                                            </li>

                                        @endforeach

                                    </ul>

                                @else

                                    <span class="text-xs text-green-600">
                                        Data siap diimport.
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="9"
                                class="px-6 py-12 text-center text-sm text-gray-500"
                            >
                                Tidak ada data JPL yang ditemukan.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- Footer Action --}}
        <div class="flex flex-col gap-3 border-t border-gray-200 bg-gray-50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

            <div>

                @if($errorCount > 0)

                    <p class="text-sm font-medium text-red-600">
                        Import tidak tersedia karena masih terdapat data bermasalah.
                    </p>

                @else

                    <p class="text-sm text-gray-600">
                        {{ $total }} data JPL siap dimasukkan ke sistem.
                    </p>

                @endif

            </div>


            <div class="flex gap-3">

                <a
                    href="{{ route('admin.jpl.import') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Upload Ulang
                </a>


                @if($errorCount === 0 && $total > 0)

                    <form
                        action="{{ route('admin.jpl.import.confirm') }}"
                        method="POST"
                    >

                        @csrf

                        <button
                            type="submit"
                            onclick="return confirm('Import {{ $total }} data JPL ke Wilayah {{ addslashes($wilayahName) }}? Resort yang belum ada akan dibuat otomatis.')"
                            class="inline-flex items-center justify-center rounded-xl bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#14233f]"
                        >
                            Konfirmasi Import
                        </button>

                    </form>

                @endif

            </div>

        </div>

    </div>

</div>

@endsection