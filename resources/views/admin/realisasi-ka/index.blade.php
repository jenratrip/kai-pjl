@extends('layouts.admin')

@section('title', 'Realisasi KA')

@section('content')
<div class="mx-auto max-w-7xl px-5 py-8 sm:px-8">

    <div class="mb-6">
        <h1 class="text-3xl font-bold text-[#1d2d50]">
            Realisasi KA
        </h1>
        <p class="mt-2 text-slate-500">
            Pantau realisasi perjalanan kereta api dari seluruh petugas PJL.
        </p>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Total Realisasi', $totalRealisasi],
            ['KA Jalan', $totalJalan],
            ['KA Batal', $totalBatal],
            ['Belum Direalisasikan', $totalBelum],
        ] as [$label, $jumlah])
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-3xl font-bold text-[#1d2d50]">
                    {{ $jumlah }}
                </p>
            </div>
        @endforeach
    </div>

    <form method="GET" class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <input
                type="text"
                name="q"
                value="{{ request('q') }}"
                placeholder="Cari petugas, nomor KA, atau JPL"
                class="rounded-lg border border-slate-300 px-3 py-2"
            >

            <input
                type="date"
                name="tanggal"
                value="{{ request('tanggal') }}"
                class="rounded-lg border border-slate-300 px-3 py-2"
            >

            <select name="jpl_id" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Semua JPL</option>
                @foreach ($jpls as $jpl)
                    <option
                        value="{{ $jpl->id }}"
                        @selected((string) request('jpl_id') === (string) $jpl->id)
                    >
                        {{ $jpl->kode_jpl }} — {{ $jpl->nama_jpl }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Semua Status</option>
                <option value="jalan" @selected(request('status') === 'jalan')>
                    Jalan
                </option>
                <option value="batal" @selected(request('status') === 'batal')>
                    Batal
                </option>
                <option value="belum" @selected(request('status') === 'belum')>
                    Belum
                </option>
            </select>
        </div>

        <div class="mt-4 flex gap-3">
            <button class="rounded-lg bg-[#1d2d50] px-5 py-2 font-semibold text-white">
                Terapkan Filter
            </button>

            <a
                href="{{ route('admin.realisasi-ka.index') }}"
                class="rounded-lg border border-slate-300 px-5 py-2 text-slate-700"
            >
                Reset
            </a>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-3 text-left">No.</th>
                    <th class="px-4 py-3 text-left">Tanggal Dinas</th>
                    <th class="px-4 py-3 text-left">Petugas</th>
                    <th class="px-4 py-3 text-left">JPL</th>
                    <th class="px-4 py-3 text-left">Kereta</th>
                    <th class="px-4 py-3 text-left">Jadwal</th>
                    <th class="px-4 py-3 text-left">Aktual</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Jenis</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($realisasi as $item)
                    @php
                        $status = match ($item->status_lewat) {
                            'centang' => 'JALAN',
                            'silang' => 'BATAL',
                            default => 'BELUM',
                        };

                        $statusClass = match ($status) {
                            'JALAN' => 'bg-green-100 text-green-800',
                            'BATAL' => 'bg-red-100 text-red-800',
                            default => 'bg-amber-100 text-amber-800',
                        };

                        $jadwalJam = $item->jadwalKa?->jam_lewat;
                    @endphp

                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            {{ $realisasi->firstItem() + $loop->index }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $item->dinas?->tanggal_dinas
                                ? \Illuminate\Support\Carbon::parse($item->dinas->tanggal_dinas)->format('d/m/Y')
                                : '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $item->dinas?->user?->name ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            {{ $item->dinas?->jpl?->kode_jpl ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="font-semibold">
                                {{ $item->keretaApi?->nomor_ka ?? '—' }}
                            </div>
                            <div class="text-slate-500">
                                {{ $item->keretaApi?->nama_ka ?? '—' }}
                            </div>
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $jadwalJam ? substr((string) $jadwalJam, 0, 5) : '—' }}
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $item->jam_aktual ? substr((string) $item->jam_aktual, 0, 5) : '—' }}
                        </td>

                        <td class="px-4 py-3">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                {{ $status }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            {{ $item->is_manual ? 'Manual' : 'Terjadwal' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-slate-500">
                            Belum ada data realisasi KA yang sesuai dengan filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-5">
        {{ $realisasi->links() }}
    </div>
</div>
@endsection
