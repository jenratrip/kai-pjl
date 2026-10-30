@extends('layouts.admin')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- HEADER --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="mb-2 flex items-center gap-2 text-sm text-slate-500">
                    <span>Operasional</span>
                    <span>/</span>
                    <span class="font-medium text-blue-700">Jadwal KA</span>
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Jadwal Perjalanan KA
                </h1>

                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Kelola jadwal perjalanan kereta api berdasarkan
                    pos jaga lintasan (JPL).
                </p>

                @isset($tanggalHariIni)
                    <p class="mt-2 flex items-center gap-2 text-sm font-semibold text-blue-700">
                        <svg class="h-4 w-4 shrink-0"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="1.8">
                            <rect x="3" y="5" width="18" height="16" rx="2"/>
                            <path stroke-linecap="round" d="M16 3v4M8 3v4M3 10h18"/>
                        </svg>

                        Jadwal hari ini:
                        {{ \Carbon\Carbon::parse($tanggalHariIni)->locale('id')->translatedFormat('l, d F Y') }}
                    </p>
                @endisset
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                {{-- ARSIP JADWAL --}}
                <a
                    href="{{ route('admin.jadwal-ka.arsip') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 focus:outline-none focus:ring-4 focus:ring-slate-200"
                >
                    <svg class="h-5 w-5"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M4 4h16v4H4zM5 8v12h14V8M9 12h6M9 16h6"/>
                    </svg>
                    Arsip Jadwal
                </a>

                    {{-- IMPORT EXCEL --}}
                <a
                    href="{{ route('admin.jadwal-ka.import.form') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200"
                >
                    <svg class="h-5 w-5"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M12 16V4m0 0L7 9m5-5 5 5M5 15v4a1 1 0 001 1h12a1 1 0 001-1v-4"/>
                    </svg>
                    Import Excel
                </a>
            </div>
        </div>

        {{-- NOTIFIKASI SUKSES --}}
        @if (session('success'))
            <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                <svg class="mt-0.5 h-5 w-5 shrink-0"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m5 12 4 4L19 6"/>
                </svg>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        {{-- NOTIFIKASI ERROR --}}
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <p class="mb-2 font-semibold">Terjadi kesalahan</p>

                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- RINGKASAN --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

            {{-- TOTAL JPL --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            JPL dengan Jadwal Hari Ini
                        </p>

                        <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                            {{ $jpls->total() }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Jumlah JPL dalam hasil pencarian dan filter saat ini
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                        <svg class="h-6 w-6"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M4 19V5m0 14h16M7 15l3-4 3 2 5-6"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- HASIL HALAMAN --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">
                            Hasil pada Halaman Ini
                        </p>

                        <p class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                            {{ $jpls->count() }}
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Maksimal 25 JPL per halaman
                        </p>
                    </div>

                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700">
                        <svg class="h-6 w-6"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- DAFTAR JPL --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            {{-- HEADER TABEL --}}
            <div class="flex flex-col gap-4 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        Daftar JPL
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Pilih JPL untuk melihat jadwal KA yang berlaku hari ini.
                    </p>
                </div>

                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Jadwal hari ini
                </span>
            </div>

            {{-- PENCARIAN --}}
            <div class="border-b border-slate-100 bg-slate-50/70 p-4 sm:p-5">
                <form
                    method="GET"
                    action="{{ route('admin.jadwal-ka.index') }}"
                    class="flex flex-col gap-3 sm:flex-row"
                >
                    <div class="relative min-w-0 flex-1">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                             viewBox="0 0 24 24"
                             fill="none"
                             stroke="currentColor"
                             stroke-width="1.8">
                            <circle cx="11" cy="11" r="7"/>
                            <path stroke-linecap="round" d="m16 16 4 4"/>
                        </svg>

                        <input
                            type="search"
                            name="q"
                            value="{{ request('q') }}"
                            placeholder="Cari kode atau nama JPL..."
                            aria-label="Cari kode atau nama JPL"
                            class="w-full rounded-xl border border-slate-300 bg-white py-3 pl-10 pr-4 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"
                        >
                    </div>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                    >
                        Cari JPL
                    </button>

                    @if (request()->filled('q'))
                        <a
                            href="{{ route('admin.jadwal-ka.index') }}"
                            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
                        >
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            {{-- TABEL JPL --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="w-16 px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                No.
                            </th>

                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Kode JPL
                            </th>

                            <th class="px-5 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                                Nama JPL
                            </th>

                            <th class="px-5 py-4 text-center text-xs font-bold uppercase tracking-wider text-slate-500">
                                Jumlah Jadwal
                            </th>

                            <th class="px-5 py-4 text-right text-xs font-bold uppercase tracking-wider text-slate-500">
                                Aksi
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($jpls as $jpl)
                            <tr class="transition hover:bg-blue-50/40">

                                {{-- NOMOR --}}
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-400">
                                    {{ $jpls->firstItem() + $loop->index }}
                                </td>

                                {{-- KODE JPL --}}
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex items-center rounded-lg border border-blue-100 bg-blue-50 px-3 py-1.5 text-sm font-bold text-blue-800">
                                        {{ $jpl->kode_jpl }}
                                    </span>
                                </td>

                                {{-- NAMA JPL --}}
                                <td class="px-5 py-4">
                                    <p class="text-sm font-semibold text-slate-800">
                                        {{ $jpl->nama_jpl ?: 'Nama JPL belum tersedia' }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Pos jaga lintasan
                                    </p>
                                </td>

                                {{-- JUMLAH JADWAL --}}
                                <td class="whitespace-nowrap px-5 py-4 text-center">
                                    <span class="inline-flex min-w-24 items-center justify-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                                        {{ $jpl->jadwal_count ?? 0 }} jadwal
                                    </span>
                                </td>

                                {{-- AKSI --}}
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <a
                                        href="{{ route('admin.jadwal-ka.show', $jpl->id) }}"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200"
                                    >
                                        Lihat Jadwal

                                        <svg class="h-4 w-4"
                                             viewBox="0 0 24 24"
                                             fill="none"
                                             stroke="currentColor"
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
                                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <svg class="h-7 w-7"
                                             viewBox="0 0 24 24"
                                             fill="none"
                                             stroke="currentColor"
                                             stroke-width="1.6">
                                            <circle cx="11" cy="11" r="8"/>
                                            <path stroke-linecap="round" d="m17 17 4 4M8 11h6"/>
                                        </svg>
                                    </div>

                                    <h3 class="mt-4 text-sm font-bold text-slate-800">
                                        Tidak ada JPL ditemukan
                                    </h3>

                                    <p class="mx-auto mt-1 max-w-sm text-sm leading-6 text-slate-500">
                                        @if (request()->filled('q'))
                                            Tidak ada JPL yang sesuai dengan kata kunci pencarian.
                                        @else
                                            Belum ada JPL yang cocok dengan data jadwal hari ini. Periksa arsip jadwal atau lakukan import Excel untuk tanggal yang sesuai.
                                        @endif
                                    </p>

                                    <div class="mt-4 flex flex-col justify-center gap-2 sm:flex-row">
                                        @if (request()->filled('q'))
                                            <a
                                                href="{{ route('admin.jadwal-ka.index') }}"
                                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-100"
                                            >
                                                Hapus pencarian
                                            </a>
                                        @endif

                                        <a
                                            href="{{ route('admin.jadwal-ka.arsip') }}"
                                            class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100"
                                        >
                                            Buka Arsip Jadwal
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if ($jpls->hasPages() || $jpls->total() > 0)
                <div class="flex flex-col gap-4 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500">
                        Menampilkan
                        <span class="font-semibold text-slate-800">
                            {{ $jpls->firstItem() ?? 0 }}–{{ $jpls->lastItem() ?? 0 }}
                        </span>
                        dari
                        <span class="font-semibold text-slate-800">
                            {{ $jpls->total() }}
                        </span>
                        JPL
                    </p>

                    <div class="overflow-x-auto">
                        {{ $jpls->links() }}
                    </div>
                </div>
            @endif
        </section>

        {{-- FOOTER --}}
        <p class="pb-2 text-center text-xs text-slate-400">
            Jadwal Perjalanan KA &middot; Sistem Informasi PJL
        </p>
    </div>
</div>
@endsection
