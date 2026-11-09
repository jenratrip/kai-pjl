@extends('layouts.superadmin')

@section('title', 'Arsip Jadwal KA')
@section('page-title', 'Arsip Jadwal KA')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('superadmin.jadwal-ka.index') }}" class="text-sm font-medium text-blue-700 hover:underline">Jadwal KA</a>
            <h1 class="mt-2 text-2xl font-bold text-gray-900 sm:text-3xl">Arsip Jadwal KA</h1>
            <p class="mt-2 text-sm text-gray-500">Pantau periode versi batch yang telah berakhir dan arsip tanggal jadwal legacy.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800">Arsip · Hanya baca</span>
            <a href="{{ route('superadmin.jadwal-ka.index') }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Jadwal Hari Ini</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Tanggal Hari Ini</p>
            <p class="mt-2 text-xl font-bold text-gray-900">{{ \Carbon\Carbon::parse($tanggalHariIni)->locale('id')->translatedFormat('d F Y') }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">Tanggal dalam Arsip</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($arsip->total()) }}</p>
            <p class="mt-1 text-xs text-gray-500">Periode batch dan tanggal legacy</p>
        </div>
    </div>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800">Daftar Tanggal Arsip</h2>
            <p class="mt-1 text-sm text-gray-500">Cari berdasarkan kode/nama JPL atau filter tanggal.</p>
        </div>
        <form method="GET" action="{{ route('superadmin.jadwal-ka.arsip') }}" class="grid gap-3 border-b border-gray-100 bg-gray-50 p-4 sm:grid-cols-3">
            <div>
                <label for="q" class="mb-1 block text-xs font-semibold text-gray-600">Cari JPL</label>
                <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Kode atau nama JPL" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <div>
                <label for="tanggal" class="mb-1 block text-xs font-semibold text-gray-600">Tanggal mulai efektif / tanggal legacy</label>
                <input id="tanggal" type="date" name="tanggal" value="{{ request('tanggal') }}" class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-800">Terapkan Filter</button>
                @if (request()->hasAny(['q', 'tanggal']))<a href="{{ route('superadmin.jadwal-ka.arsip') }}" class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-100">Reset</a>@endif
            </div>
        </form>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-5 py-4 text-left">No.</th><th class="px-5 py-4 text-left">Periode Efektif / Tanggal Legacy</th><th class="px-5 py-4 text-center">Jumlah JPL</th><th class="px-5 py-4 text-center">Jumlah Jadwal</th><th class="px-5 py-4 text-center">Detail</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($arsip as $item)
                        <tr class="hover:bg-blue-50/40">
                            <td class="whitespace-nowrap px-5 py-4 text-gray-400">{{ $arsip->firstItem() + $loop->index }}</td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if ($item->source === 'batch')
                                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Batch #{{ $item->batch_id }}</p>
                                    <p class="mt-1 font-semibold text-gray-900">{{ \Carbon\Carbon::parse($item->tanggal)->locale('id')->translatedFormat('d M Y') }} – {{ \Carbon\Carbon::parse($item->tanggal_akhir)->locale('id')->translatedFormat('d M Y') }}</p>
                                    <p class="mt-1 text-xs text-gray-500">Periode efektif untuk cakupan JPL ini</p>
                                @else
                                    <p class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($item->tanggal)->locale('id')->translatedFormat('d F Y') }}</p>
                                    <p class="mt-1 text-xs text-gray-500">Legacy · {{ \Carbon\Carbon::parse($item->tanggal)->locale('id')->translatedFormat('l') }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-center"><span class="rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700">{{ $item->total_jpl }} JPL</span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-center"><span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">{{ $item->total_jadwal }} jadwal</span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-center"><a href="{{ route('superadmin.jadwal-ka.arsip.show', $item->source === 'batch' ? ['tanggal' => $item->tanggal, 'batch_id' => $item->batch_id, 'tanggal_akhir' => $item->tanggal_akhir] : ['tanggal' => $item->tanggal]) }}" class="inline-flex items-center rounded-lg bg-blue-700 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-blue-800">Lihat Arsip</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-gray-500">Arsip tidak ditemukan untuk pencarian atau tanggal tersebut.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($arsip->hasPages() || $arsip->total() > 0)
            <div class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm text-gray-500">Menampilkan {{ $arsip->firstItem() ?? 0 }}–{{ $arsip->lastItem() ?? 0 }} dari {{ $arsip->total() }} tanggal</p>{{ $arsip->links() }}</div>
        @endif
    </section>
</div>
@endsection
