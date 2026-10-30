@extends('layouts.user')

@section('title', 'Inventaris - Sistem PJL')

@section('content')
<div class="w-full min-w-0 px-2 py-6 sm:px-5 lg:px-8 lg:py-7">
    <section class="mx-auto w-full max-w-5xl">

        {{-- HEADER --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-[#1d2d50]">Inventaris PJL</h1>
            <p class="mt-1 text-sm text-slate-500">
                Periksa dan catat kondisi inventaris selama dinas berlangsung.
            </p>
        </div>

        {{-- NOTIFIKASI --}}
        @if (session('success'))
            <div
                role="alert"
                class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800"
            >
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div
                role="alert"
                class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
            >
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div
                role="alert"
                class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
            >
                <p class="mb-1 font-semibold">Terdapat kesalahan:</p>
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- TIDAK ADA DINAS AKTIF --}}
        @if (!$dinas)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto mb-3 h-10 w-10 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p class="font-semibold text-amber-800">Tidak ada dinas aktif</p>
                <p class="mt-1 text-sm text-amber-700">
                    Inventaris hanya dapat diperiksa saat dinas sedang berlangsung.
                </p>
            </div>
        @else

            {{-- INFO DINAS --}}
            <div class="mb-6 grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">JPL</p>
                    <p class="mt-1 font-semibold text-slate-800">
                        {{ $dinas->jpl?->kode_jpl ?? '-' }}
                        <span class="font-normal text-slate-500">{{ $dinas->jpl?->nama_jpl ? '— ' . $dinas->jpl->nama_jpl : '' }}</span>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tanggal Dinas</p>
                    <p class="mt-1 font-semibold text-slate-800">
                        {{ \Carbon\Carbon::parse($dinas->tanggal_dinas)->locale('id')->translatedFormat('l, d F Y') }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Shift</p>
                    <p class="mt-1 font-semibold text-slate-800">
                        {{ $dinas->shift?->nama_shift ?? '-' }}
                    </p>
                </div>
            </div>

            {{-- PROGRESS --}}
            @php
                $totalItem     = $inventaris->count();
                $sudahDiperiksa = $realisasi->count();
                $persen        = $totalItem > 0 ? round(($sudahDiperiksa / $totalItem) * 100) : 0;
            @endphp

            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="font-semibold text-slate-700">
                        Kemajuan Pemeriksaan
                    </span>
                    <span class="text-slate-500">
                        {{ $sudahDiperiksa }} / {{ $totalItem }} item diperiksa
                    </span>
                </div>
                <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200">
                    <div
                        class="h-2.5 rounded-full transition-all duration-500 {{ $persen === 100 ? 'bg-green-500' : 'bg-blue-500' }}"
                        style="width: {{ $persen }}%"
                    ></div>
                </div>
                <p class="mt-1 text-right text-xs text-slate-400">{{ $persen }}%</p>
            </div>

            {{-- DAFTAR INVENTARIS --}}
            @if ($inventaris->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500">
                    Belum ada data inventaris untuk JPL ini.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($inventaris as $item)
                        @php
                            $hasil       = $realisasi->get($item->id);
                            $sudahIsi    = $hasil !== null;
                            $adaMasalah  = $sudahIsi && $hasil->status === 'silang';
                        @endphp

                        <div
                            x-data="{ open: false }"
                            class="overflow-hidden rounded-xl border shadow-sm transition
                                {{ $adaMasalah  ? 'border-red-200 bg-red-50'
                                  : ($sudahIsi  ? 'border-green-200 bg-green-50'
                                               : 'border-slate-200 bg-white') }}"
                        >
                            {{-- HEADER ITEM --}}
                            <button
                                type="button"
                                @click="open = !open"
                                class="flex w-full items-center justify-between px-4 py-3 text-left"
                                :aria-expanded="open"
                            >
                                <div class="flex items-center gap-3">
                                    {{-- Status icon --}}
                                    @if ($adaMasalah)
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                    @elseif ($sudahIsi)
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </span>
                                    @else
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                        </span>
                                    @endif

                                    <div>
                                        <p class="font-semibold text-slate-800">{{ $item->nama_barang }}</p>
                                        <p class="text-xs text-slate-500">Jumlah standar: {{ $item->jumlah }} pcs</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if ($adaMasalah)
                                        <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">Bermasalah</span>
                                    @elseif ($sudahIsi)
                                        <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Baik</span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">Belum diperiksa</span>
                                    @endif

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4 text-slate-400 transition-transform duration-200"
                                        :class="{ 'rotate-180': open }"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            {{-- FORM PEMERIKSAAN --}}
                            <div x-show="open" x-collapse class="border-t border-slate-200 px-4 pb-4 pt-3">
                                <form
                                    method="POST"
                                    action="{{ route('user.inventaris.update', $item->id) }}"
                                    class="space-y-4"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="dinas_id" value="{{ $dinas->id }}">

                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        {{-- Kondisi --}}
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-slate-600">
                                                Kondisi <span class="text-red-500">*</span>
                                            </label>
                                            <select
                                                name="kondisi"
                                                required
                                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                                            >
                                                @foreach ([
                                                    'baik'             => 'Baik',
                                                    'perlu_perbaikan'  => 'Perlu Perbaikan',
                                                    'rusak'            => 'Rusak',
                                                    'hilang'           => 'Hilang',
                                                ] as $val => $label)
                                                    <option
                                                        value="{{ $val }}"
                                                        {{ ($hasil?->kondisi ?? 'baik') === $val ? 'selected' : '' }}
                                                    >
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Jumlah aktual --}}
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-slate-600">
                                                Jumlah Aktual
                                                <span class="font-normal text-slate-400">(kosongkan = sesuai standar)</span>
                                            </label>
                                            <input
                                                type="number"
                                                name="jumlah_aktual"
                                                min="0"
                                                value="{{ $hasil?->jumlah_aktual ?? '' }}"
                                                placeholder="{{ $item->jumlah }}"
                                                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                                            >
                                        </div>
                                    </div>

                                    {{-- Catatan --}}
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-slate-600">
                                            Catatan
                                        </label>
                                        <textarea
                                            name="catatan"
                                            rows="2"
                                            maxlength="2000"
                                            placeholder="Keterangan kondisi (opsional)..."
                                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                                        >{{ $hasil?->catatan ?? '' }}</textarea>
                                    </div>

                                    <div class="flex justify-end">
                                        <button
                                            type="submit"
                                            class="rounded-lg bg-[#1d2d50] px-5 py-2 text-sm font-semibold text-white transition hover:bg-[#14213d]"
                                        >
                                            Simpan Pemeriksaan
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        @endif

    </section>
</div>
@endsection
