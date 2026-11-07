@extends('layouts.superadmin')

@section('title', 'Detail Jadwal KA')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('superadmin.jadwal-ka.index') }}" class="text-sm font-medium text-blue-700 hover:underline">← Kembali ke daftar JPL</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900">Jadwal KA — {{ $jpl->kode_jpl }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $jpl->nama_jpl ?? 'Nama JPL tidak tersedia' }}</p>
            <p class="mt-1 text-xs text-gray-400">Tanggal: {{ \Carbon\Carbon::parse($tanggalHariIni)->format('d-m-Y') }}</p>
        </div>
        <a href="{{ route('superadmin.jadwal-ka.arsip') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Lihat Arsip</a>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-gray-500">Total jadwal sesuai tanggal dan filter</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ $jadwal->total() }}</p>
    </div>

    <form method="GET" action="{{ route('superadmin.jadwal-ka.show', $jpl->id) }}" class="grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <div class="sm:col-span-2">
            <label for="q" class="mb-1 block text-xs font-semibold text-gray-600">Pencarian KA</label>
            <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Cari nomor atau nama KA..." class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
        </div>
        <div>
            <label for="status" class="mb-1 block text-xs font-semibold text-gray-600">Status</label>
            <select id="status" name="status" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
                <option value="">Semua status</option>
                <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Terapkan</button>
            @if (request()->hasAny(['q', 'status']))
                <a href="{{ route('superadmin.jadwal-ka.show', $jpl->id) }}" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">Reset</a>
            @endif
        </div>
    </form>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4"><h2 class="font-semibold text-gray-800">Daftar Jadwal Kereta Api</h2><p class="mt-1 text-sm text-gray-500">{{ $jadwal->total() }} jadwal ditemukan.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500"><tr>
                    <th class="whitespace-nowrap px-4 py-3">Nomor KA</th><th class="whitespace-nowrap px-4 py-3">Nama KA</th><th class="whitespace-nowrap px-4 py-3">Dari</th><th class="whitespace-nowrap px-4 py-3">Ke</th><th class="whitespace-nowrap px-4 py-3">Jam Lewat</th><th class="min-w-48 px-4 py-3">Keterangan</th><th class="whitespace-nowrap px-4 py-3">Status</th><th class="whitespace-nowrap px-4 py-3">Batch Import</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($jadwal as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-800">{{ $item->keretaApi?->nomor_ka ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $item->keretaApi?->nama_ka ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ filled($item->dari) ? $item->dari : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ filled($item->ke) ? $item->ke : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono font-semibold">{{ $item->jam_lewat ? substr((string) $item->jam_lewat, 0, 5) : '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ filled($item->keterangan) ? $item->keterangan : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">@if ($item->is_active)<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktif</span>@else<span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Nonaktif</span>@endif</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-400">{{ filled($item->batch_import) ? substr((string) $item->batch_import, 0, 8) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center text-gray-500">Jadwal tidak ditemukan untuk tanggal dan filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($jadwal->hasPages())<div class="border-t border-gray-200 px-4 py-3">{{ $jadwal->links() }}</div>@endif
    </section>
</div>
@endsection
