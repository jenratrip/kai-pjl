@extends('layouts.admin')

@section('title', 'Import Data JPL')

@section('content')

<div class="p-6 lg:p-8">

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-[#1d2d50]">
                    Import Data JPL
                </h1>

                <p class="mt-1 text-sm text-gray-500">
                    Import data JPL dari file Excel dan hubungkan otomatis
                    ke Wilayah dan Resort.
                </p>
            </div>

            <a
                href="{{ route('admin.jpl.index') }}"
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
                <div class="mt-0.5 text-red-600">
                    ⚠
                </div>

                <div>
                    <p class="font-semibold text-red-800">
                        Import gagal
                    </p>

                    <p class="mt-1 text-sm text-red-700">
                        {{ session('error') }}
                    </p>
                </div>
            </div>
        </div>
    @endif


    {{-- Validation Error --}}
    @if($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

            <p class="font-semibold text-red-800">
                Terdapat kesalahan pada form
            </p>

            <ul class="mt-2 list-disc pl-5 text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>
    @endif


    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Form --}}
        <div class="lg:col-span-2">

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-[#1d2d50]">
                        Upload File Excel
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Tentukan Wilayah tujuan sebelum mengunggah data.
                    </p>
                </div>


                <form
                    action="{{ route('admin.jpl.import.preview.store') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="p-6"
                >

                    @csrf


                    {{-- Wilayah --}}
                    <div class="mb-6">

                        <label
                            for="wilayah_id"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            Wilayah
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="wilayah_id"
                            id="wilayah_id"
                            required
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-700 outline-none transition focus:border-[#1d2d50] focus:ring-2 focus:ring-[#1d2d50]/10"
                        >
                            <option value="">
                                -- Pilih Wilayah --
                            </option>

                            @foreach($wilayahs as $wilayah)
                                <option
                                    value="{{ $wilayah->id }}"
                                    @selected(old('wilayah_id') == $wilayah->id)
                                >
                                    {{ $wilayah->nama_wilayah }}
                                </option>
                            @endforeach

                        </select>

                        <p class="mt-2 text-xs text-gray-500">
                            Semua Resort dari file Excel akan otomatis
                            ditempatkan di Wilayah yang dipilih.
                        </p>

                    </div>


                    {{-- File --}}
                    <div class="mb-6">

                        <label
                            for="file"
                            class="mb-2 block text-sm font-semibold text-gray-700"
                        >
                            File Excel
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="file"
                            name="file"
                            id="file"
                            accept=".xlsx,.xls"
                            required
                            class="block w-full cursor-pointer rounded-xl border border-gray-300 bg-gray-50 text-sm text-gray-700 file:mr-4 file:border-0 file:bg-[#1d2d50] file:px-4 file:py-3 file:text-sm file:font-medium file:text-white hover:file:bg-[#14233f]"
                        />

                        <p class="mt-2 text-xs text-gray-500">
                            Format yang didukung: .xlsx dan .xls.
                            Maksimal 10 MB.
                        </p>

                    </div>


                    {{-- Informasi --}}
                    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">

                        <div class="flex gap-3">

                            <div class="mt-0.5 text-amber-600">
                                ℹ
                            </div>

                            <div>

                                <p class="font-semibold text-amber-800">
                                    Mekanisme Import
                                </p>

                                <ul class="mt-2 space-y-1 text-sm text-amber-800">
                                    <li>
                                        • Resort yang sudah ada akan digunakan kembali.
                                    </li>

                                    <li>
                                        • Resort yang belum ada akan dibuat otomatis.
                                    </li>

                                    <li>
                                        • Semua Resort akan terhubung ke Wilayah yang dipilih.
                                    </li>

                                    <li>
                                        • Setiap JPL akan otomatis terhubung ke Resort berdasarkan kolom RESORT.
                                    </li>

                                    <li>
                                        • Nilai <strong>Liar</strong> pada NO JPL merupakan data resmi dan akan dipertahankan.
                                    </li>

                                    <li>
                                        • File tidak langsung disimpan sebelum tahap konfirmasi.
                                    </li>
                                </ul>

                            </div>

                        </div>

                    </div>


                    {{-- Mapping --}}
                    <div class="mb-6">

                        <h3 class="mb-3 text-sm font-semibold text-gray-800">
                            Mapping Data Excel
                        </h3>

                        <div class="overflow-hidden rounded-xl border border-gray-200">

                            <table class="min-w-full text-sm">

                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                            Kolom Excel
                                        </th>

                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                            Field Sistem
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200">

                                    <tr>
                                        <td class="px-4 py-3">RESORT</td>
                                        <td class="px-4 py-3">Resort otomatis</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">ANTARA</td>
                                        <td class="px-4 py-3">antara</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">KM / HM</td>
                                        <td class="px-4 py-3">km_hm</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">NO JPL</td>
                                        <td class="px-4 py-3">kode_jpl</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">NAMA JLN / DAERAH</td>
                                        <td class="px-4 py-3">nama_jpl</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">KOTA / KABUPATEN</td>
                                        <td class="px-4 py-3">kota_kabupaten</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">PROVINSI</td>
                                        <td class="px-4 py-3">provinsi</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">JENIS JALUR KA</td>
                                        <td class="px-4 py-3">jenis_jalur_ka</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">TITIK KOORDINAT</td>
                                        <td class="px-4 py-3">titik_koordinat</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">STATUS PENJAGAAN</td>
                                        <td class="px-4 py-3">status_penjagaan</td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    {{-- Button --}}
                    <div class="flex justify-end">

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-[#1d2d50] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[#14233f] focus:outline-none focus:ring-2 focus:ring-[#1d2d50]/30"
                        >
                            Preview Import
                        </button>

                    </div>

                </form>

            </div>

        </div>


        {{-- Side Information --}}
        <div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">

                <h2 class="text-lg font-semibold text-[#1d2d50]">
                    Struktur Data
                </h2>

                <div class="mt-5 space-y-4">

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Wilayah
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-700">
                            Dipilih saat upload
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Resort
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-700">
                            Dibaca dari Excel
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            JPL
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-700">
                            Dibaca dari Excel
                        </p>
                    </div>

                </div>


                <div class="mt-6 rounded-xl bg-gray-50 p-4">

                    <p class="text-sm leading-6 text-gray-600">
                        Contoh:
                        <strong>DAOP 6</strong> dipilih sebagai Wilayah.
                        Jika Excel memiliki Resort
                        <strong>6.1 Jenar</strong>,
                        sistem akan membuat atau menggunakan Resort tersebut
                        di bawah DAOP 6.
                    </p>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection