{{-- =====================================================
     KETERANGAN WARNA DAN PENGATURAN REALISASI KA
     Sumber konfigurasi: Admin
===================================================== --}}

@php
    /*
    |--------------------------------------------------------------------------
    | AMBIL PENGATURAN DARI ADMIN
    |--------------------------------------------------------------------------
    */

    $setting = $realisasiKaSetting ?? null;

    $toleransiKeterlambatan = (int) data_get(
        $setting,
        'toleransi_keterlambatan_menit',
        5
    );

    $peringatanKeterlambatanTinggi = (int) data_get(
        $setting,
        'peringatan_keterlambatan_tinggi_menit',
        10
    );

    // Menggunakan nama kolom database yang benar.
    $batasLebihCepat = (int) data_get(
        $setting,
        'batas_lebih_awal_menit',
        2
    );

    $jarakMinimumAntarKa = (int) data_get(
        $setting,
        'jarak_minimum_antar_ka_menit',
        0
    );

    $batasAwas2Arah = (int) data_get(
        $setting,
        'batas_awas_2_arah_menit',
        5
    );

    $batasPenutupanPalang = (int) data_get(
        $setting,
        'batas_penutupan_palang_menit',
        0
    );

    /*
    |--------------------------------------------------------------------------
    | STATUS AKTIVASI PERINGATAN
    |--------------------------------------------------------------------------
    */

    $warningKeterlambatanAktif = (bool) data_get(
        $setting,
        'warning_keterlambatan_aktif',
        false
    );

    $warningKeterlambatanTinggiAktif = (bool) data_get(
        $setting,
        'warning_keterlambatan_tinggi_aktif',
        false
    );

    $warningLebihAwalAktif = (bool) data_get(
        $setting,
        'warning_lebih_awal_aktif',
        true
    );

    $warningAntarKaAktif = (bool) data_get(
        $setting,
        'warning_antar_ka_aktif',
        false
    );

    $warningAwas2ArahAktif = (bool) data_get(
        $setting,
        'warning_awas_2_arah_aktif',
        false
    );

    $warningPenutupanPalangAktif = (bool) data_get(
        $setting,
        'warning_penutupan_palang_aktif',
        false
    );

    /*
    |--------------------------------------------------------------------------
    | FORMAT STATUS
    |--------------------------------------------------------------------------
    */

    $statusAktif = fn ($aktif) => $aktif ? 'Aktif' : 'Nonaktif';

    $warnaStatus = fn ($aktif) => $aktif
        ? 'text-emerald-600'
        : 'text-slate-400';
@endphp

<div
    class="color-legend mb-5 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4"
    role="group"
    aria-label="Keterangan warna dan konfigurasi realisasi kereta api"
>

    {{-- =================================================
         1. KETERANGAN WARNA
    ================================================== --}}

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

    <div class="border-t border-slate-100"></div>

    {{-- =================================================
         2. KONFIGURASI PERINGATAN ADMIN
    ================================================== --}}

    <section>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-xs font-extrabold text-[#1d2d50]">
                    Konfigurasi Realisasi KA
                </h3>

                <p class="mt-1 text-[10px] text-slate-500">
                    Nilai ambang batas dan status peringatan yang ditentukan oleh Admin.
                </p>
            </div>

            <span class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-[10px] font-semibold text-blue-700">
                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                Konfigurasi Admin
            </span>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">

            {{-- 1. TOLERANSI KETERLAMBATAN --}}
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-[11px] font-bold text-slate-700">
                        Toleransi Keterlambatan
                    </h4>

                    <span class="rounded bg-slate-200 px-1.5 py-0.5 text-[9px] font-semibold text-slate-600">
                        Waktu
                    </span>
                </div>

                <div class="mt-2 text-xl font-extrabold tabular-nums text-[#1d2d50]">
                    {{ $toleransiKeterlambatan }}
                    <span class="text-xs font-semibold text-slate-500">menit</span>
                </div>

                <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                    Batas toleransi keterlambatan KA sebelum melewati ambang yang ditentukan.
                </p>

                <div class="mt-3 border-t border-slate-200 pt-2 text-[10px]">
                    <span class="text-slate-500">Peringatan keterlambatan:</span>
                    <span class="font-bold {{ $warnaStatus($warningKeterlambatanAktif) }}">
                        {{ $statusAktif($warningKeterlambatanAktif) }}
                    </span>
                </div>
            </div>

            {{-- 2. KETERLAMBATAN TINGGI --}}
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-[11px] font-bold text-slate-700">
                        Keterlambatan Tinggi
                    </h4>

                    <span class="rounded bg-amber-100 px-1.5 py-0.5 text-[9px] font-semibold text-amber-700">
                        Peringatan
                    </span>
                </div>

                <div class="mt-2 text-xl font-extrabold tabular-nums text-[#1d2d50]">
                    {{ $peringatanKeterlambatanTinggi }}
                    <span class="text-xs font-semibold text-slate-500">menit</span>
                </div>

                <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                    Ambang keterlambatan tinggi untuk memberikan peringatan tambahan.
                </p>

                <div class="mt-3 border-t border-slate-200 pt-2 text-[10px]">
                    <span class="text-slate-500">Peringatan:</span>
                    <span class="font-bold {{ $warnaStatus($warningKeterlambatanTinggiAktif) }}">
                        {{ $statusAktif($warningKeterlambatanTinggiAktif) }}
                    </span>
                </div>
            </div>

            {{-- 3. BATAS LEBIH CEPAT --}}
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-[11px] font-bold text-slate-700">
                        Batas Lebih Cepat
                    </h4>

                    <span class="rounded bg-sky-100 px-1.5 py-0.5 text-[9px] font-semibold text-sky-700">
                        Waktu
                    </span>
                </div>

                <div class="mt-2 text-xl font-extrabold tabular-nums text-[#1d2d50]">
                    {{ $batasLebihCepat }}
                    <span class="text-xs font-semibold text-slate-500">menit</span>
                </div>

                <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                    Ambang selisih waktu kedatangan lebih awal dibandingkan jadwal KA.
                </p>

                <div class="mt-3 border-t border-slate-200 pt-2 text-[10px]">
                    <span class="text-slate-500">Peringatan lebih awal:</span>
                    <span class="font-bold {{ $warnaStatus($warningLebihAwalAktif) }}">
                        {{ $statusAktif($warningLebihAwalAktif) }}
                    </span>
                </div>
            </div>

            {{-- 4. JARAK MINIMUM ANTAR KA --}}
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-[11px] font-bold text-slate-700">
                        Jarak Minimum Antar KA
                    </h4>

                    <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[9px] font-semibold text-indigo-700">
                        Interval
                    </span>
                </div>

                <div class="mt-2 text-xl font-extrabold tabular-nums text-[#1d2d50]">
                    {{ $jarakMinimumAntarKa }}
                    <span class="text-xs font-semibold text-slate-500">menit</span>
                </div>

                <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                    Jarak waktu minimum antar perjalanan KA sebagai acuan pemeriksaan interval.
                </p>

                <div class="mt-3 border-t border-slate-200 pt-2 text-[10px]">
                    <span class="text-slate-500">Peringatan:</span>
                    <span class="font-bold {{ $warnaStatus($warningAntarKaAktif) }}">
                        {{ $statusAktif($warningAntarKaAktif) }}
                    </span>
                </div>
            </div>

            {{-- 5. AWAS 2 ARAH --}}
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-[11px] font-bold text-slate-700">
                        AWAS 2 ARAH
                    </h4>

                    <span class="rounded bg-red-100 px-1.5 py-0.5 text-[9px] font-semibold text-red-700">
                        Keselamatan
                    </span>
                </div>

                <div class="mt-2 text-xl font-extrabold tabular-nums text-[#1d2d50]">
                    {{ $batasAwas2Arah }}
                    <span class="text-xs font-semibold text-slate-500">menit</span>
                </div>

                <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                    Ambang waktu untuk pemeriksaan kondisi pergerakan KA dua arah.
                </p>

                <div class="mt-3 border-t border-slate-200 pt-2 text-[10px]">
                    <span class="text-slate-500">Peringatan:</span>
                    <span class="font-bold {{ $warnaStatus($warningAwas2ArahAktif) }}">
                        {{ $statusAktif($warningAwas2ArahAktif) }}
                    </span>
                </div>
            </div>

            {{-- 6. PENUTUPAN PALANG --}}
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <div class="flex items-start justify-between gap-2">
                    <h4 class="text-[11px] font-bold text-slate-700">
                        Penutupan Palang
                    </h4>

                    <span class="rounded bg-orange-100 px-1.5 py-0.5 text-[9px] font-semibold text-orange-700">
                        Operasional
                    </span>
                </div>

                <div class="mt-2 text-xl font-extrabold tabular-nums text-[#1d2d50]">
                    {{ $batasPenutupanPalang }}
                    <span class="text-xs font-semibold text-slate-500">menit</span>
                </div>

                <p class="mt-1 text-[10px] leading-relaxed text-slate-500">
                    Ambang waktu penutupan palang sesuai konfigurasi operasional.
                </p>

                <div class="mt-3 border-t border-slate-200 pt-2 text-[10px]">
                    <span class="text-slate-500">Peringatan:</span>
                    <span class="font-bold {{ $warnaStatus($warningPenutupanPalangAktif) }}">
                        {{ $statusAktif($warningPenutupanPalangAktif) }}
                    </span>
                </div>
            </div>

        </div>
    </section>

    {{-- =================================================
         3. INFORMASI
    ================================================== --}}

    <div class="flex items-start gap-2 rounded-lg border border-blue-100 bg-blue-50 p-3">
        <span class="mt-0.5 text-blue-600">
            <svg xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 16v-4"/>
                <path d="M12 8h.01"/>
            </svg>
        </span>

        <p class="text-[10px] leading-relaxed text-blue-800">
            <strong>Informasi:</strong>
            Seluruh nilai ambang batas dan status peringatan pada bagian ini mengikuti konfigurasi yang ditetapkan Admin.
            PJL menggunakan informasi tersebut sebagai acuan dalam melakukan pemantauan dan pencatatan realisasi KA.
            Pengaturan hanya dapat diubah melalui halaman pengaturan Admin.
        </p>
    </div>

</div>
