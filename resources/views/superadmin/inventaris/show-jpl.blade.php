@extends('layouts.superadmin')

@section('title', 'Inventaris JPL')
@section('page-title', 'Inventaris JPL')

@section('content')
<div class="p-4 md:p-6">

    {{-- BREADCRUMB --}}
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

    </div>

    {{-- STATISTIK --}}
    @php
        $cards = [
            ['label' => 'Jenis Inventaris', 'value' => $statistik['total'], 'color' => 'text-[#1d2d50]'],
            ['label' => 'Total Unit', 'value' => $statistik['unit'], 'color' => 'text-[#1d2d50]'],
            ['label' => 'Aktif', 'value' => $statistik['aktif'], 'color' => 'text-green-600'],
            ['label' => 'Baik', 'value' => $statistik['baik'], 'color' => 'text-green-600'],
            ['label' => 'Perlu Perbaikan', 'value' => $statistik['perlu_perbaikan'], 'color' => 'text-amber-600'],
            ['label' => 'Rusak', 'value' => $statistik['rusak'], 'color' => 'text-red-600'],
        ];
    @endphp

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-3">
        @foreach($cards as $card)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ $card['label'] }}</p>
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
        ];
    @endphp

    <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach($filters as $key => $label)
                <a
                    href="{{ route('superadmin.inventaris.jpl.show', array_merge(
                        ['jpl' => $jpl->id],
                        array_filter(['status' => $key, 'search' => $search])
                    )) }}"
                    class="rounded-lg px-4 py-2 text-xs font-semibold
                    {{ $status === $key
                        ? 'bg-[#1d2d50] text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET"
              action="{{ route('superadmin.inventaris.jpl.show', $jpl->id) }}"
              class="flex flex-col gap-3 sm:flex-row">

            <input type="hidden" name="status" value="{{ $status }}">

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari nama atau kode barang..."
                class="min-w-0 flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:outline-none focus:border-blue-500"
            >

            <button type="submit"
                    class="rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white">
                Cari Barang
            </button>
        </form>
    </div>

    {{-- TABEL INVENTARIS --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-bold text-[#1d2d50]">
                Daftar Alat dan Inventaris
            </h2>
            <p class="mt-1 text-xs text-gray-500">
                Pilih alat untuk melihat detail kondisi dan tindak lanjut.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] table-fixed text-sm">
                <colgroup>
                    <col class="w-[28%]">
                    <col class="w-[17%]">
                    <col class="w-[11%]">
                    <col class="w-[16%]">
                    <col class="w-[12%]">
                    <col class="w-[16%]">
                </colgroup>
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-4">Barang</th>
                        <th class="px-5 py-4">Kategori</th>
                        <th class="px-5 py-4 text-center">Jumlah</th>
                        <th class="px-5 py-4">Kondisi</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="whitespace-nowrap px-5 py-4 text-center">Aksi</th>
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
                        @endphp

                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-[#1d2d50]">
                                    {{ $item->nama_barang }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $item->kode_barang ?: 'Tanpa kode' }}
                                </p>
                            </td>

                            <td class="px-5 py-4 text-gray-600">
                                {{ $kategoriLabel }}
                            </td>

                            <td class="px-5 py-4 text-center font-semibold">
                                {{ number_format($item->jumlah) }}
                            </td>

                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $kondisiClass }}">
                                    {{ $kondisiLabel }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold
                                    {{ $item->is_active
                                        ? 'bg-green-50 text-green-700'
                                        : 'bg-gray-100 text-gray-600' }}">
                                    {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            <td class="px-5 py-4 text-center">
                                <a
                                    href="{{ route('superadmin.inventaris.show', $item->id) }}"
                                    class="inline-flex rounded-lg bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                                >
                                    Detail Alat
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-gray-500">
                                Tidak ada inventaris yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($inventaris->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $inventaris->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
