@extends('layouts.user')

@section('title', 'Realisasi KA - Sistem PJL')

@section('content')
<div class="realisasi-ka-page w-full min-w-0 px-2 py-6 sm:px-5 lg:px-8 lg:py-7">
    <section class="mx-auto w-full max-w-[1700px] rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:p-6 lg:p-8">

        {{-- INFORMASI DINAS --}}
        <div class="mb-7 grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2 sm:p-5 xl:grid-cols-[1fr_1fr_1.1fr_0.7fr]">
            <div>
                <label for="tanggal" class="field-label mb-2 block text-sm font-semibold text-slate-700">HARI / TANGGAL</label>
                <input
                    type="text"
                    id="tanggal"
                    class="field-input min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                    value="{{ ($dinasAktif ?? null)
? \Carbon\Carbon::parse($dinasAktif->tanggal_dinas)->locale('id')->translatedFormat('l, d F Y')
: \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}"
                    readonly>
            </div>

            <div>
                <label for="nomorJpl" class="field-label mb-2 block text-sm font-semibold text-slate-700">NOMOR JPL</label>
                <input
                    id="nomorJpl"
                    type="text"
                    class="field-input min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                    value="{{ ($dinasAktif ?? null) ? ($dinasAktif->jpl?->kode_jpl ?? '-') : '-' }}"
                    readonly>
            </div>

            <div>
                <label for="stasiunRegional" class="field-label mb-2 block text-sm font-semibold text-slate-700">STASIUN / REGIONAL</label>
                <input
                    id="stasiunRegional"
                    type="text"
                    class="field-input min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                    value="{{ ($dinasAktif ?? null) ? ($dinasAktif->jpl?->nama_jpl ?? '-') : '-' }}"
                    readonly>
            </div>

            <div>
                <label for="shift" class="field-label mb-2 block text-sm font-semibold text-slate-700">SHIFT</label>
                <input
                    id="shift"
                    type="text"
                    class="field-input min-h-11 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                    value="{{ ($dinasAktif ?? null) ? ($dinasAktif->shift?->nama_shift ?? '-') : '-' }}"
                    readonly>
            </div>
        </div>

        {{-- DAFTAR KA --}}
        <div class="mb-4 border-b border-slate-200 pb-3 text-base font-bold text-[#1d2d50] sm:text-lg">
            Daftar KA Terjadwal
        </div>

        {{-- JAM SEKARANG DAN RENTANG FILTER --}}
        <div class="mb-4 flex flex-col gap-2 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between">
            <span>
                Filter jadwal: <strong>±1 jam dari waktu sekarang</strong>.
                KA manual tetap ditampilkan.
            </span>

            <div class="waktu-info">
                <span>
                    Jam sekarang:
                    <strong id="jamSekarang">--:--:--</strong> WIB
                </span>

                <span id="windowRange">Rentang jadwal --:--–--:-- WIB</span>
            </div>
        </div>

        {{-- PERINGATAN AWAS 2 ARAH --}}
        <div
            id="awasBanner"
            class="mb-4 hidden rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900"
            role="alert"
            aria-live="assertive"
            hidden></div>

        {{-- RINGKASAN REALISASI --}}
        <div
            class="mb-4 flex w-full min-w-0 flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:p-4"
            role="group"
            aria-label="Ringkasan realisasi shift"
            title="Ringkasan dihitung dari seluruh KA dalam satu shift, termasuk KA manual.">

            <div class="summary-chips flex flex-wrap items-center gap-x-5 gap-y-2">
                <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
                    <span class="chip-label text-xs font-semibold text-slate-500">Total</span>
                    <span id="totalKA" class="chip-number">0</span>
                </div>
                <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
                    <span class="chip-label text-xs font-semibold text-slate-500">KA Manual</span>
                    <span id="manualKA" class="chip-number text-sm font-extrabold text-amber-600">0</span>
                </div>
                <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
                    <span class="chip-dot dot-green h-2 w-2 shrink-0 rounded-full bg-emerald-500"></span>
                    <span class="chip-label text-xs font-semibold text-slate-500">Jalan</span>
                    <span id="jalanKA" class="chip-number green text-sm font-extrabold text-emerald-600">0</span>
                </div>
                <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
                    <span class="chip-dot dot-yellow h-2 w-2 shrink-0 rounded-full bg-amber-400"></span>
                    <span class="chip-label text-xs font-semibold text-slate-500">Terlambat</span>
                    <span id="telatKA" class="chip-number yellow text-sm font-extrabold text-amber-600">0</span>
                </div>
                <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
                    <span class="chip-dot dot-blue h-2 w-2 shrink-0 rounded-full bg-sky-300"></span>
                    <span class="chip-label text-xs font-semibold text-slate-500">Lebih cepat</span>
                    <span id="cepatKA" class="chip-number blue text-sm font-extrabold text-sky-600">0</span>
                </div>
                <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
                    <span class="chip-dot dot-red h-2 w-2 shrink-0 rounded-full bg-red-500"></span>
                    <span class="chip-label text-xs font-semibold text-slate-500">Batal</span>
                    <span id="batalKA" class="chip-number red text-sm font-extrabold text-red-500">0</span>
                </div>
                <div class="summary-chip inline-flex items-center gap-1.5 whitespace-nowrap text-xs leading-tight">
                    <span class="chip-dot dot-gray h-2 w-2 shrink-0 rounded-full bg-slate-400"></span>
                    <span class="chip-label text-xs font-semibold text-slate-500">Belum diproses</span>
                    <span id="belumKA" class="chip-number">0</span>
                </div>
            </div>

            <div class="summary-progress flex w-full min-w-0 items-center gap-2 pt-1">
                <div class="flex h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-200">
                    <div id="progressGreen" class="progress-green h-full bg-emerald-500" style="width: 0%"></div>
                    <div id="progressYellow" class="progress-yellow h-full bg-amber-400" style="width: 0%"></div>
                    <div id="progressBlue" class="progress-blue h-full bg-sky-300" style="width: 0%"></div>
                    <div id="progressRed" class="progress-red h-full bg-red-500" style="width: 0%"></div>
                    <div id="progressGray" class="progress-gray h-full bg-slate-400" style="width: 100%"></div>
                </div>
                <span class="progress-label shrink-0 text-[11px] font-semibold text-slate-500">Progres</span>
                <span id="progressText" class="progress-text min-w-8 shrink-0 text-right text-xs font-extrabold text-[#1d2d50]">0%</span>
            </div>
        </div>

        {{-- KETERANGAN WARNA --}}
        <div class="color-legend mb-5 flex flex-col gap-2 rounded-lg border border-slate-200 bg-white p-3 text-xs text-slate-600" role="group" aria-label="Keterangan warna">
            <div class="legend-title text-xs font-extrabold text-[#1d2d50]">Keterangan Warna</div>

            <div class="legend-list flex flex-wrap items-center gap-x-4 gap-y-2">
                <div class="legend-item inline-flex items-center gap-1.5 text-[11px] leading-snug text-slate-500">
                    <span class="legend-swatch sw-green h-[11px] w-[11px] shrink-0 rounded-[3px] bg-emerald-500"></span>
                    <span class="legend-text">Hijau: tepat waktu</span>
                </div>

                <div class="legend-item inline-flex items-center gap-1.5 text-[11px] leading-snug text-slate-500">
                    <span class="legend-swatch sw-yellow h-[11px] w-[11px] shrink-0 rounded-[3px] bg-amber-400"></span>
                    <span class="legend-text">
                        Kuning: terlambat melewati batas peringatan yang dikonfigurasi
                    </span>
                </div>

                <div class="legend-item inline-flex items-center gap-1.5 text-[11px] leading-snug text-slate-500">
                    <span class="legend-swatch sw-blue h-[11px] w-[11px] shrink-0 rounded-[3px] bg-sky-300"></span>
                    <span class="legend-text">Biru: lebih cepat dari 2 menit</span>
                </div>

                <div class="legend-item inline-flex items-center gap-1.5 text-[11px] leading-snug text-slate-500">
                    <span class="legend-swatch sw-red h-[11px] w-[11px] shrink-0 rounded-[3px] bg-red-500"></span>
                    <span class="legend-text">Merah: KA batal</span>
                </div>

                <div class="legend-item inline-flex items-center gap-1.5 text-[11px] leading-snug text-slate-500">
                    <span class="legend-swatch sw-gray h-[11px] w-[11px] shrink-0 rounded-[3px] bg-slate-400"></span>
                    <span class="legend-text">Abu-abu: belum diproses</span>
                </div>

                <div class="legend-item inline-flex items-center gap-1.5 text-[11px] leading-snug text-slate-500">
                    <span class="legend-swatch sw-awas h-[11px] w-[11px] shrink-0 rounded-[3px] border border-red-300 bg-red-50 shadow-[inset_3px_0_0_#ef5145]"></span>
                    <span class="legend-text">Merah muda: AWAS 2 ARAH</span>
                </div>

                <div class="legend-item inline-flex items-center gap-1.5 text-[11px] leading-snug text-slate-500">
                    <span class="legend-mark mk-yes inline-flex h-[17px] w-[17px] shrink-0 items-center justify-center rounded border border-emerald-200 bg-emerald-50 text-[11px] font-bold text-emerald-600">✓</span>
                    <span class="legend-mark mk-no inline-flex h-[17px] w-[17px] shrink-0 items-center justify-center rounded border border-red-200 bg-red-50 text-[11px] font-bold text-red-500">×</span>
                    <span class="legend-text">
                        Pemeriksaan: ✓ Ya, × Tidak, abu-abu Belum diisi
                    </span>
                </div>
            </div>
        </div>

        {{-- TABEL REALISASI KA --}}
        <div class="table-wrapper w-full min-w-0 max-w-full overflow-hidden rounded-xl border border-slate-200">
            <div class="w-full max-w-full overflow-x-auto">
                <table class="w-max min-w-full border-collapse text-center text-sm">
                    <thead class="bg-[#1d2d50] text-white">
                        <tr>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">NO</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">NOMOR KA</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">NAMA KA</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">HULU</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">HILIR</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">RELASI</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">JAM JADWAL</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">JAM AKTUAL</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">STATUS</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">SEMBOYAN 21</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">GENTA</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">PINTU PERLINTASAN</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">SEMBOYAN 35</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">KETERANGAN</th>
                            <th class="border-0 px-4 py-3 !text-center align-middle font-semibold whitespace-nowrap">AKSI</th>
                        </tr>
                    </thead>
                    <tbody id="kaTableBody"></tbody>
                </table>
            </div>
        </div>

        {{-- TAMBAH KA MANUAL --}}
        <section class="mt-4 w-full min-w-0">
            <button
                type="button"
                id="toggleTambahKa"
                class="rounded-md border border-[#1d2d50] bg-white px-3 py-1.5 text-xs font-semibold text-[#1d2d50] transition hover:bg-slate-50"
                aria-expanded="false"
                aria-controls="formTambahKa"
                onclick="toggleFormTambahKA()">
                + Tambah KA
            </button>

            <div class="mt-3 w-full min-w-0 rounded-lg border border-slate-200 bg-slate-50 p-3 sm:p-4" id="formTambahKa" hidden>
                <div class="mb-4 text-sm font-bold text-[#1d2d50]">Tambah KA Manual</div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="min-w-0">
                        <label for="manualNomor" class="mb-1.5 block text-xs font-semibold text-slate-700">NOMOR KA</label>
                        <input id="manualNomor" type="text"
                            class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                            placeholder="Nomor KA">
                    </div>

                    <div class="min-w-0">
                        <label for="manualNama" class="mb-1.5 block text-xs font-semibold text-slate-700">NAMA KA</label>
                        <input id="manualNama" type="text"
                            class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                            placeholder="Nama KA">
                    </div>

                    <div class="min-w-0">
                        <label for="manualHulu" class="mb-1.5 block text-xs font-semibold text-slate-700">HULU</label>
                        <input id="manualHulu" type="text"
                            class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                            placeholder="Hulu">
                    </div>

                    <div class="min-w-0">
                        <label for="manualHilir" class="mb-1.5 block text-xs font-semibold text-slate-700">HILIR</label>
                        <input id="manualHilir" type="text"
                            class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                            placeholder="Hilir">
                    </div>

                    <div class="min-w-0">
                        <label for="manualRelasi" class="mb-1.5 block text-xs font-semibold text-slate-700">RELASI</label>
                        <input id="manualRelasi" type="text"
                            class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                            placeholder="Contoh: YK - SBI">
                    </div>

                    <div class="min-w-0">
                        <label for="manualAktual" class="mb-1.5 block text-xs font-semibold text-slate-700">JAM AKTUAL</label>
                        <input id="manualAktual" type="time"
                            class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20">
                    </div>

                    <div class="min-w-0">
                        <label for="manualSemboyan21" class="mb-1.5 block text-xs font-semibold text-slate-700">SEMBOYAN 21</label>
                        <div class="mini-check flex items-center gap-1">
                            <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-200" data-group="manual21" data-value="yes" aria-label="Semboyan 21 ya" aria-pressed="false" onclick="setManualCheck(this)">✓</button>
                            <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200" data-group="manual21" data-value="no" aria-label="Semboyan 21 tidak" aria-pressed="false" onclick="setManualCheck(this)">×</button>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <label for="manualGenta" class="mb-1.5 block text-xs font-semibold text-slate-700">GENTA</label>
                        <div class="mini-check flex items-center gap-1">
                            <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-200" data-group="manualGenta" data-value="yes" aria-label="Genta ya" aria-pressed="false" onclick="setManualCheck(this)">✓</button>
                            <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200" data-group="manualGenta" data-value="no" aria-label="Genta tidak" aria-pressed="false" onclick="setManualCheck(this)">×</button>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <label for="manualPintu" class="mb-1.5 block text-xs font-semibold text-slate-700">PINTU PERLINTASAN</label>
                        <div class="mini-check flex items-center gap-1">
                            <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-200" data-group="manualPintu" data-value="yes" aria-label="Pintu perlintasan ya" aria-pressed="false" onclick="setManualCheck(this)">✓</button>
                            <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200" data-group="manualPintu" data-value="no" aria-label="Pintu perlintasan tidak" aria-pressed="false" onclick="setManualCheck(this)">×</button>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <label for="manualSemboyan35" class="mb-1.5 block text-xs font-semibold text-slate-700">SEMBOYAN 35</label>
                        <div class="mini-check flex items-center gap-1">
                            <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-200" data-group="manual35" data-value="yes" aria-label="Semboyan 35 ya" aria-pressed="false" onclick="setManualCheck(this)">✓</button>
                            <button type="button" class="check inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-300 bg-white text-sm font-semibold text-slate-500 transition-colors hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200" data-group="manual35" data-value="no" aria-label="Semboyan 35 tidak" aria-pressed="false" onclick="setManualCheck(this)">×</button>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <label for="manualKeterangan" class="mb-1.5 block text-xs font-semibold text-slate-700">KETERANGAN</label>
                        <input id="manualKeterangan" type="text"
                            class="h-9 w-full min-w-0 rounded-md border border-slate-300 bg-white px-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                            placeholder="Keterangan">
                    </div>

                    <div class="min-w-0 flex flex-row items-center gap-2">
                        <button
                            type="button"
                            class="add-button mt-0 inline-flex min-h-9 items-center justify-center rounded-md border border-[#1d2d50] bg-[#1d2d50] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#263e6a] focus:outline-none focus:ring-2 focus:ring-[#55769a]/30"
                            onclick="tambahKAManual()">
                            + Simpan KA Manual
                        </button>

                        <button
                            type="button"
                            class="action-btn btn-outline mt-0 inline-flex min-h-9 items-center justify-center rounded-md border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-300"
                            onclick="toggleFormTambahKA(false)">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- AKSI REALISASI --}}
        <div class="mt-5 flex flex-col gap-3 border-t border-slate-200 pt-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="text-xs text-slate-600">
                Formulir realisasi digital untuk pencatatan dinas PJL.

                <div
                    id="saveStatus"
                    class="save-status idle mt-2"
                    role="status"
                    aria-live="polite">
                    <span class="save-dot"></span>
                    <span id="saveStatusText">Perubahan belum disimpan</span>
                </div>
            </div>

            <div class="actions flex flex-col items-stretch gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                <button
                    type="button"
                    class="action-btn btn-outline inline-flex min-h-10 items-center justify-center rounded-md border border-[#1d2d50] bg-white px-4 py-2 text-xs font-semibold text-[#1d2d50] transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#55769a]/30"
                    onclick="cetakDokumen()">
                    ▣ &nbsp; Cetak Dokumen JPL
                </button>

                <button
                    type="button"
                    id="simpanBtn"
                    class="action-btn btn-dark inline-flex min-h-10 items-center justify-center rounded-md border border-[#1d2d50] bg-[#1d2d50] px-4 py-2 text-xs font-semibold text-white transition hover:bg-[#263e6a] focus:outline-none focus:ring-2 focus:ring-[#55769a]/40 disabled:cursor-not-allowed disabled:opacity-60"
                    onclick="simpanRealisasi()">
                    ▣ &nbsp; Simpan Semua
                </button>

                <a
                    id="selesaiLink"
                    href="{{ url('/user/serah-terima-dinas') }}"
                    class="action-btn btn-orange inline-flex min-h-10 items-center justify-center rounded-md border border-amber-600 bg-amber-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-400/40">
                    Selesai &amp; Generate Laporan Serah Terima
                </a>
            </div>
        </div>

    </section>

    {{-- CATATAN DINAS --}}
    <section class="mt-6 w-full rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" id="catatanDinasQuick">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="text-sm font-bold text-[#1d2d50]">Catatan Dinas</div>
                <p class="mt-1 text-xs text-slate-500">
                    Catat kejadian penting selama pelaksanaan dinas.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('user.catatan-dinas') }}"
                    class="cd-quick-link inline-flex items-center rounded-md px-2 py-2 text-xs font-semibold text-[#1d2d50] underline-offset-4 transition hover:bg-slate-50 hover:underline">
                    Lihat semua catatan →
                </a>

                <button
                    type="button"
                    class="add-button cd-open-btn mt-0 inline-flex min-h-9 items-center justify-center rounded-md border border-[#1d2d50] bg-white px-3 py-1.5 text-xs font-semibold text-[#1d2d50] transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-[#55769a]/30 disabled:cursor-not-allowed disabled:opacity-50"
                    onclick="openCatatanModal()"
                    @disabled(!($dinasAktif ?? null))>
                    + Tambah Catatan
                </button>
            </div>
        </div>

        @if (session('success'))
        <div class="cd-quick-alert success" role="status">
            {{ session('success') }}
        </div>
        @endif

        @if (!($dinasAktif ?? null))
        <div class="cd-quick-alert error">
            Belum ada dinas aktif, catatan belum bisa ditambahkan.
        </div>
        @endif
    </section>

    @if ($dinasAktif ?? null)
    @php
    $tanggalCatatan = \Carbon\Carbon::parse($dinasAktif->tanggal_dinas)
    ->format('Y-m-d');
    @endphp

    {{-- MODAL TAMBAH CATATAN --}}
    <div
        class="cdm-overlay fixed inset-0 z-[1000] hidden items-center justify-center overflow-y-auto bg-slate-900/55 p-3 backdrop-blur-sm sm:p-5"
        id="catatanModal"
        aria-hidden="true"
        data-open-on-load="{{ $errors->any() ? '1' : '0' }}">

        <div
            class="cdm-modal relative my-auto max-h-[calc(100vh-2rem)] w-full max-w-[560px] overflow-y-auto rounded-xl bg-white p-4 shadow-2xl sm:p-5"
            role="dialog"
            aria-modal="true"
            aria-labelledby="catatanModalTitle">

            <div class="cdm-header mb-4 flex items-center justify-between gap-3 border-b border-slate-200 pb-3">
                <h2 class="cdm-title m-0 text-base font-bold text-[#1d2d50]" id="catatanModalTitle">
                    Tambah Catatan Dinas
                </h2>

                <button
                    type="button"
                    class="cdm-close inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xl leading-none text-slate-500 transition hover:bg-slate-100"
                    aria-label="Tutup"
                    onclick="closeCatatanModal()">
                    ×
                </button>
            </div>

            <form
                id="catatanForm"
                action="{{ route('user.catatan-dinas.store') }}"
                method="POST">
                @csrf

                <input type="hidden" name="redirect_to" value="realisasi-ka">

                <div class="cdm-group mb-3">
                    <label class="cdm-label mb-1.5 block text-xs font-semibold text-slate-800" for="cdmTanggal">
                        Tanggal
                    </label>
                    <input
                        type="date"
                        class="cdm-input block h-9 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20 read-only:bg-slate-50 read-only:text-slate-500"
                        id="cdmTanggal"
                        name="tanggal"
                        value="{{ $tanggalCatatan }}"
                        readonly
                        required>
                </div>

                <div class="cdm-group mb-3">
                    <label class="cdm-label mb-1.5 block text-xs font-semibold text-slate-800" for="cdmPjl">
                        PJL
                    </label>
                    <input
                        type="text"
                        class="cdm-input block h-9 w-full rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20 read-only:bg-slate-50 read-only:text-slate-500"
                        id="cdmPjl"
                        value="{{ auth()->user()->name ?? '' }}"
                        readonly>
                </div>

                <div class="cdm-group mb-3">
                    <label class="cdm-label mb-1.5 block text-xs font-semibold text-slate-800" for="cdmShift">
                        Shift
                    </label>
                    <select
                        class="cdm-input cdm-select"
                        id="cdmShift"
                        name="shift_id"
                        required>
                        <option value="{{ $dinasAktif->shift_id }}" selected>
                            {{ $dinasAktif->shift?->nama_shift ?? '-' }}
                            — {{ $tanggalCatatan }}
                        </option>
                    </select>
                </div>

                <div class="cdm-group mb-3">
                    <label class="cdm-label mb-1.5 block text-xs font-semibold text-slate-800" for="cdmCatatan">
                        Catatan
                    </label>
                    <textarea
                        class="cdm-textarea block min-h-[110px] w-full resize-y rounded-md border border-slate-200 bg-white px-3 py-2 text-sm leading-relaxed text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-1 focus:ring-[#55769a]/20"
                        id="cdmCatatan"
                        name="catatan"
                        maxlength="5000"
                        required
                        placeholder="Tuliskan catatan selama pelaksanaan dinas...">{{ old('catatan') }}</textarea>

                    <div class="cdm-help mt-1.5 text-xs text-slate-500" id="cdmCounter">
                        Maksimal 5.000 karakter.
                    </div>
                </div>

                @if ($errors->any())
                <div class="cdm-error mb-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold leading-relaxed text-red-900" role="alert">
                    @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                    @endforeach
                </div>
                @endif

                <div class="mt-4 flex flex-col-reverse justify-end gap-2 border-t border-slate-200 pt-3 sm:flex-row">
                    <button
                        type="button"
                        class="cdm-btn cdm-cancel min-h-9 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                        onclick="closeCatatanModal()">
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="cdm-btn cdm-save min-h-9 rounded-md border border-[#1d2d50] bg-[#1d2d50] px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-[#263e6a]">
                        Simpan Catatan
                    </button>
                </div>
            </form>
        </div>
    </div>

    @endif
</div>
@endsection

@push('scripts')
{{-- Konfigurasi data realisasi dan pengaturan peringatan dari database --}}
<script>
    window.realisasiKaConfig = {
        data: @json($dataKA ?? []),
        storeUrl: @json(route('user.realisasi-ka.store')),
        shiftStart: @json(data_get($dinasAktif, 'shift.jam_mulai')),
        shiftEnd: @json(data_get($dinasAktif, 'shift.jam_selesai')),
        timeZone: 'Asia/Jakarta',
        locale: 'id-ID',

        settings: {
            toleransiKeterlambatanMenit: {{ (int) data_get($realisasiKaSetting, 'toleransi_keterlambatan_menit', 5) }},
            peringatanKeterlambatanTinggiMenit: {{ (int) data_get($realisasiKaSetting, 'peringatan_keterlambatan_tinggi_menit', 10) }},
            jarakMinimumAntarKaMenit: {{ (int) data_get($realisasiKaSetting, 'jarak_minimum_antar_ka_menit', 0) }},
            batasPenutupanPalangMenit: {{ (int) data_get($realisasiKaSetting, 'batas_penutupan_palang_menit', 0) }},

            warningKeterlambatanAktif: @json((bool) data_get($realisasiKaSetting, 'warning_keterlambatan_aktif', false)),
            warningKeterlambatanTinggiAktif: @json((bool) data_get($realisasiKaSetting, 'warning_keterlambatan_tinggi_aktif', false)),
            warningAntarKaAktif: @json((bool) data_get($realisasiKaSetting, 'warning_antar_ka_aktif', false)),
            warningPenutupanPalangAktif: @json((bool) data_get($realisasiKaSetting, 'warning_penutupan_palang_aktif', false))
        }
    };
</script>

{{-- Jam realtime, filter jadwal, tabel, dan autosave --}}
<script>
    window.realisasiKaConfig = window.realisasiKaConfig || {};

    const CONFIG = window.realisasiKaConfig;
    const SETTINGS = CONFIG.settings || {};

    if (!Array.isArray(CONFIG.data)) {
        CONFIG.data = [];
    }

    const ZONA_WAKTU = CONFIG.timeZone || 'Asia/Jakarta';

    const BATAS_CEPAT_MENIT = 2;

    const BATAS_TELAT_MENIT = Number(
        SETTINGS.toleransiKeterlambatanMenit ?? 5
    );

    const BATAS_TELAT_TINGGI_MENIT = Number(
        SETTINGS.peringatanKeterlambatanTinggiMenit ?? 10
    );

    const BATAS_AWAS_MENIT = Number(
        SETTINGS.jarakMinimumAntarKaMenit ?? 0
    );

    const BATAS_PENUTUPAN_PALANG_MENIT = Number(
        SETTINGS.batasPenutupanPalangMenit ?? 0
    );

    const WARNING_KETERLAMBATAN_AKTIF =
        SETTINGS.warningKeterlambatanAktif === true;

    const WARNING_KETERLAMBATAN_TINGGI_AKTIF =
        SETTINGS.warningKeterlambatanTinggiAktif === true;

    const WARNING_ANTAR_KA_AKTIF =
        SETTINGS.warningAntarKaAktif === true;

    const WARNING_PENUTUPAN_PALANG_AKTIF =
        SETTINGS.warningPenutupanPalangAktif === true;

    const JEDA_SIMPAN_STATUS = 300;
    const JEDA_SIMPAN_CHECK = 600;
    const JEDA_SIMPAN_KETIK = 800;
    const JEDA_COBA_ULANG = 10000;

    const JUMLAH_KOLOM_TABEL = 15;
    const BATAS_KARAKTER_CATATAN = 5000;

    /*
     * BATAS_PENUTUPAN_PALANG_MENIT dan
     * WARNING_PENUTUPAN_PALANG_AKTIF sudah dibaca dari database.
     *
     * Peringatan penutupan palang belum diterapkan karena data
     * waktu penutupan/pembukaan palang belum tersedia pada objek KA.
     */

    /* =====================================================
    DATA KA
    ===================================================== */

    let dataKA = CONFIG.data.map((ka, index) => ({
        no: ka.no ?? index + 1,
        nomor: ka.nomor ?? '',
        nama: ka.nama ?? '',
        hulu: ka.hulu ?? '',
        hilir: ka.hilir ?? '',
        relasi: ka.relasi ?? '',
        arah: String(ka.arah ?? '').trim().toUpperCase(),
        jadwal: ka.jadwal ?? '-',
        aktual: ka.aktual ?? '',
        status: ['JALAN', 'BATAL', 'BELUM'].includes(ka.status) ?
            ka.status : 'BELUM',
        semboyan21: ka.semboyan21 ?? '',
        genta: ka.genta ?? '',
        pintu: ka.pintu ?? '',
        semboyan35: ka.semboyan35 ?? '',
        keterangan: ka.keterangan ?? '',
        jadwal_ka_id: ka.jadwal_ka_id ?? null,
        realisasi_ka_id: ka.realisasi_ka_id ?? null,
        is_manual: Boolean(ka.is_manual)
    }));

    // Status terakhir yang diketahui berhasil tersimpan di server.
    const savedStatuses = new Map(
        dataKA.map((ka, index) => [index, ka.status])
    );

    let manualCheck = {
        semboyan21: '',
        genta: '',
        pintu: '',
        semboyan35: ''
    };

    /* =====================================================
    UTILITAS
    ===================================================== */

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getWIBTime() {
        const parts = new Intl.DateTimeFormat('en-GB', {
            timeZone: ZONA_WAKTU,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hourCycle: 'h23'
        }).formatToParts(new Date());

        const result = {};

        parts.forEach(part => {
            if (['hour', 'minute', 'second'].includes(part.type)) {
                result[part.type] = Number(part.value);
            }
        });

        return {
            hour: result.hour ?? 0,
            minute: result.minute ?? 0,
            second: result.second ?? 0
        };
    }

    function getCurrentMinutes() {
        const now = getWIBTime();
        return now.hour * 60 + now.minute;
    }

    function getNowHHMM() {
        const now = getWIBTime();

        return (
            String(now.hour).padStart(2, '0') + ':' +
            String(now.minute).padStart(2, '0')
        );
    }

    function getNowHHMMSS() {
        const now = getWIBTime();

        return (
            String(now.hour).padStart(2, '0') + ':' +
            String(now.minute).padStart(2, '0') + ':' +
            String(now.second).padStart(2, '0')
        );
    }

    function timeToMinutes(time) {
        if (!time || time === '-') {
            return null;
        }

        const match = String(time).trim().match(
            /^(\d{1,2}):(\d{2})(?::\d{2})?$/
        );

        if (!match) {
            return null;
        }

        const hours = Number(match[1]);
        const minutes = Number(match[2]);

        if (
            !Number.isInteger(hours) ||
            !Number.isInteger(minutes) ||
            hours < 0 ||
            hours > 23 ||
            minutes < 0 ||
            minutes > 59
        ) {
            return null;
        }

        return hours * 60 + minutes;
    }

    function formatMinutes(totalMinutes) {
        totalMinutes = ((totalMinutes % 1440) + 1440) % 1440;

        return (
            String(Math.floor(totalMinutes / 60)).padStart(2, '0') +
            ':' +
            String(totalMinutes % 60).padStart(2, '0')
        );
    }

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /* =====================================================
    JAM REALTIME DAN RENTANG JAM SAAT INI
    ===================================================== */

    function getCurrentHourWindowMinutes() {
        const startMinutes = getCurrentMinutes();
        const endMinutes = startMinutes + 60;

        return {
            startMinutes,
            endMinutes
        };
    }

    function updateWindowRange() {
        const clock = document.getElementById('jamSekarang');
        const range = document.getElementById('windowRange');

        if (clock) {
            clock.textContent = getNowHHMMSS();
        }

        const {
            startMinutes,
            endMinutes
        } =
        getCurrentHourWindowMinutes();

        if (range) {
            const jamMulai = formatMinutes(startMinutes).replace(':', '.');
            const jamSelesai = formatMinutes(endMinutes).replace(':', '.');

            range.textContent =
                `Rentang jadwal ${jamMulai}–${jamSelesai} WIB`;
        }
    }

    /* =====================================================
    FILTER JADWAL BERDASARKAN JAM SAAT INI
    ===================================================== */

    function isWithinCurrentHour(ka, index) {
        if (ka.is_manual) {
            return !ka.realisasi_ka_id;
        }

        if (!ka.jadwal || ka.jadwal === '-') {
            return true;
        }

        const jadwalMinutes = timeToMinutes(ka.jadwal);

        if (jadwalMinutes === null) {
            return false;
        }

        // Batas jam shift dinas aktif.
        const shiftStart = timeToMinutes(CONFIG.shiftStart);
        const shiftEnd = timeToMinutes(CONFIG.shiftEnd);

        if (shiftStart === null || shiftEnd === null) {
            return false;
        }

        // Pastikan jadwal berada dalam shift aktif.
        const dalamShift = shiftStart <= shiftEnd ?
            jadwalMinutes >= shiftStart && jadwalMinutes <= shiftEnd :
            jadwalMinutes >= shiftStart || jadwalMinutes <= shiftEnd;

        if (!dalamShift) {
            return false;
        }

        const currentMinutes = getCurrentMinutes();

        // Rentang bergulir 60 menit.
        const selisihMaju =
            (jadwalMinutes - currentMinutes + 1440) % 1440;

        if (selisihMaju <= 60) {
            return true;
        }

        // Jadwal yang sudah lewat tetap tampil
        // selama belum berhasil diproses dan disimpan.
        const jadwalSudahLewat =
            jadwalMinutes < currentMinutes ||
            (
                currentMinutes < 60 &&
                jadwalMinutes >= 23 * 60
            );

        if (!jadwalSudahLewat) {
            return false;
        }

        const statusSudahDiproses = ['JALAN', 'BATAL'].includes(ka.status) &&
            savedStatuses.get(index) === ka.status &&
            !dirtyRows.has(index) &&
            !savingRows.has(index);

        return !statusSudahDiproses;
    }

    function getVisibleKA() {
        return dataKA
            .map((ka, index) => ({
                ka,
                index
            }))
            .filter(item => isWithinCurrentHour(item.ka, item.index));
    }

    function getVisibleIds() {
        return getVisibleKA()
            .map(item => item.index)
            .join(',');
    }

    /* =====================================================
    PERHITUNGAN SELISIH WAKTU
    ===================================================== */

    function getSelisihMenit(ka) {
        const jadwal = timeToMinutes(ka.jadwal);
        const aktual = timeToMinutes(ka.aktual);

        if (jadwal === null || aktual === null) {
            return null;
        }

        let diff = aktual - jadwal;

        // Menangani pergantian hari.
        if (diff > 720) diff -= 1440;
        if (diff < -720) diff += 1440;

        return diff;
    }

    /* =====================================================
    WARNA STATUS OTOMATIS
    ===================================================== */

    function getStatusColor(ka) {
        if (ka.status === 'BATAL') {
            return 'batal';
        }

        if (ka.status !== 'JALAN') {
            return 'belum';
        }

        const selisih = getSelisihMenit(ka);

        if (selisih === null) {
            return 'tepat';
        }

        /*
         * Peringatan keterlambatan tinggi diperiksa lebih dahulu
         * agar tidak tertimpa kategori keterlambatan biasa.
         */
        if (
            WARNING_KETERLAMBATAN_TINGGI_AKTIF &&
            selisih >= BATAS_TELAT_TINGGI_MENIT
        ) {
            return 'telat-tinggi';
        }

        if (
            WARNING_KETERLAMBATAN_AKTIF &&
            selisih > BATAS_TELAT_MENIT
        ) {
            return 'telat';
        }

        if (selisih < -BATAS_CEPAT_MENIT) {
            return 'cepat';
        }

        return 'tepat';
    }

    function getStatusNote(ka, color) {
        if (color === 'batal') {
            return 'KA batal';
        }

        const selisih = getSelisihMenit(ka);

        if (color === 'telat-tinggi') {
            return `Peringatan keterlambatan tinggi: ${selisih} menit`;
        }

        if (color === 'telat') {
            return `Terlambat ${selisih} menit`;
        }

        if (color === 'cepat') {
            return `Lebih cepat ${Math.abs(selisih)} menit`;
        }

        if (color === 'tepat') {
            if (selisih === null || selisih === 0) {
                return 'Tepat waktu';
            }

            return selisih > 0 ?
                `Terlambat ${selisih} menit (dalam toleransi)` :
                `Lebih cepat ${Math.abs(selisih)} menit (dalam toleransi)`;
        }

        return '';
    }

    /* =====================================================
    AWAS 2 ARAH
    ===================================================== */

    function getParitas(ka) {
        const match = String(ka.nomor ?? '').match(/\d+/);

        if (!match) {
            return '';
        }

        return Number(match[0].slice(-1)) % 2 === 0 ?
            'GENAP' :
            'GANJIL';
    }

    function getLabelArah(ka) {
        if (ka.arah === 'HULU' || ka.arah === 'HILIR') {
            return `dari ${ka.arah.toLowerCase()}`;
        }

        const paritas = getParitas(ka);

        return paritas ?
            `nomor ${paritas.toLowerCase()}` :
            'arah tidak diketahui';
    }

    function isBerlawananArah(a, b) {
        const arahValid = ['HULU', 'HILIR'];

        if (
            arahValid.includes(a.arah) &&
            arahValid.includes(b.arah)
        ) {
            return a.arah !== b.arah;
        }

        const paritasA = getParitas(a);
        const paritasB = getParitas(b);

        return (
            paritasA !== '' &&
            paritasB !== '' &&
            paritasA !== paritasB
        );
    }

    function getWaktuRujukan(ka) {
        if (ka.status === 'JALAN' && ka.aktual) {
            return timeToMinutes(ka.aktual);
        }

        return timeToMinutes(ka.jadwal);
    }

    function cariAwas2Arah() {
        /*
         * Peringatan tidak dihitung jika dinonaktifkan oleh Admin
         * atau jarak minimum belum dikonfigurasi.
         */
        if (
            !WARNING_ANTAR_KA_AKTIF ||
            !Number.isFinite(BATAS_AWAS_MENIT) ||
            BATAS_AWAS_MENIT <= 0
        ) {
            return {
                indexSet: new Set(),
                pairs: []
            };
        }

        const kandidat = getVisibleKA()
            .map(({
                ka,
                index
            }) => ({
                ka,
                index,
                waktu: getWaktuRujukan(ka)
            }))
            .filter(item =>
                item.ka.status !== 'BATAL' &&
                item.waktu !== null
            );

        const indexSet = new Set();
        const pairs = [];

        for (let i = 0; i < kandidat.length; i++) {
            for (let j = i + 1; j < kandidat.length; j++) {
                const a = kandidat[i];
                const b = kandidat[j];

                if (!isBerlawananArah(a.ka, b.ka)) {
                    continue;
                }

                /*
                 * Peringatan ditujukan pada pasangan yang setidaknya
                 * salah satu KA-nya belum diproses.
                 */
                if (
                    a.ka.status !== 'BELUM' &&
                    b.ka.status !== 'BELUM'
                ) {
                    continue;
                }

                let selisih = Math.abs(a.waktu - b.waktu);
                selisih = Math.min(selisih, 1440 - selisih);

                // Peringatan aktif jika jarak waktu <= batas minimum.
                if (selisih > BATAS_AWAS_MENIT) {
                    continue;
                }

                indexSet.add(a.index);
                indexSet.add(b.index);

                pairs.push({
                    a,
                    b,
                    selisih
                });
            }
        }

        pairs.sort((a, b) => a.selisih - b.selisih);

        return {
            indexSet,
            pairs
        };
    }

    function updateAwasBanner(info) {
        const banner = document.getElementById('awasBanner');

        if (!banner) {
            return;
        }

        if (!info.pairs.length) {
            banner.hidden = true;
            banner.innerHTML = '';
            return;
        }

        const describe = item =>
            `KA ${escapeHtml(item.ka.nomor)} ` +
            `(${escapeHtml(getLabelArah(item.ka))}, ` +
            `${formatMinutes(item.waktu)})`;

        const lines = info.pairs.map(pair => `
        <li>
            ${describe(pair.a)} &amp; ${describe(pair.b)}
            <span class="awas-gap">
                selisih ${pair.selisih} menit
            </span>
        </li>
    `).join('');

        banner.innerHTML = `
        <div class="awas-title">⚠ AWAS 2 ARAH</div>
        <div class="awas-text">
            KA dari dua arah akan melintas berdekatan.
            Tingkatkan kewaspadaan.
        </div>
        <ul class="awas-list">${lines}</ul>
    `;

        banner.hidden = false;
    }

    /* =====================================================
    STATUS PENYIMPANAN PER BARIS
    ===================================================== */

    let dirtyRows = new Set();
    let savingRows = new Set();
    let savedRows = new Set();
    let rowVersions = new Map();

    function getRowVersion(index) {
        return rowVersions.get(index) || 0;
    }

    function incrementRowVersion(index) {
        rowVersions.set(index, getRowVersion(index) + 1);
    }

    function stateBaris(index) {
        if (savingRows.has(index)) {
            return 'saving';
        }

        if (dirtyRows.has(index)) {
            return 'dirty';
        }

        if (
            savedRows.has(index) ||
            (dataKA[index] && dataKA[index].realisasi_ka_id)
        ) {
            return 'saved';
        }

        return 'dirty';
    }

    function refreshRowButtons() {
        document.querySelectorAll('[data-save-row]').forEach(button => {
            const index = Number(button.dataset.saveRow);
            const state = stateBaris(index);

            const labels = {
                dirty: 'Simpan',
                saving: 'Menyimpan…',
                saved: '✓ Tersimpan'
            };

            button.className = `row-save-btn ${state}`;
            const styles = {
                dirty: 'background:#2563eb;color:#fff;border:1px solid #2563eb;',
                saving: 'background:#64748b;color:#fff;border:1px solid #64748b;',
                saved: 'background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;'
            };

            button.style.cssText = `
            ${styles[state] || styles.dirty}
            padding:7px 12px;
            border-radius:7px;
            font-size:12px;
            font-weight:600;
            white-space:nowrap;
            cursor:pointer;
        `;
            button.textContent = labels[state] || 'Simpan';
            button.disabled = state === 'saving';
        });
    }

    function simpanBaris(index) {
        if (!dataKA[index]) {
            return;
        }

        dirtyRows.add(index);
        isDirty = true;

        refreshRowButtons();
        simpanRealisasi({
            index
        });
    }

    /* =====================================================
    KOMPONEN TABEL
    ===================================================== */
    function createCheckGroup(value, index, field) {
        const yesClass = value === 'yes' ?
            'bg-green-100 text-green-700 border-green-300' :
            'bg-white text-slate-500 border-slate-300 hover:bg-green-50 hover:text-green-700';

        const noClass = value === 'no' ?
            'bg-red-100 text-red-700 border-red-300' :
            'bg-white text-slate-500 border-slate-300 hover:bg-red-50 hover:text-red-700';

        const buttonBase = [
            'inline-flex items-center justify-center',
            'h-8 w-8 rounded-md border',
            'text-sm font-semibold transition-colors',
            'focus:outline-none focus:ring-2 focus:ring-offset-1'
        ].join(' ');

        return `
        <div class="check-group flex items-center justify-center gap-1">
            <button
                type="button"
                class="check ${buttonBase} ${yesClass}"
                onclick="setCheck(this, ${index}, '${field}', 'yes')"
                aria-label="Ya"
                title="Ya"
            >✓</button>

            <button
                type="button"
                class="check ${buttonBase} ${noClass}"
                onclick="setCheck(this, ${index}, '${field}', 'no')"
                aria-label="Tidak"
                title="Tidak"
            >×</button>
        </div>
    `;
    }

    function createStatus(ka, index) {
        const color = getStatusColor(ka);

        const labelMap = {
            tepat: 'JALAN',
            telat: 'TERLAMBAT',
            'telat-tinggi': 'TELAT TINGGI',
            cepat: 'LEBIH CEPAT',
            batal: 'BATAL'
        };

        const badgeStyleMap = {
            tepat: 'background:#f0fdf4;color:#15803d;border-color:#bbf7d0;',
            telat: 'background:#fefce8;color:#a16207;border-color:#fde68a;',
            'telat-tinggi': 'background:#fefce8;color:#a16207;border-color:#fde68a;',
            cepat: 'background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;',
            batal: 'background:#fef2f2;color:#b91c1c;border-color:#fecaca;'
        };

        const jalanActive = ka.status === 'JALAN';
        const batalActive = ka.status === 'BATAL';

        const buttonBaseStyle =
            'flex:1;min-width:0;height:36px;padding:0 8px;' +
            'border:1px solid #d5dce5;border-radius:9px;' +
            'box-shadow:none;font-size:12px;font-weight:500;' +
            'line-height:1.2;white-space:nowrap;cursor:pointer;' +
            'display:flex;align-items:center;justify-content:center;';

        const jalanStyle = buttonBaseStyle + (
            jalanActive ?
            'background:#f0fdf4;color:#15803d;border-color:#bbf7d0;' :
            'background:#f8fafc;color:#64748b;'
        );

        const batalStyle = buttonBaseStyle + (
            batalActive ?
            'background:#fef2f2;color:#b91c1c;border-color:#fecaca;' :
            'background:#f8fafc;color:#64748b;'
        );

        const note = getStatusNote(ka, color);

        return `

        <div style="
            display:flex;
            width:100%;
            min-width:0;
            gap:6px;
            align-items:center;
        ">
            <button
                type="button"
                data-print-status="jalan"
                style="${jalanStyle}"
                onclick="setStatus(${index}, 'JALAN')"
                aria-pressed="${jalanActive}">
                JALAN
            </button>

            <button
                type="button"
                data-print-status="batal"
                style="${batalStyle}"
                onclick="setStatus(${index}, 'BATAL')"
                aria-pressed="${batalActive}">
                BATAL
            </button>
        </div>

        ${note
? `<div style="margin-top:6px;white-space:normal;overflow-wrap:anywhere;font-size:12px;color:#6b7280;">${escapeHtml(note)}</div>`
: ''}
    `;
    }

    function createRowAction(index) {
        const state = stateBaris(index);

        const styles = {
            dirty: 'background:#2563eb;color:#fff;border:1px solid #2563eb;',
            saving: 'background:#64748b;color:#fff;border:1px solid #64748b;',
            saved: 'background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;'
        };

        const labels = {
            dirty: 'Simpan',
            saving: 'Menyimpan…',
            saved: '✓ Tersimpan'
        };

        return `
        <button
            type="button"
            class="row-save-btn ${state}"
            style="${styles[state] || styles.dirty} padding:7px 12px; border-radius:7px; font-size:12px; font-weight:600; white-space:nowrap; cursor:pointer;"
            data-save-row="${index}"
            onclick="simpanBaris(${index})"
            ${state === 'saving' ? 'disabled' : ''}
        >
            ${labels[state] || 'Simpan'}
        </button>
    `;
    }

    function renderTable(forPrint = false) {
        const tbody = document.getElementById('kaTableBody');

        if (!tbody) return;

        const awasInfo = cariAwas2Arah();
        updateAwasBanner(awasInfo);

        const visibleKA = forPrint
            ? dataKA.map((ka, index) => ({ ka, index }))
            : getVisibleKA();
        tbody.innerHTML = '';

        if (!visibleKA.length) {
            const message = dataKA.length === 0 ?
                'Belum ada jadwal KA untuk dinas aktif ini.' :
                'Tidak ada KA dalam rentang jam ini.';

            tbody.innerHTML = `
            <tr>
                <td colspan="${JUMLAH_KOLOM_TABEL}"
                    class="px-4 py-8 text-center text-sm text-gray-500">
                    ${escapeHtml(message)}
                </td>
            </tr>
        `;

            updateSummary();
            updateWindowRange();
            refreshRowButtons();
            return;
        }

        visibleKA.forEach(({
            ka,
            index
        }, visibleIndex) => {
            const row = document.createElement('tr');
            row.dataset.index = index;
            row.className = 'border-b border-gray-200 hover:bg-gray-50';

            if (awasInfo.indexSet.has(index)) {
                row.classList.add('awas-row');
            }

            row.innerHTML = `
            <td class="whitespace-nowrap px-3 py-3 text-center text-sm text-gray-600">
                ${visibleIndex + 1}
            </td>

            <td class="whitespace-nowrap px-3 py-3 text-sm font-semibold text-gray-800">
                ${escapeHtml(ka.nomor)}
                ${ka.is_manual
? '<div class="mt-1 text-xs font-medium text-amber-600">MANUAL</div>'
: ''}
            </td>

            <td class="min-w-36 px-3 py-3 text-sm text-gray-800">
                ${escapeHtml(ka.nama)}
                ${awasInfo.indexSet.has(index)
? '<div class="awas-badge">⚠ AWAS 2 ARAH</div>'
: ''}
            </td>

            <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-600">
                ${escapeHtml(ka.hulu || '-')}
            </td>

            <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-600">
                ${escapeHtml(ka.hilir || '-')}
            </td>

            <td class="min-w-28 px-3 py-3 text-sm text-gray-600">
                ${escapeHtml(ka.relasi || '-')}
            </td>

            <td class="whitespace-nowrap px-3 py-3 text-center text-sm font-medium text-gray-700">
                ${escapeHtml(ka.jadwal)}
            </td>

            <td class="min-w-32 px-3 py-3">
                <input
                    type="time"
                    class="actual-time w-full min-w-24 rounded-md border border-gray-300 px-2 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    value="${escapeHtml(ka.aktual)}"
                    onchange="updateActualTime(${index}, this.value)">
            </td>

            <td class="min-w-28 px-3 py-3 text-center">
                ${createStatus(ka, index)}
            </td>

            <td class="min-w-28 px-3 py-3 text-center">
                ${createCheckGroup(ka.semboyan21, index, 'semboyan21')}
            </td>

            <td class="min-w-24 px-3 py-3 text-center">
                ${createCheckGroup(ka.genta, index, 'genta')}
            </td>

            <td class="min-w-36 px-3 py-3 text-center">
                ${createCheckGroup(ka.pintu, index, 'pintu')}
            </td>

            <td class="min-w-28 px-3 py-3 text-center">
                ${createCheckGroup(ka.semboyan35, index, 'semboyan35')}
            </td>

            <td class="min-w-44 px-3 py-3">
                <input
                    type="text"
                    class="note-input w-full min-w-36 rounded-md border border-gray-300 px-2 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    value="${escapeHtml(ka.keterangan)}"
                    placeholder="Keterangan"
                    oninput="updateKeterangan(${index}, this.value)">
            </td>

            <td class="whitespace-nowrap px-3 py-3 text-center">
                ${createRowAction(index)}
            </td>
        `;

            tbody.appendChild(row);
        });

        updateSummary();
        updateWindowRange();
        refreshRowButtons();
    }


    /* =====================================================
    AKSI STATUS DAN PEMERIKSAAN
    ===================================================== */

    function setStatus(index, status) {
        const ka = dataKA[index];

        if (!ka || !['JALAN', 'BATAL'].includes(status)) {
            return;
        }

        const fieldSemboyan = [
            'semboyan21',
            'genta',
            'pintu',
            'semboyan35'
        ];

        if (ka.status === status) {
            ka.status = 'BELUM';
            ka.aktual = '';

            fieldSemboyan.forEach(field => {
                ka[field] = '';
            });
        } else if (status === 'JALAN') {
            ka.status = 'JALAN';
            ka.aktual = getNowHHMM();

            fieldSemboyan.forEach(field => {
                ka[field] = 'yes';
            });
        } else {
            ka.status = 'BATAL';
            ka.aktual = '';

            fieldSemboyan.forEach(field => {
                ka[field] = 'no';
            });
        }

        incrementRowVersion(index);
        renderTable();
        tandaiBerubah(JEDA_SIMPAN_STATUS, index);
    }

    function updateActualTime(index, value) {
        if (!dataKA[index]) {
            return;
        }

        dataKA[index].aktual = value;
        incrementRowVersion(index);

        renderTable();
        tandaiBerubah(JEDA_SIMPAN_STATUS, index);
    }

    function setCheck(button, index, field, value) {
        if (!dataKA[index]) {
            return;
        }

        dataKA[index][field] = value;

        incrementRowVersion(index);
        renderTable();
        tandaiBerubah(JEDA_SIMPAN_CHECK, index);
    }

    function updateKeterangan(index, value) {
        if (!dataKA[index]) {
            return;
        }

        dataKA[index].keterangan = value;
        incrementRowVersion(index);

        tandaiBerubah(JEDA_SIMPAN_KETIK, index);
    }

    /* =====================================================
    RINGKASAN
    ===================================================== */

    function updateSummary() {
        const allData = dataKA;
        const total = allData.length;
        const totalManual = allData.filter(ka => ka.is_manual).length;

        let tepat = 0;
        let telat = 0;
        let cepat = 0;
        let batal = 0;
        let belum = 0;

        allData.forEach(ka => {
            const color = getStatusColor(ka);

            if (color === 'tepat') {
                tepat++;
            } else if (
                color === 'telat' ||
                color === 'telat-tinggi'
            ) {
                telat++;
            } else if (color === 'cepat') {
                cepat++;
            } else if (color === 'batal') {
                batal++;
            } else {
                belum++;
            }
        });

        const setText = (id, value) => {
            const element = document.getElementById(id);

            if (element) {
                element.textContent = value;
            }
        };

        const setWidth = (id, value) => {
            const element = document.getElementById(id);

            if (element) {
                element.style.width = value;
            }
        };

        setText('totalKA', total);
        setText('manualKA', totalManual);
        setText('jalanKA', tepat);
        setText('telatKA', telat);
        setText('cepatKA', cepat);
        setText('batalKA', batal);
        setText('belumKA', belum);

        const selesai = tepat + telat + cepat + batal;
        const progress = total ?
            Math.round((selesai / total) * 100) :
            0;

        setText('progressText', `${progress}%`);

        const persen = jumlah =>
            `${total ? (jumlah / total) * 100 : 0}%`;

        setWidth('progressGreen', persen(tepat));
        setWidth('progressYellow', persen(telat));
        setWidth('progressBlue', persen(cepat));
        setWidth('progressRed', persen(batal));
        setWidth('progressGray', persen(belum));
    }

    /* =====================================================
    PENYIMPANAN OTOMATIS
    ===================================================== */

    let isSaving = false;
    let isDirty = false;
    let saveQueued = false;
    let queuedManual = false;
    let saveGagal = false;

    let autoSaveTimer = null;
    let retryTimer = null;

    function setSaveStatus(state, text) {
        const box = document.getElementById('saveStatus');
        const label = document.getElementById('saveStatusText');

        if (box) {
            box.className = `save-status ${state}`;
        }

        if (label) {
            label.textContent = text;
        }
    }

    function tandaiBerubah(delay = JEDA_SIMPAN_STATUS, index) {
        isDirty = true;

        if (index !== undefined && index !== null) {
            dirtyRows.add(index);
        }

        setSaveStatus('pending', 'Perubahan belum disimpan…');
        refreshRowButtons();

        clearTimeout(autoSaveTimer);
    }

    function ambilCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function formatJamStatusSimpan() {
        const now = getWIBTime();

        return (
            String(now.hour).padStart(2, '0') + ':' +
            String(now.minute).padStart(2, '0')
        );
    }

    async function simpanRealisasi(options = {}) {
        const otomatis = options.otomatis === true;
        const tombol = document.getElementById('simpanBtn');

        if (dataKA.length === 0) {
            if (!otomatis) {
                alert('Belum ada data KA untuk disimpan.');
            }

            return;
        }

        if (isSaving) {
            saveQueued = true;

            if (!otomatis) {
                queuedManual = true;
            }

            return;
        }

        clearTimeout(autoSaveTimer);
        clearTimeout(retryTimer);

        isSaving = true;

        const versiDikirim = new Map();
        const barisDikirim = new Set(dirtyRows);

        dataKA.forEach((_, index) => {
            versiDikirim.set(index, getRowVersion(index));
        });

        const snapshot = JSON.parse(JSON.stringify(dataKA));

        isDirty = false;
        savingRows = new Set(barisDikirim);
        dirtyRows = new Set();

        refreshRowButtons();
        setSaveStatus('saving', 'Menyimpan…');

        if (!otomatis && tombol) {
            tombol.disabled = true;
            tombol.textContent = 'Menyimpan...';
        }

        try {
            const response = await fetch(CONFIG.storeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': ambilCsrfToken()
                },
                body: JSON.stringify({
                    data: snapshot
                })
            });

            const contentType = response.headers.get('content-type') || '';

            if (!contentType.includes('application/json')) {
                let error;

                if (response.status === 419) {
                    error = new Error(
                        'Sesi berakhir. Muat ulang halaman, lalu coba lagi.'
                    );
                    error.permanen = true;
                } else if (
                    response.status === 401 ||
                    response.status === 403
                ) {
                    error = new Error(
                        'Akses ditolak. Silakan login kembali.'
                    );
                    error.permanen = true;
                } else {
                    error = new Error(
                        'Server tidak mengembalikan JSON. Periksa route dan log Laravel.'
                    );
                }

                throw error;
            }

            const result = await response.json();

            if (!response.ok) {
                const pesanValidasi = result.errors ?
                    Object.values(result.errors).flat().join('\n') :
                    result.message;

                const error = new Error(
                    pesanValidasi || 'Gagal menyimpan realisasi KA.'
                );

                error.permanen = response.status === 422;

                throw error;
            }

            if (Array.isArray(result.data)) {
                result.data.forEach(item => {
                    const index = Number(item.index);

                    if (
                        Number.isInteger(index) &&
                        index >= 0 &&
                        dataKA[index] &&
                        item.realisasi_ka_id !== undefined &&
                        item.realisasi_ka_id !== null
                    ) {
                        dataKA[index].realisasi_ka_id =
                            item.realisasi_ka_id;
                    }
                });
            }

            barisDikirim.forEach(index => {
                if (
                    getRowVersion(index) === versiDikirim.get(index)
                ) {
                    savedRows.add(index);
                    savedStatuses.set(index, dataKA[index].status);
                    dirtyRows.delete(index);
                } else {
                    dirtyRows.add(index);
                }
            });

            savingRows = new Set();
            saveGagal = false;
            renderTable();
            if (dirtyRows.size > 0 || isDirty) {
                isDirty = true;

                setSaveStatus(
                    'pending',
                    'Ada perubahan baru yang belum disimpan…'
                );
            } else {
                isDirty = false;

                const jam = formatJamStatusSimpan();

                if (result.serah_terima_sinkron === false) {
                    setSaveStatus(
                        'saved',
                        `Tersimpan pukul ${jam} (sinkron ke Serah Terima gagal)`
                    );
                } else {
                    setSaveStatus(
                        'saved',
                        otomatis ?
                        `Tersimpan otomatis pukul ${jam}` :
                        `Tersimpan pukul ${jam}`
                    );
                }
            }

            if (!otomatis) {
                alert('Berhasil disimpan!');
            }

        } catch (error) {
            console.error('Gagal menyimpan realisasi:', error);

            saveGagal = true;
            isDirty = true;

            barisDikirim.forEach(index => dirtyRows.add(index));
            savingRows = new Set();

            if (error.permanen) {
                setSaveStatus(
                    'error',
                    `Gagal menyimpan: ${error.message}`
                );
            } else {
                setSaveStatus(
                    'error',
                    'Gagal menyimpan. Silakan klik Simpan untuk mencoba lagi.'
                );
            }

            if (!otomatis) {
                alert(
                    'Realisasi belum berhasil disimpan.\n\n' +
                    error.message
                );
            }
        } finally {
            isSaving = false;

            refreshRowButtons();

            if (tombol) {
                tombol.disabled = false;
                tombol.textContent = '▣  Simpan Semua';
            }

            if (saveQueued) {
                const manual = queuedManual;

                saveQueued = false;
                queuedManual = false;

                if (manual && (isDirty || dirtyRows.size > 0)) {
                    simpanRealisasi();
                }
            }
        }
    }

    async function tungguSimpanSelesai() {
        while (isSaving) {
            await sleep(150);
        }

        return !isDirty && !saveGagal;
    }

    window.addEventListener('beforeunload', event => {
        if (isDirty) {
            event.preventDefault();
            event.returnValue = '';
        }
    });

    window.addEventListener('offline', () => {
        setSaveStatus(
            'error',
            'Tidak ada koneksi internet.'
        );
    });

    /* =====================================================
    TOMBOL SELESAI
    ===================================================== */

    const selesaiLink = document.getElementById('selesaiLink');

    if (selesaiLink) {
        selesaiLink.addEventListener('click', async event => {
            if (!isDirty && !isSaving && !saveGagal) {
                return;
            }

            event.preventDefault();

            const berhasil = await tungguSimpanSelesai();

            if (berhasil) {
                window.location.href = selesaiLink.href;
                return;
            }

            const lanjut = confirm(
                'Sebagian data belum berhasil tersimpan.\n\n' +
                'Tetap lanjut ke Serah Terima Dinas?'
            );

            if (lanjut) {
                window.location.href = selesaiLink.href;
            }
        });
    }

    function cetakDokumen() {
    const tbody = document.getElementById('kaTableBody');

    if (!tbody) return;

    // Tampilkan seluruh KA khusus untuk proses cetak.
    renderTable(true);

    function pulihkanTampilan() {
        renderTable();
        window.removeEventListener('afterprint', pulihkanTampilan);
    }

    window.addEventListener('afterprint', pulihkanTampilan);

    window.print();
}

    /* =====================================================
    TAMBAH KA MANUAL
    ===================================================== */

    function toggleFormTambahKA(forceOpen) {
        const form = document.getElementById('formTambahKa');
        const btn = document.getElementById('toggleTambahKa');

        if (!form || !btn) {
            return;
        }

        const open = typeof forceOpen === 'boolean' ?
            forceOpen :
            form.hidden;

        form.hidden = !open;
        btn.hidden = open;
        btn.setAttribute('aria-expanded', String(open));

        if (open) {
            form.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });

            document.getElementById('manualNomor')?.focus();
        } else {
            resetFormManual();
            btn.focus();
        }
    }

    function setManualCheck(button) {
        const group = button.dataset.group;
        const value = button.dataset.value;

        const fieldMap = {
            manual21: 'semboyan21',
            manualGenta: 'genta',
            manualPintu: 'pintu',
            manual35: 'semboyan35'
        };

        const field = fieldMap[group];
        if (!field || !['yes', 'no'].includes(value)) return;

        const buttons = document.querySelectorAll(`.mini-check [data-group="${group}"]`);
        const baseClasses = [
            'border-slate-300', 'bg-white', 'text-slate-500'
        ];

        buttons.forEach(item => {
            item.classList.remove(
                'border-green-300', 'bg-green-100', 'text-green-700',
                'border-red-300', 'bg-red-100', 'text-red-700'
            );
            baseClasses.forEach(className => item.classList.add(className));
            item.setAttribute('aria-pressed', 'false');
        });

        button.classList.remove(...baseClasses);
        button.classList.add(
            value === 'yes' ? 'border-green-300' : 'border-red-300',
            value === 'yes' ? 'bg-green-100' : 'bg-red-100',
            value === 'yes' ? 'text-green-700' : 'text-red-700'
        );
        button.setAttribute('aria-pressed', 'true');

        manualCheck[field] = value;
    }

    function resetManualChecks() {
        manualCheck = {
            semboyan21: '',
            genta: '',
            pintu: '',
            semboyan35: ''
        };

        document.querySelectorAll('.mini-check [data-group]').forEach(button => {
            button.classList.remove(
                'border-green-300', 'bg-green-100', 'text-green-700',
                'border-red-300', 'bg-red-100', 'text-red-700'
            );
            button.classList.add('border-slate-300', 'bg-white', 'text-slate-500');
            button.setAttribute('aria-pressed', 'false');
        });
    }

    function resetFormManual() {
        [
            'manualNomor',
            'manualNama',
            'manualHulu',
            'manualHilir',
            'manualRelasi',
            'manualAktual',
            'manualKeterangan'
        ].forEach(id => {
            const element = document.getElementById(id);

            if (element) {
                element.value = '';
            }
        });

        resetManualChecks();
    }

    function ambilNilaiInput(id) {
        const element = document.getElementById(id);
        return element ? element.value.trim() : '';
    }

    function tambahKAManual() {
        const nomor = ambilNilaiInput('manualNomor');
        const nama = ambilNilaiInput('manualNama');
        const hulu = ambilNilaiInput('manualHulu');
        const hilir = ambilNilaiInput('manualHilir');
        const relasi = ambilNilaiInput('manualRelasi');
        const keterangan = ambilNilaiInput('manualKeterangan');
        const aktual = ambilNilaiInput('manualAktual') || getNowHHMM();

        if (!nomor) {
            alert('Nomor KA wajib diisi.');
            document.getElementById('manualNomor')?.focus();
            return;
        }

        if (!nama) {
            alert('Nama KA wajib diisi.');
            document.getElementById('manualNama')?.focus();
            return;
        }

        const kaBaru = {
            no: dataKA.length + 1,
            nomor,
            nama,
            hulu,
            hilir,
            relasi,
            arah: '',
            jadwal: '-',
            aktual,
            status: 'JALAN',
            semboyan21: manualCheck.semboyan21,
            genta: manualCheck.genta,
            pintu: manualCheck.pintu,
            semboyan35: manualCheck.semboyan35,
            keterangan: keterangan || 'KA tambahan',
            jadwal_ka_id: null,
            realisasi_ka_id: null,
            is_manual: true
        };

        dataKA.push(kaBaru);

        const indexBaru = dataKA.length - 1;

        incrementRowVersion(indexBaru);
        dirtyRows.add(indexBaru);

        renderTable();
        toggleFormTambahKA(false);
        tandaiBerubah(JEDA_SIMPAN_STATUS, indexBaru);
    }

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') {
            return;
        }

        const form = document.getElementById('formTambahKa');

        if (form && !form.hidden) {
            toggleFormTambahKA(false);
        }
    });

    /* =====================================================
    CATATAN DINAS - MODAL
    ===================================================== */

    function openCatatanModal() {
        const modal = document.getElementById('catatanModal');

        if (!modal) {
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');

        document.getElementById('cdmCatatan')?.focus();
    }

    function closeCatatanModal() {
        const modal = document.getElementById('catatanModal');

        if (!modal) {
            return;
        }

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
    }

    function initCatatanModal() {
        const modal = document.getElementById('catatanModal');

        if (!modal) {
            return;
        }

        const form = document.getElementById('catatanForm');
        const textarea = document.getElementById('cdmCatatan');
        const counter = document.getElementById('cdmCounter');
        const submitBtn = form ?
            form.querySelector('.cdm-save') :
            null;

        const labelAwal = submitBtn ? submitBtn.textContent : '';
        let sedangKirim = false;

        function updateHitung() {
            if (!counter || !textarea) {
                return;
            }

            counter.textContent =
                `${textarea.value.length.toLocaleString('id-ID')} / ` +
                `${BATAS_KARAKTER_CATATAN.toLocaleString('id-ID')} karakter`;
        }

        function setTombol(disabled, text) {
            if (!submitBtn) {
                return;
            }

            submitBtn.disabled = disabled;
            submitBtn.textContent = text;
        }

        modal.addEventListener('mousedown', event => {
            if (event.target === modal) {
                closeCatatanModal();
            }
        });

        document.addEventListener('keydown', event => {
            if (
                event.key === 'Escape' &&
                !modal.classList.contains('hidden')
            ) {
                closeCatatanModal();
            }
        });

        if (textarea) {
            textarea.addEventListener('input', updateHitung);
            updateHitung();

            textarea.addEventListener('keydown', event => {
                if (
                    (event.ctrlKey || event.metaKey) &&
                    event.key === 'Enter'
                ) {
                    event.preventDefault();

                    if (form && typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    }
                }
            });
        }

        if (form) {
            form.addEventListener('submit', async event => {
                if (sedangKirim) {
                    event.preventDefault();
                    return;
                }

                if (!form.checkValidity()) {
                    return;
                }

                if (textarea && textarea.value.trim() === '') {
                    event.preventDefault();
                    textarea.focus();
                    return;
                }

                event.preventDefault();

                sedangKirim = true;
                setTombol(true, 'Menyimpan...');

                if (isDirty || isSaving || saveGagal) {
                    const berhasil = await tungguSimpanSelesai();

                    if (!berhasil) {
                        const lanjut = confirm(
                            'Sebagian data realisasi KA belum berhasil tersimpan.\n\n' +
                            'Tetap simpan catatan dinas?'
                        );

                        if (!lanjut) {
                            sedangKirim = false;
                            setTombol(false, labelAwal);
                            return;
                        }
                    }
                }

                form.submit();
            });
        }

        window.addEventListener('pageshow', event => {
            if (event.persisted) {
                sedangKirim = false;
                setTombol(false, labelAwal);
            }
        });

        if (modal.dataset.openOnLoad === '1') {
            openCatatanModal();
        }
    }

    /* =====================================================
    REFRESH FILTER WAKTU
    ===================================================== */

    let lastVisibleIds = '';

    function refreshTimeFilter() {
        updateWindowRange();

        const currentVisibleIds = getVisibleIds();

        /*
         * Render ulang hanya jika daftar KA yang tampil berubah.
         * Ini mencegah input yang sedang diketik ter-reset setiap detik.
         */
        if (currentVisibleIds !== lastVisibleIds) {
            lastVisibleIds = currentVisibleIds;
            renderTable();
        }
    }

    /* =====================================================
    MULAI
    ===================================================== */

    function initRealisasiKA() {
        renderTable();

        lastVisibleIds = getVisibleIds();

        updateWindowRange();

        setSaveStatus('idle', 'Penyimpanan otomatis aktif');

        initCatatanModal();

        setInterval(refreshTimeFilter, 1000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRealisasiKA, {
            once: true
        });
    } else {
        initRealisasiKA();
    }
</script>
@endpush