@extends('layouts.superadmin')

@section('title', 'Detail Inventaris')
@section('page-title', 'Detail Inventaris')

@section('content')
<div class="p-4 md:p-6">

    <div class="mb-5">
        <a
            href="{{ route('superadmin.inventaris.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali</span>
        </a>
    </div>

    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                {{ $inventaris->nama_barang }}
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                {{ $inventaris->kode_barang ?: 'Tanpa kode barang' }}
            </p>
        </div>

    </div>

    @php
        $kondisi = $inventaris->kondisi_terakhir;

        $kondisiLabel = [
            'baik' => 'Baik',
            'perlu_perbaikan' => 'Perlu Perbaikan',
            'rusak' => 'Rusak',
            'hilang' => 'Hilang',
        ][$kondisi] ?? 'Belum Diperiksa';

        $kategoriLabel = [
            'perangkat_pengamanan' => 'Perangkat Pengamanan',
            'alat_pendukung' => 'Alat Pendukung',
            'inventaris_pjl' => 'Inventaris PJL',
        ][$inventaris->kategori] ?? $inventaris->kategori;
    @endphp

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        {{-- INFORMASI ALAT --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="mb-5 text-lg font-bold text-[#1d2d50]">
                Informasi Inventaris
            </h2>

            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <dt class="text-xs text-gray-500">Nama Barang</dt>
                    <dd class="mt-1 font-semibold">{{ $inventaris->nama_barang }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Kode Barang</dt>
                    <dd class="mt-1 font-semibold">{{ $inventaris->kode_barang ?: '-' }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Kategori</dt>
                    <dd class="mt-1 font-semibold">{{ $kategoriLabel }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">JPL</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $inventaris->jpl?->kode_jpl ?? '-' }} -
                        {{ $inventaris->jpl?->nama_jpl ?? 'Belum ditentukan' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Lokasi</dt>
                    <dd class="mt-1 font-semibold">{{ $inventaris->lokasi }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Jumlah Terdaftar</dt>
                    <dd class="mt-1 font-semibold">
                        {{ number_format($inventaris->jumlah) }} unit
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Jumlah Aktual Terakhir</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $inventaris->jumlah_aktual_terakhir !== null
                            ? number_format($inventaris->jumlah_aktual_terakhir) . ' unit'
                            : 'Belum diperiksa' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Status Inventaris</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $inventaris->is_active ? 'Aktif' : 'Nonaktif' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Kondisi Terakhir</dt>
                    <dd class="mt-1 font-semibold">{{ $kondisiLabel }}</dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Status Tinjauan</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $inventaris->status_tinjauan
                            ? ucwords(str_replace('_', ' ', $inventaris->status_tinjauan))
                            : 'Belum ada tinjauan' }}
                    </dd>
                </div>
            </dl>

            <div class="mt-6 border-t border-gray-100 pt-5">
                <p class="text-xs font-semibold text-gray-500">
                    Keterangan Inventaris
                </p>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-700">
                    {{ $inventaris->keterangan ?: 'Tidak ada keterangan tambahan.' }}
                </p>
            </div>
        </div>

        {{-- TINDAK LANJUT --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#1d2d50]">
                Tindak Lanjut
            </h2>

            <p class="mt-2 text-sm text-gray-500">
                Ringkasan masalah dan peninjauan terakhir.
            </p>

            <div class="mt-5 space-y-5">
                <div>
                    <p class="text-xs font-semibold text-gray-500">
                        Catatan Masalah
                    </p>
                    <p class="mt-2 whitespace-pre-line text-sm text-gray-700">
                        {{ $inventaris->catatan_masalah ?: 'Belum ada laporan masalah.' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500">
                        Dilaporkan Pada
                    </p>
                    <p class="mt-1 text-sm text-gray-700">
                        {{ $inventaris->dilaporkan_pada
                            ? \Carbon\Carbon::parse($inventaris->dilaporkan_pada)->format('d M Y H:i')
                            : '-' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500">
                        Catatan Tinjauan
                    </p>
                    <p class="mt-2 whitespace-pre-line text-sm text-gray-700">
                        {{ $inventaris->catatan_tinjauan ?: 'Belum ada catatan tindak lanjut.' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-gray-500">
                        Ditinjau Pada
                    </p>
                    <p class="mt-1 text-sm text-gray-700">
                        {{ $inventaris->ditinjau_pada
                            ? \Carbon\Carbon::parse($inventaris->ditinjau_pada)->format('d M Y H:i')
                            : '-' }}
                    </p>
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-4 text-xs leading-relaxed text-blue-800">
                Jika inventaris memiliki riwayat pemeriksaan, tindakan hapus akan menonaktifkan inventaris agar riwayat tetap terjaga.
            </div>
        </div>

    </div>
</div>
@endsection
