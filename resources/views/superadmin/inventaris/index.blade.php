@extends('layouts.superadmin')

@section('title', 'Master Inventaris')
@section('page-title', 'Master Inventaris')

@section('content')
<div>

    {{-- HEADER --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Pemantauan operasional · Baca saja</p>
            <h1 class="text-2xl font-bold text-[#1d2d50]">
                Master Inventaris
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Monitoring kondisi dan penempatan inventaris berdasarkan JPL.
            </p>
        </div>

    </div>

    @if(session('success'))
        <div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- STATISTIK GLOBAL --}}
    @php
        $cards = [
            ['label' => 'Jenis inventaris', 'value' => $statistik['total_jenis'], 'color' => 'text-slate-900'],
            ['label' => 'Unit barang', 'value' => $statistik['total_unit'], 'color' => 'text-slate-900'],
            ['label' => 'Inventaris aktif', 'value' => $statistik['aktif'], 'color' => 'text-emerald-700'],
        ];
    @endphp

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
        @foreach($cards as $card)
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium text-gray-500 sm:text-sm">
                    {{ $card['label'] }}
                </p>
                <p class="mt-2 text-2xl font-bold {{ $card['color'] }}">
                    {{ number_format($card['value']) }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Ringkasan seluruh data</p>
            </div>
        @endforeach
    </div>

    <section aria-label="Ringkasan kondisi inventaris" class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-sm font-semibold text-slate-800">Kondisi pemeriksaan terakhir</h2>
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-5">
            @foreach ([['Baik', $statistik['baik'], 'text-emerald-700'], ['Perlu perbaikan', $statistik['perlu_perbaikan'], 'text-amber-700'], ['Rusak', $statistik['rusak'], 'text-red-700'], ['Hilang', $statistik['hilang'], 'text-rose-700'], ['Belum diperiksa', $statistik['belum_diperiksa'], 'text-slate-600']] as [$label, $value, $color])
                <div class="border-l-2 border-slate-200 pl-3">
                    <p class="text-xs text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-lg font-semibold {{ $color }}">{{ number_format($value) }}</p>
                </div>
            @endforeach
        </div>
    </section>

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
                    href="{{ route('superadmin.inventaris.index', array_filter(['status' => $key, 'search' => $search])) }}"
                    class="rounded-lg px-4 py-2 text-xs font-semibold transition
                    {{ $status === $key
                        ? 'bg-[#1d2d50] text-white'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('superadmin.inventaris.index') }}"
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
            <table class="w-full min-w-[900px] table-fixed text-sm">
                <colgroup>
                    <col class="w-[12%]">
                    <col class="w-[22%]">
                    <col class="w-[10%]">
                    <col class="w-[10%]">
                    <col class="w-[10%]">
                    <col class="w-[10%]">
                    <col class="w-[10%]">
                    <col class="w-[16%]">
                </colgroup>
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-5 py-4 text-center">Kode JPL</th>
                        <th class="px-5 py-4 text-center">Nama JPL</th>
                        <th class="px-5 py-4 text-center">Jenis</th>
                        <th class="px-5 py-4 text-center">Unit</th>
                        <th class="px-5 py-4 text-center">Aktif</th>
                        <th class="px-5 py-4 text-center">Baik</th>
                        <th class="px-5 py-4 text-center">Rusak</th>
                        <th class="whitespace-nowrap px-5 py-4 text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($jpls as $jpl)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-4 font-semibold text-[#1d2d50] text-center">
                                {{ $jpl->kode_jpl }}
                            </td>

                            <td class="px-5 py-4 text-xs text-gray-500 text-center">
                                {{ $jpl->nama_jpl }}
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

                            <td class="px-5 py-4 text-center">
                                <a href="{{ route('superadmin.inventaris.jpl.show', $jpl->id) }}"
                                   class="inline-flex rounded-lg bg-blue-50 px-4 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                    Detail JPL
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
