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
                    Import data JPL dari file Excel. Sistem akan menghubungkan
                    data secara otomatis ke Wilayah Daop 6 dan Resort terkait.
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

        {{-- Form Upload --}}
        <div class="lg:col-span-2">

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

                <div class="border-b border-gray-200 px-6 py-5">
                    <h2 class="text-lg font-semibold text-[#1d2d50]">
                        Upload File Excel
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Unggah file Excel. Sistem akan menggunakan Wilayah
                        <strong>{{ $wilayahName ?? 'default yang dikonfigurasi' }}</strong>
                        secara otomatis.
                    </p>
                </div>

                <form
                    action="{{ route('admin.jpl.import.preview.store') }}"
                    method="POST"
                    enctype="multipart/form-data"
                    class="p-6"
                >

                    @csrf

                    {{-- Wilayah Default --}}
                    <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4">

                        <div class="flex items-start gap-3">

                            <div class="mt-0.5 text-blue-600">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z"
                                    />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-blue-900">
                                    Wilayah Tujuan Otomatis
                                </p>

                                <p class="mt-1 text-sm text-blue-800">
                                    {{ $wilayahName ?? 'Wilayah default belum tersedia' }}
                                </p>

                                <p class="mt-1 text-xs leading-5 text-blue-700">
                                    Wilayah ditentukan oleh konfigurasi sistem
                                    dan tidak perlu dipilih saat mengunggah Excel.
                                    Semua Resort baru akan terhubung ke Wilayah ini.
                                </p>
                            </div>

                        </div>

                    </div>

                    {{-- File Excel --}}
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

                        @error('file')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>

                    {{-- Informasi Import --}}
                    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">

                        <div class="flex gap-3">

                            <div class="mt-0.5 text-amber-600">
                                ℹ
                            </div>

                            <div>

                                <p class="font-semibold text-amber-800">
                                    Mekanisme Import
                                </p>

                                <ul class="mt-2 space-y-2 text-sm leading-5 text-amber-800">

                                    <li>
                                        • Wilayah Daop 6 ditentukan otomatis oleh sistem.
                                    </li>

                                    <li>
                                        • Resort yang sudah ada di Wilayah tujuan akan digunakan kembali.
                                    </li>

                                    <li>
                                        • Resort yang belum ada akan dibuat otomatis dan dihubungkan ke Wilayah tujuan.
                                    </li>

                                    <li>
                                        • Setiap JPL akan terhubung ke Resort berdasarkan kolom RESORT pada Excel.
                                    </li>

                                    <li>
                                        • Nilai <strong>Liar</strong> pada NO JPL merupakan data resmi dan akan dipertahankan.
                                    </li>

                                    <li>
                                        • Baris error dan duplikat akan ditampilkan pada preview, tetapi tidak akan diimpor.
                                    </li>

                                    <li>
                                        • Data tidak langsung disimpan sebelum kamu mengonfirmasi hasil preview.
                                    </li>

                                    <li>
                                        • Inventaris default akan diinisialisasi untuk setiap JPL yang berhasil dibuat.
                                    </li>

                                </ul>

                            </div>

                        </div>

                    </div>

                    {{-- Mapping Data Excel --}}
                    <div class="mb-6">

                        <h3 class="mb-3 text-sm font-semibold text-gray-800">
                            Mapping Data Excel
                        </h3>

                        <div class="overflow-x-auto rounded-xl border border-gray-200">

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
                                        <td class="px-4 py-3">KONSTRUKSI JALAN</td>
                                        <td class="px-4 py-3">konst_jalan</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">TITIK KOORDINAT</td>
                                        <td class="px-4 py-3">titik_koordinat</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">LEBAR JALAN</td>
                                        <td class="px-4 py-3">lebar_jln</td>
                                    </tr>

                                    <tr>
                                        <td class="px-4 py-3">STATUS PENJAGAAN</td>
                                        <td class="px-4 py-3">status_penjagaan</td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                    {{-- Tombol Preview --}}
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                        <a
                            href="{{ route('admin.jpl.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                        >
                            Batal
                        </a>

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

        {{-- Informasi Samping --}}
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
                            {{ $wilayahName ?? 'Belum dikonfigurasi' }}
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Ditentukan otomatis oleh sistem.
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Resort
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-700">
                            Dibaca dari Excel
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Dicari berdasarkan nama di Wilayah tujuan.
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            JPL
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-700">
                            Dibaca dari Excel
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Dihubungkan ke Resort yang sesuai.
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                            Inventaris
                        </p>

                        <p class="mt-1 text-sm font-semibold text-gray-700">
                            Inisialisasi otomatis
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Diproses untuk JPL yang berhasil diimpor.
                        </p>
                    </div>

                </div>

                <div class="mt-6 rounded-xl bg-gray-50 p-4">

                    <p class="text-sm leading-6 text-gray-600">
                        <strong>Contoh alur:</strong>

                        Sistem menggunakan Wilayah
                        <strong>
                            {{ $wilayahName ?? 'Daop 6' }}
                        </strong>.

                        Jika Excel memiliki Resort
                        <strong>6.1 Jenar</strong>,
                        sistem akan mencari Resort tersebut di Wilayah tujuan.
                        Jika belum tersedia, Resort akan dibuat secara otomatis.
                    </p>

                </div>

            </div>

            {{-- Panduan --}}
            <div class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">

                <h2 class="text-base font-semibold text-[#1d2d50]">
                    Sebelum Mengimpor
                </h2>

                <ul class="mt-4 space-y-3 text-sm text-gray-600">

                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 text-green-600">✓</span>
                        <span>Pastikan file Excel menggunakan format kolom yang didukung.</span>
                    </li>

                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 text-green-600">✓</span>
                        <span>Periksa nama Resort dan identitas JPL.</span>
                    </li>

                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 text-green-600">✓</span>
                        <span>Periksa semua baris pada halaman preview sebelum konfirmasi.</span>
                    </li>

                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 text-green-600">✓</span>
                        <span>Pastikan data yang akan diimpor memang ditujukan untuk Wilayah default.</span>
                    </li>

                </ul>

            </div>

        </div>

    </div>

</div>

@endsection
