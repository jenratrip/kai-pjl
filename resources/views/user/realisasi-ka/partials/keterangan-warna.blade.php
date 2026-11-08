{{-- =====================================================
     KETERANGAN WARNA REALISASI KA
===================================================== --}}

@php
    $setting = $realisasiKaSetting ?? null;

    $toleransiKeterlambatan = (int) data_get(
        $setting,
        'toleransi_keterlambatan_menit',
        5
    );

    $batasLebihCepat = (int) data_get(
        $setting,
        'batas_lebih_awal_menit',
        2
    );
@endphp

<div
    class="color-legend mb-5 rounded-xl border border-slate-200 bg-white p-4"
    role="group"
    aria-label="Keterangan warna realisasi kereta api"
>
    <section>
        <div class="mb-3">
            <h3 class="text-xs font-extrabold text-[#1d2d50]">
                Keterangan Warna Realisasi KA
            </h3>

            <p class="mt-1 text-[10px] text-slate-500">
                Panduan membaca status dan kondisi realisasi perjalanan kereta api.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-x-5 gap-y-3">

            {{-- TEPAT WAKTU --}}
            <div class="inline-flex items-center gap-2">
                <span class="h-3 w-3 shrink-0 rounded-[3px] bg-emerald-500"></span>
                <span class="text-[11px] text-slate-600">
                    <strong class="text-slate-700">Hijau</strong> — KA tepat waktu.
                </span>
            </div>

            {{-- TERLAMBAT --}}
            <div class="inline-flex items-center gap-2">
                <span class="h-3 w-3 shrink-0 rounded-[3px] bg-amber-400"></span>
                <span class="text-[11px] text-slate-600">
                    <strong class="text-slate-700">Kuning</strong> — KA terlambat melebihi toleransi
                    {{ $toleransiKeterlambatan }} menit.
                </span>
            </div>

            {{-- LEBIH CEPAT --}}
            <div class="inline-flex items-center gap-2">
                <span class="h-3 w-3 shrink-0 rounded-[3px] bg-sky-300"></span>
                <span class="text-[11px] text-slate-600">
                    <strong class="text-slate-700">Biru</strong> — KA lebih cepat dari
                    {{ $batasLebihCepat }} menit.
                </span>
            </div>

            {{-- BATAL --}}
            <div class="inline-flex items-center gap-2">
                <span class="h-3 w-3 shrink-0 rounded-[3px] bg-red-500"></span>
                <span class="text-[11px] text-slate-600">
                    <strong class="text-slate-700">Merah</strong> — KA dibatalkan.
                </span>
            </div>

            {{-- BELUM DIPROSES --}}
            <div class="inline-flex items-center gap-2">
                <span class="h-3 w-3 shrink-0 rounded-[3px] bg-slate-400"></span>
                <span class="text-[11px] text-slate-600">
                    <strong class="text-slate-700">Abu-abu</strong> — realisasi belum diproses.
                </span>
            </div>

            {{-- AWAS 2 ARAH --}}
            <div class="inline-flex items-center gap-2">
                <span class="h-3 w-3 shrink-0 rounded-[3px] border border-red-300 bg-red-50 shadow-[inset_3px_0_0_#ef5145]"></span>
                <span class="text-[11px] text-slate-600">
                    <strong class="text-slate-700">Merah muda</strong> — terdapat kondisi AWAS 2 ARAH.
                </span>
            </div>

            {{-- PEMERIKSAAN --}}
            <div class="inline-flex flex-wrap items-center gap-1.5">
                <span class="inline-flex h-[18px] w-[18px] items-center justify-center rounded border border-emerald-200 bg-emerald-50 text-[11px] font-bold text-emerald-600">✓</span>

                <span class="inline-flex h-[18px] w-[18px] items-center justify-center rounded border border-red-200 bg-red-50 text-[11px] font-bold text-red-500">×</span>

                <span class="inline-flex h-[18px] w-[18px] items-center justify-center rounded border border-slate-200 bg-slate-100 text-[11px] font-bold text-slate-400">—</span>

                <span class="text-[11px] text-slate-600">
                    <strong class="text-slate-700">Pemeriksaan</strong> — ✓ Ya, × Tidak, — Belum diisi.
                </span>
            </div>

        </div>
    </section>
</div>
