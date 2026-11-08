@extends('layouts.admin')

@section('title', 'Master Inventaris')

@section('content')
<div class="p-4 md:p-6">

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Master Inventaris
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Monitoring kondisi, penempatan, dan tindak lanjut inventaris berdasarkan JPL.
            </p>
        </div>

        <a href="{{ route('admin.inventaris.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#1d2d50] px-5 py-3 text-sm font-semibold text-white hover:bg-[#14213d]">
            <span class="text-lg">+</span>
            Tambah Inventaris
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

    {{-- STATISTIK GLOBAL --}}
    @php
        $cards = [
            [
                'label' => 'Total Jenis Inventaris',
                'value' => $statistik['total_jenis'],
                'color' => 'text-[#1d2d50]',
            ],
            [
                'label' => 'Total Unit Barang',
                'value' => $statistik['total_unit'],
                'color' => 'text-[#1d2d50]',
            ],
            [
                'label' => 'Inventaris Aktif',
                'value' => $statistik['aktif'],
                'color' => 'text-green-600',
            ],
            [
                'label' => 'Kondisi Baik',
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
                'label' => 'Hilang',
                'value' => $statistik['hilang'],
                'color' => 'text-rose-700',
            ],
            [
                'label' => 'Belum Diperiksa',
                'value' => $statistik['belum_diperiksa'],
                'color' => 'text-gray-600',
            ],
            [
                'label' => 'Perlu Tinjauan Admin',
                'value' => $statistik['perlu_tinjauan'],
                'color' => 'text-orange-600',
            ],
        ];
    @endphp

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-5">
        @foreach($cards as $card)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500 sm:text-sm">
                    {{ $card['label'] }}
                </p>

                <p class="mt-2 text-2xl font-bold {{ $card['color'] }}">
                    {{ number_format($card['value']) }}
                </p>

                <p class="mt-1 text-[11px] text-gray-400">
                    Seluruh database
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
                    href="{{ route('admin.inventaris.index', array_filter([
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

        <form method="GET"
              action="{{ route('admin.inventaris.index') }}"
              class="flex flex-col gap-3 sm:flex-row">

            <input type="hidden" name="status" value="{{ $status }}">

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari kode atau nama JPL..."
                class="min-w-0 flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none"
            >

            <button
                type="submit"
                class="rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#14213d]"
            >
                Cari JPL
            </button>

            @if($search !== '')
                <a
                    href="{{ route('admin.inventaris.index', ['status' => $status]) }}"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-center text-sm font-semibold text-gray-600 hover:bg-gray-50"
                >
                    Reset
                </a>
            @endif
        </form>
    </div>

    {{-- DAFTAR JPL --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-bold text-[#1d2d50]">
                Inventaris per JPL
            </h2>

            <p class="mt-1 text-xs text-gray-500">
                Klik Detail JPL untuk melihat barang, kondisi, dan status peninjauan inventaris.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-sm">

                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-4">JPL</th>
                        <th class="px-5 py-4 text-center">Jenis</th>
                        <th class="px-5 py-4 text-center">Unit</th>
                        <th class="px-5 py-4 text-center">Aktif</th>
                        <th class="px-5 py-4 text-center">Baik</th>
                        <th class="px-5 py-4 text-center">Rusak</th>
                        <th class="px-5 py-4 text-center">Perlu Tinjauan</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">

                    @forelse($jpls as $jpl)
                        <tr class="transition hover:bg-gray-50">

                            {{-- JPL --}}
                            <td class="px-5 py-4">
                                <p class="font-semibold text-[#1d2d50]">
                                    {{ $jpl->kode_jpl }}
                                </p>

                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $jpl->nama_jpl }}
                                </p>
                            </td>

                            {{-- TOTAL JENIS --}}
                            <td class="px-5 py-4 text-center">
                                {{ number_format($jpl->inventaris_count) }}
                            </td>

                            {{-- TOTAL UNIT --}}
                            <td class="px-5 py-4 text-center">
                                {{ number_format($jpl->total_unit ?? 0) }}
                            </td>

                            {{-- AKTIF --}}
                            <td class="px-5 py-4 text-center font-semibold text-green-600">
                                {{ number_format($jpl->inventaris_aktif_count) }}
                            </td>

                            {{-- BAIK --}}
                            <td class="px-5 py-4 text-center text-green-600">
                                {{ number_format($jpl->inventaris_baik_count) }}
                            </td>

                            {{-- RUSAK --}}
                            <td class="px-5 py-4 text-center font-semibold text-red-600">
                                {{ number_format($jpl->inventaris_rusak_count) }}
                            </td>

                            {{-- PERLU TINJAUAN --}}
                            <td class="px-5 py-4 text-center">
                                @if($jpl->inventaris_perlu_tinjauan_count > 0)
                                    <span class="inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-bold text-orange-700">
                                        {{ number_format($jpl->inventaris_perlu_tinjauan_count) }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">
                                        0
                                    </span>
                                @endif
                            </td>

                            {{-- AKSI --}}
                            <td class="px-5 py-4 text-right">
                                <a
                                    href="{{ route('admin.inventaris.jpl.show', $jpl->id) }}"
                                    class="inline-flex rounded-lg bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                >
                                    Detail JPL →
                                </a>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-gray-500">
                                Tidak ada JPL yang sesuai dengan pencarian atau filter.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($jpls->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $jpls->links() }}
            </div>
        @endif
    </div>

    {{-- KETERANGAN --}}
    <div class="mt-4 rounded-xl border border-gray-200 bg-gray-50 p-4">
        <p class="text-xs leading-relaxed text-gray-600">
            <strong>Status Aktif</strong> menunjukkan ketersediaan inventaris dalam sistem,
            sedangkan <strong>Kondisi</strong> menunjukkan hasil pemeriksaan terakhir.
            <strong>Perlu Tinjauan</strong> menunjukkan adanya laporan masalah yang belum
            diselesaikan oleh admin. Angka kondisi dan tinjauan dihitung berdasarkan jenis
            inventaris, bukan jumlah unit.
        </p>
    </div>

</div>
@endsection
