@extends('layouts.superadmin')

@section('title', 'Jadwal KA')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-medium text-blue-700">Operasional / Jadwal KA</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 sm:text-3xl">Jadwal Perjalanan KA</h1>
            <p class="mt-2 text-sm text-gray-500">Pantau jadwal perjalanan kereta api berdasarkan JPL.</p>
            <p class="mt-2 text-sm font-semibold text-blue-700">Jadwal hari ini: {{ \Carbon\Carbon::parse($tanggalHariIni)->locale('id')->translatedFormat('l, d F Y') }}</p>
        </div>
        <a href="{{ route('superadmin.jadwal-ka.arsip') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
            Arsip Jadwal
        </a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">JPL dengan Jadwal Hari Ini</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($jpls->total()) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Tanggal Jadwal</p>
            <p class="mt-2 text-lg font-bold text-gray-900">{{ \Carbon\Carbon::parse($tanggalHariIni)->translatedFormat('d F Y') }}</p>
        </div>
    </div>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <form method="GET" action="{{ route('superadmin.jadwal-ka.index') }}" class="flex flex-col gap-3 border-b border-gray-100 bg-gray-50 p-4 sm:flex-row">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama JPL..." aria-label="Cari kode atau nama JPL" class="min-w-0 flex-1 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            <button type="submit" class="rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Cari JPL</button>
            @if (request()->filled('q'))
                <a href="{{ route('superadmin.jadwal-ka.index') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-center text-sm font-semibold text-gray-600 hover:bg-gray-100">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="w-16 px-5 py-4 text-left">No.</th>
                        <th class="px-5 py-4 text-left">Kode JPL</th>
                        <th class="px-5 py-4 text-left">Nama JPL</th>
                        <th class="px-5 py-4 text-center">Jumlah Jadwal</th>
                        <th class="px-5 py-4 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($jpls as $jpl)
                        <tr class="hover:bg-blue-50/40">
                            <td class="whitespace-nowrap px-5 py-4 text-gray-400">{{ $jpls->firstItem() + $loop->index }}</td>
                            <td class="whitespace-nowrap px-5 py-4 font-bold text-blue-800">{{ $jpl->kode_jpl }}</td>
                            <td class="px-5 py-4 font-medium text-gray-800">{{ $jpl->nama_jpl ?: 'Nama JPL belum tersedia' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-center"><span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">{{ $jpl->jadwal_count ?? 0 }} jadwal</span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-center">
                                <a href="{{ route('superadmin.jadwal-ka.show', $jpl->id) }}" class="inline-flex items-center justify-center rounded-lg bg-blue-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-800">Lihat Jadwal</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500">Tidak ada JPL yang memiliki jadwal hari ini atau sesuai pencarian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($jpls->hasPages() || $jpls->total() > 0)
            <div class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500">Menampilkan {{ $jpls->firstItem() ?? 0 }}–{{ $jpls->lastItem() ?? 0 }} dari {{ $jpls->total() }} JPL</p>
                {{ $jpls->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
