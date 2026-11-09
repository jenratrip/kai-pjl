@extends('layouts.superadmin')

@section('title', 'Detail Arsip Jadwal KA')
@section('page-title', 'Detail Arsip Jadwal KA')

@section('content')
<div class="mx-auto max-w-7xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                <a href="{{ route('superadmin.jadwal-ka.index') }}" class="hover:text-blue-700">Jadwal KA</a><span>/</span><a href="{{ route('superadmin.jadwal-ka.arsip') }}" class="hover:text-blue-700">Arsip</a><span>/</span><span class="font-medium text-blue-700">Detail</span>
            </div>
            <h1 class="mt-2 text-2xl font-bold text-gray-900 sm:text-3xl">Detail Arsip Jadwal KA</h1>
            <p class="mt-2 text-sm text-gray-500">
                @if ($jenisArsip === 'batch')
                    Periode efektif:
                    <span class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y') }} – {{ \Carbon\Carbon::parse($tanggalAkhir)->locale('id')->translatedFormat('d F Y') }}</span>
                @else
                    Tanggal legacy:
                    <span class="font-semibold text-gray-800">{{ \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l, d F Y') }}</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-800">Riwayat · Hanya baca</span>
            <a href="{{ route('superadmin.jadwal-ka.arsip') }}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kembali ke Arsip</a>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">{{ $jenisArsip === 'batch' ? 'Mulai Berlaku' : 'Tanggal Arsip Legacy' }}</p><p class="mt-2 text-lg font-bold text-gray-900">{{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }}</p>@if ($jenisArsip === 'batch')<p class="mt-1 text-sm text-gray-600">Sampai {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d-m-Y') }}</p>@endif</div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Jumlah Jadwal</p><p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($jadwal->total()) }}</p></div>
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><p class="text-sm text-gray-500">Status Data</p><p class="mt-2 text-lg font-bold text-indigo-700">Arsip historis</p><p class="mt-1 text-xs text-gray-500">{{ $jenisArsip === 'batch' ? 'Rentang dari batch dan cakupan JPL.' : 'Dikelompokkan menurut tanggal berlaku legacy.' }}</p></div>
    </div>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 p-5"><h2 class="font-semibold text-gray-800">{{ $jenisArsip === 'batch' ? 'Jadwal dalam Periode Batch' : 'Jadwal Legacy pada Tanggal Ini' }}</h2><p class="mt-1 text-sm text-gray-500">Diurutkan berdasarkan JPL dan jam lewat.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500"><tr><th class="px-4 py-3 text-left">No.</th><th class="px-4 py-3 text-left">Kode JPL</th><th class="px-4 py-3 text-left">Nama JPL</th><th class="px-4 py-3 text-left">Nomor KA</th><th class="px-4 py-3 text-left">Nama KA</th><th class="px-4 py-3 text-left">Dari</th><th class="px-4 py-3 text-left">Ke</th><th class="px-4 py-3 text-center">Jam Lewat</th><th class="px-4 py-3 text-center">Status</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($jadwal as $item)
                        <tr class="hover:bg-blue-50/40">
                            <td class="whitespace-nowrap px-4 py-3 text-gray-400">{{ $jadwal->firstItem() + $loop->index }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-bold text-blue-800">{{ $item->jpl?->kode_jpl ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $item->jpl?->nama_jpl ?: 'Nama JPL belum tersedia' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold">{{ $item->keretaApi?->nomor_ka ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $item->keretaApi?->nama_ka ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ filled($item->dari) ? $item->dari : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ filled($item->ke) ? $item->ke : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-center font-mono">{{ $item->jam_lewat ? substr((string) $item->jam_lewat, 0, 5) : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">@if ($item->is_active)<span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Aktif</span>@else<span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Nonaktif</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-12 text-center text-gray-500">Tidak ada jadwal pada periode arsip ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($jadwal->total() > 0)<div class="flex flex-col gap-3 border-t border-gray-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm text-gray-500">Menampilkan {{ $jadwal->firstItem() ?? 0 }}–{{ $jadwal->lastItem() ?? 0 }} dari {{ $jadwal->total() }} jadwal</p>{{ $jadwal->links() }}</div>@endif
    </section>
</div>
@endsection
