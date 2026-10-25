@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2 text-sm text-slate-500">
                    <a href="{{ route('admin.jadwal-ka.index') }}"
                       class="hover:text-blue-700">
                        Jadwal KA
                    </a>
                    <span>/</span>
                    <span class="font-medium text-blue-700">Arsip</span>
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Arsip Jadwal KA
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Lihat jadwal perjalanan KA dari tanggal selain hari ini.
                    Pilih tanggal untuk membuka detail jadwal.
                </p>
            </div>

            <a href="{{ route('admin.jadwal-ka.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M15 18l-6-6 6-6"/>
                </svg>
                Jadwal Hari Ini
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tanggal Hari Ini</p>
                <p class="mt-2 text-xl font-bold text-slate-900">
                    {{ \Carbon\Carbon::parse($tanggalHariIni)->translatedFormat('d F Y') }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Jadwal hari ini tersedia pada halaman utama.
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Tanggal dalam Arsip</p>
                <p class="mt-2 text-3xl font-bold text-slate-900">
                    {{ $arsip->total() }}
                </p>
                <p class="mt-1 text-xs text-slate-500">
                    Jumlah tanggal arsip yang ditemukan.
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5 sm:p-6">
                <h2 class="text-lg font-bold text-slate-900">
                    Daftar Tanggal Arsip
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Gunakan pencarian JPL atau filter tanggal untuk menemukan arsip.
                </p>
            </div>

            <div class="border-b border-slate-100 bg-slate-50/70 p-4 sm:p-5">
                <form method="GET"
                      action="{{ route('admin.jadwal-ka.arsip') }}"
                      class="grid grid-cols-1 gap-3 sm:grid-cols-3">

                    <div>
                        <label for="q" class="mb-1.5 block text-xs font-semibold text-slate-600">
                            Cari JPL
                        </label>
                        <input
                            id="q"
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Kode atau nama JPL"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div>
                        <label for="tanggal" class="mb-1.5 block text-xs font-semibold text-slate-600">
                            Filter Tanggal
                        </label>
                        <input
                            id="tanggal"
                            type="date"
                            name="tanggal"
                            value="{{ request('tanggal') }}"
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="inline-flex flex-1 items-center justify-center rounded-xl bg-blue-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800">
                            Terapkan Filter
                        </button>

                        <a href="{{ route('admin.jadwal-ka.arsip') }}"
                           class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                No.
                            </th>
                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Tanggal Jadwal
                            </th>
                            <th class="px-5 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">
                                Jumlah JPL
                            </th>
                            <th class="px-5 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">
                                Jumlah Jadwal
                            </th>
                            <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        @forelse ($arsip as $item)
                            <tr class="transition hover:bg-blue-50/40">
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-400">
                                    {{ $arsip->firstItem() + $loop->index }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-sm font-semibold text-slate-900">
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d F Y') }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('l') }}
                                    </p>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-center">
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700">
                                        {{ $item->total_jpl }} JPL
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-center">
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700">
                                        {{ $item->total_jadwal }} jadwal
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <a href="{{ route('admin.jadwal-ka.arsip.show', ['tanggal' => $item->tanggal]) }}"
                                       class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-blue-800">
                                        Lihat Arsip
                                        <svg class="h-4 w-4" viewBox="0 0 24 24"
                                             fill="none" stroke="currentColor"
                                             stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M5 12h14m-6-6 6 6-6 6"/>
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-16 text-center">
                                    <h3 class="text-sm font-bold text-slate-800">
                                        Arsip belum ditemukan
                                    </h3>
                                    <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">
                                        Belum ada jadwal bertanggal selain hari ini yang sesuai dengan filter.
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($arsip->hasPages() || $arsip->total() > 0)
                <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">
                        Menampilkan {{ $arsip->firstItem() ?? 0 }}–{{ $arsip->lastItem() ?? 0 }}
                        dari {{ $arsip->total() }} tanggal
                    </p>
                    <div>
                        {{ $arsip->links() }}
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
