@extends('layouts.admin')

@section('title', 'Inventaris JPL')

@section('content')
<div class="p-4 md:p-6">

    {{-- BREADCRUMB --}}
    <div class="mb-5">
        <a
            href="{{ route('admin.inventaris.index') }}"
            class="text-sm font-medium text-blue-600 hover:underline"
        >
            ← Kembali ke Master Inventaris
        </a>
    </div>

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Inventaris {{ $jpl->kode_jpl }}
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                {{ $jpl->nama_jpl }}
            </p>
        </div>

        <a
            href="{{ route('admin.inventaris.create') }}"
            class="rounded-xl bg-[#1d2d50] px-5 py-3 text-center text-sm font-semibold text-white hover:bg-[#14213d]"
        >
            + Tambah Inventaris
        </a>
    </div>

    {{-- NOTIFIKASI --}}
    @if(session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- STATISTIK --}}
    @php
        $cards = [
            [
                'label' => 'Jenis Inventaris',
                'value' => $statistik['total'],
                'color' => 'text-[#1d2d50]',
            ],
            [
                'label' => 'Total Unit',
                'value' => $statistik['unit'],
                'color' => 'text-[#1d2d50]',
            ],
            [
                'label' => 'Aktif',
                'value' => $statistik['aktif'],
                'color' => 'text-green-600',
            ],
            [
                'label' => 'Baik',
                'value' => $statistik['baik'],
                'color' => 'text-green-600',
            ],
            [
                'label' => 'Perlu Perbaikan',
                'value' => $statistik['perlu_perbaikan'],
                'color' => 'text-amber-600',
            ],
            [
                'label' => 'Rusak',
                'value' => $statistik['rusak'],
                'color' => 'text-red-600',
            ],
            [
                'label' => 'Perlu Tinjauan',
                'value' => $statistik['perlu_tinjauan'],
                'color' => 'text-orange-600',
            ],
        ];
    @endphp

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach($cards as $card)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500">
                    {{ $card['label'] }}
                </p>

                <p class="mt-2 text-2xl font-bold {{ $card['color'] }}">
                    {{ number_format($card['value']) }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- FILTER --}}
    @php
        $filters = [
            'semua' => 'Semua',
            'aktif' => 'Aktif',
            'nonaktif' => 'Nonaktif',
            'baik' => 'Baik',
            'perlu_perbaikan' => 'Perlu Perbaikan',
            'rusak' => 'Rusak',
            'hilang' => 'Hilang',
            'perlu_tinjauan' => 'Perlu Tinjauan',
        ];
    @endphp

    <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">

        <div class="mb-4 flex flex-wrap gap-2">
            @foreach($filters as $key => $label)
                <a
                    href="{{ route('admin.inventaris.jpl.show', array_filter([
                        'jpl' => $jpl->id,
                        'status' => $key,
                        'search' => $search,
                    ], fn ($value) => $value !== null && $value !== '')) }}"
                    class="rounded-lg px-4 py-2 text-xs font-semibold transition
                    {{ $status === $key
                        ? 'bg-[#1d2d50] text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                >
                    {{ $label }}

                    @if($key === 'perlu_tinjauan' && $statistik['perlu_tinjauan'] > 0)
                        <span class="ml-1 rounded-full bg-orange-100 px-2 py-0.5 text-orange-700">
                            {{ $statistik['perlu_tinjauan'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- PENCARIAN --}}
        <form
            method="GET"
            action="{{ route('admin.inventaris.jpl.show', $jpl->id) }}"
            class="flex flex-col gap-3 sm:flex-row"
        >

            <input
                type="hidden"
                name="status"
                value="{{ $status }}"
            >

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari nama atau kode barang..."
                class="min-w-0 flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none"
            >

            <button
                type="submit"
                class="rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#14213d]"
            >
                Cari Barang
            </button>

            @if($search !== '')
                <a
                    href="{{ route('admin.inventaris.jpl.show', [
                        'jpl' => $jpl->id,
                        'status' => $status,
                    ]) }}"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-center text-sm font-semibold text-gray-600 hover:bg-gray-50"
                >
                    Reset
                </a>
            @endif

        </form>
    </div>

    {{-- TABEL INVENTARIS --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-bold text-[#1d2d50]">
                Daftar Alat dan Inventaris
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Pilih alat untuk melihat detail kondisi dan melakukan tindak lanjut peninjauan.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1050px] text-sm">

                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-4">Barang</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4 text-center">Jumlah</th>
                        <th class="px-5 py-4">Kondisi</th>
                        <th class="px-5 py-4">Status Inventaris</th>
                        <th class="px-5 py-4">Status Tinjauan</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">

                    @forelse($inventaris as $item)

                        @php
                            $kondisi = $item->kondisi_terakhir;

                            $kondisiLabel = [
                                'baik' => 'Baik',
                                'perlu_perbaikan' => 'Perlu Perbaikan',
                                'rusak' => 'Rusak',
                                'hilang' => 'Hilang',
                            ][$kondisi] ?? 'Belum Diperiksa';

                            $kondisiClass = match($kondisi) {
                                'baik' => 'bg-green-50 text-green-700',
                                'perlu_perbaikan' => 'bg-amber-50 text-amber-700',
                                'rusak', 'hilang' => 'bg-red-50 text-red-700',
                                default => 'bg-gray-100 text-gray-600',
                            };

                            $kategoriLabel = [
                                'perangkat_pengamanan' => 'Perangkat Pengamanan',
                                'alat_pendukung' => 'Alat Pendukung',
                                'inventaris_pjl' => 'Inventaris PJL',
                            ][$item->kategori] ?? $item->kategori;

                            $perluTinjauan = $item->status_tinjauan === 'perlu_tinjauan';
                        @endphp

                        <tr class="transition hover:bg-gray-50">

                            {{-- BARANG --}}
                            <td class="px-5 py-4">
                                <p class="font-semibold text-[#1d2d50]">
                                    {{ $item->nama_barang }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $item->kode_barang ?: 'Tanpa kode' }}
                                </p>
                            </td>

                            {{-- KATEGORI --}}
                            <td class="px-5 py-4 text-gray-600">
                                {{ $kategoriLabel }}
                            </td>

                            {{-- JUMLAH --}}
                            <td class="px-5 py-4 text-center font-semibold">
                                {{ number_format($item->jumlah) }}
                            </td>

                            {{-- KONDISI --}}
                            <td class="px-5 py-4">
                                <span class="inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-semibold {{ $kondisiClass }}">
                                    {{ $kondisiLabel }}
                                </span>
                            </td>

                            {{-- STATUS AKTIF --}}
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $item->is_active
                                        ? 'bg-green-50 text-green-700'
                                        : 'bg-gray-100 text-gray-600' }}">
                                    {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            {{-- STATUS TINJAUAN --}}
                            <td class="px-5 py-4">
                                @if($perluTinjauan)
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
                                        Perlu Tinjauan
                                    </span>
                                @else
                                    <span class="inline-flex whitespace-nowrap rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                                        Normal
                                    </span>
                                @endif
                            </td>

                            {{-- AKSI --}}
                            <td class="px-5 py-4 text-right">
                                <a
                                    href="{{ route('admin.inventaris.show', $item->id) }}"
                                    class="inline-flex whitespace-nowrap rounded-lg px-4 py-2 text-xs font-semibold transition
                                    {{ $perluTinjauan
                                        ? 'bg-orange-100 text-orange-700 hover:bg-orange-200'
                                        : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}"
                                >
                                    {{ $perluTinjauan ? 'Tinjau Sekarang →' : 'Detail Alat →' }}
                                </a>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-500">
                                Tidak ada inventaris yang sesuai dengan filter.
                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($inventaris->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $inventaris->links() }}
            </div>
        @endif

    </div>

    {{-- KETERANGAN --}}
    <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
        <p class="text-xs leading-relaxed text-gray-600">
            <strong>Kondisi</strong> menunjukkan hasil pemeriksaan terakhir petugas.
            <strong>Status Inventaris</strong> menunjukkan apakah barang masih aktif
            dalam sistem. <strong>Status Tinjauan</strong> menunjukkan apakah laporan
            masalah sudah ditindaklanjuti oleh admin.
        </p>
    </div>

</div>
@endsection
