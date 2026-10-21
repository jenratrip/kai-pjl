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
                Monitoring kondisi dan penempatan inventaris berdasarkan JPL.
            </p>
        </div>

        <a href="{{ route('admin.inventaris.create') }}"
           class="inline-flex items-center justify-center gap-2 rounded-xl bg-[#1d2d50] px-5 py-3 text-sm font-semibold text-white hover:bg-[#14213d]">
            <span class="text-lg">+</span>
            Tambah Inventaris
        </a>
    </div>

    @if(session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- STATISTIK GLOBAL --}}
    @php
        $cards = [
            ['label' => 'Total Jenis Inventaris', 'value' => $statistik['total_jenis'], 'color' => 'text-[#1d2d50]'],
            ['label' => 'Total Unit Barang', 'value' => $statistik['total_unit'], 'color' => 'text-[#1d2d50]'],
            ['label' => 'Inventaris Aktif', 'value' => $statistik['aktif'], 'color' => 'text-green-600'],
            ['label' => 'Kondisi Baik', 'value' => $statistik['baik'], 'color' => 'text-green-600'],
            ['label' => 'Perlu Perbaikan', 'value' => $statistik['perlu_perbaikan'], 'color' => 'text-amber-600'],
            ['label' => 'Rusak', 'value' => $statistik['rusak'], 'color' => 'text-red-600'],
            ['label' => 'Hilang', 'value' => $statistik['hilang'], 'color' => 'text-rose-700'],
            ['label' => 'Belum Diperiksa', 'value' => $statistik['belum_diperiksa'], 'color' => 'text-gray-600'],
        ];
    @endphp

    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
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
        ];
    @endphp

    <div class="mb-5 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="mb-4 flex flex-wrap gap-2">
            @foreach($filters as $key => $label)
                <a
                    href="{{ route('admin.inventaris.index', array_filter(['status' => $key, 'search' => $search])) }}"
                    class="rounded-lg px-4 py-2 text-xs font-semibold transition
                    {{ $status === $key
                        ? 'bg-[#1d2d50] text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('admin.inventaris.index') }}"
              class="flex flex-col gap-3 sm:flex-row">
            <input type="hidden" name="status" value="{{ $status }}">

            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari kode atau nama JPL..."
                class="min-w-0 flex-1 rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:outline-none"
            >

            <button type="submit"
                    class="rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white">
                Cari JPL
            </button>
        </form>
    </div>

    {{-- DAFTAR JPL --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-bold text-[#1d2d50]">Inventaris per JPL</h2>
            <p class="mt-1 text-xs text-gray-500">
                Klik Detail JPL untuk melihat barang dan kondisi inventaris.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[850px] text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-4">JPL</th>
                        <th class="px-5 py-4 text-center">Jenis</th>
                        <th class="px-5 py-4 text-center">Unit</th>
                        <th class="px-5 py-4 text-center">Aktif</th>
                        <th class="px-5 py-4 text-center">Baik</th>
                        <th class="px-5 py-4 text-center">Rusak</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($jpls as $jpl)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-[#1d2d50]">
                                    {{ $jpl->kode_jpl }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $jpl->nama_jpl }}
                                </p>
                            </td>

                            <td class="px-5 py-4 text-center">
                                {{ number_format($jpl->inventaris_count) }}
                            </td>

                            <td class="px-5 py-4 text-center">
                                {{ number_format($jpl->total_unit ?? 0) }}
                            </td>

                            <td class="px-5 py-4 text-center font-semibold text-green-600">
                                {{ $jpl->inventaris_aktif_count }}
                            </td>

                            <td class="px-5 py-4 text-center text-green-600">
                                {{ $jpl->inventaris_baik_count }}
                            </td>

                            <td class="px-5 py-4 text-center font-semibold text-red-600">
                                {{ $jpl->inventaris_rusak_count }}
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.inventaris.jpl.show', $jpl->id) }}"
                                   class="inline-flex rounded-lg bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                    Detail JPL →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-500">
                                Tidak ada JPL yang sesuai dengan pencarian atau filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jpls->hasPages())
            <div class="border-t border-gray-100 px-5 py-4">
                {{ $jpls->links() }}
            </div>
        @endif
    </div>

    <p class="mt-4 text-xs text-gray-500">
        Status aktif menunjukkan ketersediaan inventaris dalam sistem,
        sedangkan kondisi menunjukkan hasil pemeriksaan terakhir.
        Angka kondisi dihitung berdasarkan jenis inventaris, bukan jumlah unit.
    </p>
</div>
@endsection
