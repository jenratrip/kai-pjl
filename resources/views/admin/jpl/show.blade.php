@extends('layouts.admin')

@section('title', 'Detail JPL')

@section('content')
<div class="p-4 md:p-6">

    {{-- BREADCRUMB --}}
    <div class="mb-5">
        <a href="{{ route('admin.jpl.index') }}"
           class="text-sm font-semibold text-blue-600 hover:underline">
            ← Kembali ke Master Data JPL
        </a>
    </div>

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="rounded-full px-3 py-1 text-xs font-semibold
                    {{ $jpl->is_active
                        ? 'bg-green-50 text-green-700'
                        : 'bg-gray-100 text-gray-600' }}">
                    {{ $jpl->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>

                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    {{ $jpl->kode_jpl ?: 'Tanpa kode JPL' }}
                </span>
            </div>

            <h1 class="text-2xl font-bold text-[#1d2d50]">
                {{ $jpl->nama_jpl }}
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                {{ $jpl->lokasi ?: 'Lokasi belum diisi' }}
                @if($jpl->kota_kabupaten)
                    — {{ $jpl->kota_kabupaten }}
                @endif
            </p>
        </div>

        <a href="{{ route('admin.jpl.edit', $jpl->id) }}"
           class="rounded-xl bg-[#1d2d50] px-5 py-3 text-center text-sm font-semibold text-white hover:bg-[#14213d]">
            Edit Data JPL
        </a>
    </div>

    {{-- STATISTIK --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs text-gray-500">KM / HM</p>
            <p class="mt-2 text-xl font-bold text-[#1d2d50]">
                {{ $jpl->km_hm ?: '-' }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs text-gray-500">Resort</p>
            <p class="mt-2 text-xl font-bold text-[#1d2d50]">
                {{ $jpl->resort?->nama_resort ?? '-' }}
            </p>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-xs text-gray-500">Status Penjagaan</p>
            <p class="mt-2 text-xl font-bold text-[#1d2d50]">
                {{ $jpl->status_penjagaan ?: 'Belum diisi' }}
            </p>
        </div>
    </div>

    {{-- INFORMASI JPL --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="mb-5 text-lg font-bold text-[#1d2d50]">
                Informasi Operasional JPL
            </h2>

            <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                <div>
                    <dt class="text-xs text-gray-500">Nomor JPL</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->kode_jpl ?: '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Nama JPL</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->nama_jpl ?: '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">KM / HM</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->km_hm ?: '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Antara</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->antara ?: '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Status Penjagaan</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->status_penjagaan ?: '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Jenis Jalur KA</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->jenis_jalur_ka ?: '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Konst Jalan</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->konst_jalan ?: '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Lebar Jalan</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->lebar_jln !== null ? $jpl->lebar_jln . ' m' : '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Nama Jalan / Daerah</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->lokasi ?: '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Kota / Kabupaten</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->kota_kabupaten ?: '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Provinsi</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->provinsi ?: '-' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs text-gray-500">Status Data</dt>
                    <dd class="mt-1 font-semibold">
                        {{ $jpl->is_active ? 'Aktif' : 'Nonaktif' }}
                    </dd>
                </div>
            </dl>

            <div class="mt-6 border-t border-gray-100 pt-5">
                <h3 class="text-sm font-bold text-[#1d2d50]">
                    Titik Koordinat
                </h3>

                <p class="mt-2 break-words text-sm text-gray-700">
                    {{ $jpl->titik_koordinat ?: 'Koordinat belum diisi.' }}
                </p>
            </div>

            <div class="mt-5 border-t border-gray-100 pt-5">
                <h3 class="text-sm font-bold text-[#1d2d50]">
                    Keterangan
                </h3>

                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-700">
                    {{ $jpl->keterangan ?: 'Tidak ada keterangan tambahan.' }}
                </p>
            </div>
        </div>

        {{-- INFORMASI WILAYAH --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-[#1d2d50]">
                Informasi Wilayah
            </h2>

            <div class="mt-5 space-y-5">
                <div>
                    <p class="text-xs text-gray-500">Resort</p>
                    <p class="mt-1 font-semibold text-gray-800">
                        {{ $jpl->resort?->nama_resort ?? 'Belum ditentukan' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Wilayah / Daop</p>
                    <p class="mt-1 font-semibold text-gray-800">
                        {{ $jpl->resort?->wilayah?->nama_wilayah ?? 'Belum ditentukan' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Dibuat Pada</p>
                    <p class="mt-1 text-sm text-gray-700">
                        {{ $jpl->created_at?->format('d M Y H:i') ?? '-' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Terakhir Diperbarui</p>
                    <p class="mt-1 text-sm text-gray-700">
                        {{ $jpl->updated_at?->format('d M Y H:i') ?? '-' }}
                    </p>
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-blue-100 bg-blue-50 p-4">
                <p class="text-xs leading-relaxed text-blue-800">
                    Data Resort dan Wilayah ditampilkan melalui relasi JPL.
                    Pengelolaannya tetap dilakukan melalui halaman Master Resort.
                </p>
            </div>
        </div>

    </div>

    {{-- INVENTARIS JPL --}}
    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold text-[#1d2d50]">
                    Inventaris JPL
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Lihat daftar perlengkapan yang terhubung dengan JPL ini.
                </p>
            </div>

            <a href="{{ route('admin.inventaris.jpl.show', $jpl->id) }}"
               class="rounded-lg border border-gray-300 px-4 py-2.5 text-center text-sm font-semibold text-[#1d2d50] hover:bg-gray-50">
                Lihat Inventaris →
            </a>
        </div>
    </div>

</div>
@endsection
