@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="mb-2 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('admin.jadwal-ka.index') }}"
                       class="hover:text-blue-700">
                        Jadwal KA
                    </a>

                    <span>/</span>

                    <a href="{{ route('admin.jadwal-ka.arsip') }}"
                       class="hover:text-blue-700">
                        Arsip
                    </a>

                    <span>/</span>

                    <span class="font-medium text-blue-700">
                        Detail Arsip
                    </span>
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Detail Arsip Jadwal KA
                </h1>

                <p class="mt-2 text-sm text-slate-500">
                    Tanggal:
                    <span class="font-semibold text-slate-800">
                        {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }}
                    </span>
                </p>
            </div>

            <a href="{{ route('admin.jadwal-ka.arsip') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                <svg class="h-5 w-5"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M15 18l-6-6 6-6"/>
                </svg>
                Kembali ke Arsip
            </a>
        </div>

        {{-- Ringkasan arsip --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Tanggal Arsip
                </p>

                <p class="mt-2 text-lg font-bold text-slate-900">
                    {{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Jumlah Jadwal
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ $jadwal->total() }}
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    Total data jadwal pada tanggal arsip ini.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Status Data
                </p>

                <p class="mt-2 text-lg font-bold text-indigo-700">
                    Arsip historis
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    Data ditampilkan berdasarkan tanggal jadwal.
                </p>
            </div>

        </div>

        {{-- Notifikasi --}}
        @if (session('success'))
            <div role="alert"
                 class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div role="alert"
                 class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Tabel detail arsip --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 p-5 sm:p-6">
                <h2 class="text-lg font-bold text-slate-900">
                    Daftar Jadwal pada Tanggal Ini
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Jadwal diurutkan berdasarkan JPL dan jam lewat.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">
                        <tr>
                            <th class="whitespace-nowrap px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                No.
                            </th>

                            <th class="whitespace-nowrap px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Kode JPL
                            </th>

                            <th class="whitespace-nowrap px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Nama JPL
                            </th>

                            <th class="whitespace-nowrap px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Nomor KA
                            </th>

                            <th class="whitespace-nowrap px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Nama KA
                            </th>

                            <th class="whitespace-nowrap px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Dari
                            </th>

                            <th class="whitespace-nowrap px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Ke
                            </th>

                            <th class="whitespace-nowrap px-5 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">
                                Jam Lewat
                            </th>

                            <th class="whitespace-nowrap px-5 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($jadwal as $item)
                            <tr class="transition hover:bg-blue-50/40">

                                {{-- Nomor urut --}}
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-400">
                                    {{ $jadwal->firstItem() + $loop->index }}
                                </td>

                                {{-- Kode JPL --}}
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-lg border border-blue-100 bg-blue-50 px-3 py-1.5 text-sm font-bold text-blue-800">
                                        {{ $item->jpl?->kode_jpl ?? '—' }}
                                    </span>
                                </td>

                                {{-- Nama JPL --}}
                                <td class="px-5 py-4">
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $item->jpl?->nama_jpl ?: 'Nama JPL belum tersedia' }}
                                    </p>
                                </td>

                                {{-- Nomor KA --}}
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-slate-800">
                                    {{ $item->keretaApi?->nomor_ka ?? '—' }}
                                </td>

                                {{-- Nama KA --}}
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ $item->keretaApi?->nama_ka ?? '—' }}
                                </td>

                                {{-- Stasiun asal --}}
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-700">
                                    {{ filled($item->dari) ? $item->dari : '—' }}
                                </td>

                                {{-- Stasiun tujuan --}}
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-700">
                                    {{ filled($item->ke) ? $item->ke : '—' }}
                                </td>

                                {{-- Jam lewat --}}
                                <td class="whitespace-nowrap px-5 py-4 text-center">
                                    <span class="inline-flex rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-800">
                                        {{ $item->jam_lewat
                                            ? substr((string) $item->jam_lewat, 0, 5)
                                            : '—' }}
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="whitespace-nowrap px-5 py-4 text-center">
                                    @if ($item->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-16 text-center">
                                    <div class="mx-auto max-w-md">
                                        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                                            <svg class="h-7 w-7"
                                                 viewBox="0 0 24 24"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 stroke-width="1.5">
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v13a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/>
                                            </svg>
                                        </div>

                                        <h3 class="text-sm font-bold text-slate-800">
                                            Tidak ada jadwal pada tanggal ini
                                        </h3>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Tidak ditemukan jadwal yang memiliki tanggal berlaku tersebut.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

            {{-- Pagination --}}
            @if ($jadwal->total() > 0)
                <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">

                    <p class="text-sm text-slate-500">
                        Menampilkan
                        <span class="font-semibold text-slate-700">
                            {{ $jadwal->firstItem() ?? 0 }}–{{ $jadwal->lastItem() ?? 0 }}
                        </span>
                        dari
                        <span class="font-semibold text-slate-700">
                            {{ $jadwal->total() }}
                        </span>
                        jadwal
                    </p>

                    <div>
                        {{ $jadwal->links() }}
                    </div>

                </div>
            @endif

        </section>

    </div>
</div>
@endsection
