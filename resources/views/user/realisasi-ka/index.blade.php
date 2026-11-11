@extends('layouts.user')

@section('title', 'Realisasi KA - Sistem PJL')

@push('styles')
    <style>
        .realisasi-table { width: 100%; table-layout: fixed; border-collapse: collapse; }
        .realisasi-table th, .realisasi-table td { overflow-wrap: anywhere; word-break: normal; }
        .realisasi-table th { padding: .5rem .25rem; font-size: .68rem; line-height: 1.2; }
        .realisasi-table td { padding: .4rem .2rem; vertical-align: middle; }
        .realisasi-table input { max-width: 100%; min-width: 0; }
        .print-document-header-row { display: none; }
        .print-document-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .4rem .6rem; color: #1d2d50; text-align: left; }
        .print-document-brand, .print-document-identity { display: flex; align-items: center; gap: .65rem; }
        .print-document-brand img { width: 2.6rem; height: 2.6rem; object-fit: contain; }
        .print-document-brand div, .print-document-identity { display: flex; flex-direction: column; gap: .12rem; }
        .print-document-brand strong, .print-document-identity strong { font-size: .75rem; }
        .print-document-brand span, .print-document-identity span { font-size: .62rem; }
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            body { background: #fff !important; }
            .realisasi-table { table-layout: fixed; font-size: 7.5pt; }
            .realisasi-table th, .realisasi-table td { padding: 1mm .8mm !important; }
            .realisasi-table thead { display: table-header-group; }
            .realisasi-table .print-document-header-row { display: table-row !important; }
            .realisasi-table tr { break-inside: avoid; page-break-inside: avoid; }
            .add-section, .bottom-row, #catatanDinasQuick, #catatanModal { display: none !important; }
            .realisasi-table th:last-child, .realisasi-table td:last-child { display: none !important; }
}
    </style>
@endpush

@section('content')
    <main class="main mx-auto w-full max-w-[1680px] space-y-4 px-3 py-4 sm:px-5">
        <section class="page-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">

            {{-- INFORMASI DINAS --}}
            <div class="filter-grid grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="tanggal" class="field-label mb-1 block text-xs font-semibold tracking-wide text-slate-600">HARI / TANGGAL</label>
                    <input
                        type="text"
                        id="tanggal"
                        class="field-input w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-800"
                        value="{{ ($dinasAktif ?? null)
                            ? \Carbon\Carbon::parse($dinasAktif->tanggal_dinas)->locale('id')->translatedFormat('l, d F Y')
                            : \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}"
                        readonly>
                </div>

                <div>
                    <label for="nomorJpl" class="field-label mb-1 block text-xs font-semibold tracking-wide text-slate-600">NOMOR JPL</label>
                    <input
                        id="nomorJpl"
                        type="text"
                        class="field-input w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-800"
                        value="{{ ($dinasAktif ?? null) ? ($dinasAktif->jpl?->kode_jpl ?? '-') : '-' }}"
                        readonly>
                </div>

                <div>
                    <label for="stasiunRegional" class="field-label mb-1 block text-xs font-semibold tracking-wide text-slate-600">STASIUN / REGIONAL</label>
                    <input
                        id="stasiunRegional"
                        type="text"
                        class="field-input w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-800"
                        value="{{ ($dinasAktif ?? null) ? ($dinasAktif->jpl?->nama_jpl ?? '-') : '-' }}"
                        readonly>
                </div>

                <div>
                    <label for="shift" class="field-label mb-1 block text-xs font-semibold tracking-wide text-slate-600">SHIFT</label>
                    <input
                        id="shift"
                        type="text"
                        class="field-input w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-800"
                        value="{{ ($dinasAktif ?? null) ? ($dinasAktif->shift?->nama_shift ?? '-') : '-' }}"
                        readonly>
                </div>
            </div>

            {{-- DAFTAR KA --}}
            <div class="section-title mt-4 mb-3 text-base font-bold text-slate-800">Daftar KA Terjadwal</div>

            {{-- JAM SEKARANG DAN RENTANG FILTER --}}
            <div class="time-window-info mb-3 flex flex-col gap-2 rounded-xl bg-blue-50 p-3 text-sm text-blue-900 md:flex-row md:items-center md:justify-between">
                <span>
                    Filter jadwal: <strong>60 menit ke depan dari waktu sekarang</strong>.
                    KA manual tetap ditampilkan.
                </span>

                <div class="waktu-info flex flex-wrap gap-3 text-xs">
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
                class="awas-banner mb-3 rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm text-rose-800"
                role="alert"
                aria-live="assertive"
                hidden>
            </div>

            {{-- RINGKASAN REALISASI --}}
            <div
                class="summary-compact mb-3 rounded-xl border border-slate-200 bg-slate-50 p-3"
                role="group"
                aria-label="Ringkasan realisasi shift"
                title="Rekap menghitung seluruh KA dalam shift, termasuk KA yang sudah dicatat.">

                <div class="summary-chips flex flex-wrap gap-2">
                    <div class="summary-chip inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs">
                        <span class="chip-label text-slate-600">Total</span>
                        <span id="totalKA" class="chip-number font-bold text-slate-800">0</span>
                    </div>

                    <div class="summary-chip inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs">
                        <span class="chip-dot dot-green"></span>
                        <span class="chip-label text-slate-600">Jalan</span>
                        <span id="jalanKA" class="chip-number green font-bold text-slate-800">0</span>
                    </div>

                    <div class="summary-chip inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs">
                        <span class="chip-dot dot-yellow"></span>
                        <span class="chip-label text-slate-600">Terlambat</span>
                        <span id="telatKA" class="chip-number yellow font-bold text-slate-800">0</span>
                    </div>

                    <div class="summary-chip inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs">
                        <span class="chip-dot dot-blue"></span>
                        <span class="chip-label text-slate-600">Lebih cepat</span>
                        <span id="cepatKA" class="chip-number blue font-bold text-slate-800">0</span>
                    </div>

                    <div class="summary-chip inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs">
                        <span class="chip-dot dot-red"></span>
                        <span class="chip-label text-slate-600">Batal</span>
                        <span id="batalKA" class="chip-number red font-bold text-slate-800">0</span>
                    </div>

                    <div class="summary-chip inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs">
                        <span class="chip-dot dot-gray"></span>
                        <span class="chip-label text-slate-600">Belum diproses</span>
                        <span id="belumKA" class="chip-number font-bold text-slate-800">0</span>
                    </div>
                </div>

                <div class="summary-progress mt-3 flex items-center gap-3 text-xs text-slate-600">
                    <div class="progress-bar flex h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-200">
                        <div id="progressGreen" class="progress-green h-full bg-green-500" style="width: 0%"></div>
                        <div id="progressYellow" class="progress-yellow h-full bg-amber-400" style="width: 0%"></div>
                        <div id="progressBlue" class="progress-blue h-full bg-blue-500" style="width: 0%"></div>
                        <div id="progressRed" class="progress-red h-full bg-rose-500" style="width: 0%"></div>
                        <div id="progressGray" class="progress-gray h-full bg-slate-300" style="width: 100%"></div>
                    </div>

                    <span class="progress-label">Progres</span>
                    <span id="progressText" class="progress-text">0%</span>
                </div>
            </div>

            {{-- KETERANGAN WARNA --}}
            <div class="color-legend mb-3 rounded-xl border border-slate-200 bg-white p-3" role="group" aria-label="Keterangan warna">
                <div class="legend-title mb-2 text-xs font-bold text-slate-700">Keterangan Warna</div>

                <div class="legend-list flex flex-wrap gap-x-4 gap-y-2">
                    <div class="legend-item inline-flex items-center gap-2 text-xs text-slate-600">
                        <span class="legend-swatch sw-green inline-block h-3 w-3 rounded-full bg-green-500"></span>
                        <span class="legend-text text-xs text-slate-600">Hijau: tepat waktu</span>
                    </div>

                    <div class="legend-item inline-flex items-center gap-2 text-xs text-slate-600">
                        <span class="legend-swatch sw-yellow inline-block h-3 w-3 rounded-full bg-amber-400"></span>
                        <span class="legend-text text-xs text-slate-600">
                            Kuning: terlambat melewati batas peringatan yang dikonfigurasi
                        </span>
                    </div>

                    <div class="legend-item inline-flex items-center gap-2 text-xs text-slate-600">
                        <span class="legend-swatch sw-blue inline-block h-3 w-3 rounded-full bg-blue-500"></span>
                        <span class="legend-text text-xs text-slate-600">Biru: lebih cepat dari 2 menit</span>
                    </div>

                    <div class="legend-item inline-flex items-center gap-2 text-xs text-slate-600">
                        <span class="legend-swatch sw-red inline-block h-3 w-3 rounded-full bg-rose-500"></span>
                        <span class="legend-text text-xs text-slate-600">Merah: KA batal</span>
                    </div>

                    <div class="legend-item inline-flex items-center gap-2 text-xs text-slate-600">
                        <span class="legend-swatch sw-gray inline-block h-3 w-3 rounded-full bg-slate-300"></span>
                        <span class="legend-text text-xs text-slate-600">Abu-abu: belum diproses</span>
                    </div>

                    <div class="legend-item inline-flex items-center gap-2 text-xs text-slate-600">
                        <span class="legend-swatch sw-awas inline-block h-3 w-3 rounded-full bg-pink-300"></span>
                        <span class="legend-text text-xs text-slate-600">Merah muda: AWAS 2 ARAH</span>
                    </div>

                    <div class="legend-item inline-flex items-center gap-2 text-xs text-slate-600">
                        <span class="legend-mark mk-yes">✓</span>
                        <span class="legend-mark mk-no">×</span>
                        <span class="legend-text text-xs text-slate-600">
                            Pemeriksaan: ✓ Ya, × Tidak, abu-abu Belum diisi
                        </span>
                    </div>
                </div>
            </div>

            <div class="print-color-legend mb-3 rounded-xl border border-slate-200 bg-white p-3 hidden print:flex print:flex-wrap print:gap-3 print:text-[9px]" aria-label="Keterangan warna dan pemeriksaan">
                <span><i class="sw-green"></i> Tepat waktu</span>
                <span><i class="sw-yellow"></i> Terlambat</span>
                <span><i class="sw-blue"></i> Lebih cepat</span>
                <span><i class="sw-red"></i> Batal</span>
                <span><i class="sw-gray"></i> Belum diproses</span>
                <span><i class="sw-awas"></i> AWAS 2 ARAH</span>
                <span class="print-check-legend">Pemeriksaan: YA / TIDAK / BELUM</span>
                <span class="print-simultaneous-legend">KA Bersamaan: peringatan merah</span>
            </div>

            {{-- TABEL REALISASI KA --}}
            @php
                $petugasCetak = auth()->user();
                $statusPetugasCetak = $dinasAktif
                    ? ($dinasAktif->is_pengganti ? 'WAKILAN' : 'REGULER')
                    : (strtolower((string) ($petugasCetak?->status_petugas ?? $petugasCetak?->jenis_petugas ?? 'reguler')) === 'wakilan'
                        ? 'WAKILAN'
                        : 'REGULER');
            @endphp
            <div class="table-wrapper w-full min-w-0 max-w-full overflow-hidden rounded-xl border border-slate-200">
                <table class="realisasi-table w-full text-center text-xs" aria-label="Tabel realisasi KA">
                    <colgroup>
                        <col style="width:4%"><col style="width:8%"><col style="width:9%">
                        <col style="width:8%"><col style="width:7%"><col style="width:8%">
                        <col style="width:8%"><col style="width:7%"><col style="width:7%">
                        <col style="width:7%"><col style="width:10%"><col style="width:9%">
                        <col style="width:8%">
                    </colgroup>
                    <thead class="bg-[#1d2d50] text-white">
                        <tr class="print-document-header-row">
                            <th colspan="13">
                                <header class="print-document-header">
                                    <div class="print-document-brand">
                                        <img src="{{ asset('images/logo-kai.jpg') }}" alt="Logo KAI">
                                        <div>
                                            <strong>KAI OPERASIONAL</strong>
                                            <span>SISTEM DIGITAL PJL</span>
                                            <span class="print-document-title">Laporan Realisasi KA</span>
                                        </div>
                                    </div>
                                    <div class="print-document-identity">
                                        <strong>{{ $petugasCetak?->name ?? 'Petugas' }}</strong>
                                        <span>NIP / NIPKWT: {{ $petugasCetak?->nip ?: '-' }}</span>
                                        <span>Status: {{ $statusPetugasCetak }}</span>
                                    </div>
                                </header>
                            </th>
                        </tr>
                        <tr>
                            <th>NO</th>
                            <th>NOMOR KA</th>
                            <th>NAMA KA</th>
                            <th>RELASI</th>
                            <th>JAM JADWAL</th>
                            <th>JAM AKTUAL</th>
                            <th>STATUS</th>
                            <th>S21</th>
                            <th>GENTA</th>
                            <th>PINTU</th>
                            <th>S35</th>
                            <th>KETERANGAN</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody id="kaTableBody"></tbody>
                </table>
            </div>

            {{-- TAMBAH KA MANUAL --}}
            <section class="add-section mt-4 print:hidden">
                <button
                    type="button"
                    id="toggleTambahKa"
                    class="add-button inline-flex items-center justify-center rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                    aria-expanded="false"
                    aria-controls="formTambahKa"
                    onclick="toggleFormTambahKA()">
                    + Tambah KA
                </button>

                <div class="add-box mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4" id="formTambahKa" hidden>
                    <div class="add-heading mb-3 text-sm font-bold text-slate-800">Tambah KA Manual</div>

                    <div class="add-grid grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-10">
                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label for="manualNomor">NOMOR KA</label>
                            <input
                                id="manualNomor"
                                type="text"
                                class="small-input w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800"
                                placeholder="Nomor KA">
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label for="manualNama">NAMA KA</label>
                            <input
                                id="manualNama"
                                type="text"
                                class="small-input w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800"
                                placeholder="Nama KA">
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label for="manualRelasi">RELASI</label>
                            <input
                                id="manualRelasi"
                                type="text"
                                class="small-input w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800"
                                placeholder="Contoh: YK - SBI">
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label for="manualAktual">JAM AKTUAL</label>
                            <input
                                id="manualAktual"
                                type="time"
                                class="small-input w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800">
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label>S21</label>
                            <div class="mini-checks">
                                <button
                                    type="button"
                                    class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                    data-group="manual21"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>

                                <button
                                    type="button"
                                    class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                    data-group="manual21"
                                    data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label>GENTA</label>
                            <div class="mini-checks">
                                <button
                                    type="button"
                                    class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                    data-group="manualGenta"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>

                                <button
                                    type="button"
                                    class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                    data-group="manualGenta"
                                    data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label>PINTU PERLINTASAN</label>
                            <div class="mini-checks">
                                <button
                                    type="button"
                                    class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                    data-group="manualPintu"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>

                                <button
                                    type="button"
                                    class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                    data-group="manualPintu"
                                    data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label>S35</label>
                            <div class="mini-checks">
                                <button
                                    type="button"
                                    class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                    data-group="manual35"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>

                                <button
                                    type="button"
                                    class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                    data-group="manual35"
                                    data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label for="manualKeterangan">KETERANGAN</label>
                            <input
                                id="manualKeterangan"
                                type="text"
                                class="small-input w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800"
                                placeholder="Keterangan">
                        </div>

                        <div class="add-actions flex flex-nowrap items-center gap-1 whitespace-nowrap">
                            <button
                                type="button"
                                class="add-button inline-flex items-center justify-center rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                                onclick="tambahKAManual()">
                                Simpan KA
                            </button>

                            <button
                                type="button"
                                class="action-btn btn-outline inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                                onclick="toggleFormTambahKA(false)">
                                Batal
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- AKSI REALISASI --}}
            <div class="bottom-row print:hidden mt-4 flex flex-col gap-3 border-t border-slate-200 pt-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="digital-note text-xs text-slate-500">
                    Formulir realisasi digital untuk pencatatan dinas PJL.

                    <div
                        id="saveStatus"
                        class="save-status idle mt-1 text-xs font-medium text-slate-600"
                        role="status"
                        aria-live="polite">
                        <span class="save-dot"></span>
                        <span id="saveStatusText">Perubahan hanya tersimpan setelah tombol Simpan Semua ditekan.</span>
                    </div>
                </div>

                <div class="actions flex flex-wrap items-center gap-2">
                    <button
                        type="button"
                        class="action-btn btn-outline inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                        onclick="cetakDokumen()">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="action-icon h-4 w-4 shrink-0 fill-none stroke-current stroke-2">
                            <path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v7H7z" />
                            <path d="M17 11h.01" />
                        </svg>
                        <span>Cetak Dokumen JPL</span>
                    </button>

                    <button
                        type="button"
                        id="simpanBtn"
                        class="action-btn btn-dark inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 bg-slate-800 text-white hover:bg-slate-900"
                        onclick="simpanRealisasi()">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="action-icon h-4 w-4 shrink-0 fill-none stroke-current stroke-2">
                            <path d="M5 3h12l4 4v14H3V3h2z" />
                            <path d="M7 3v6h10V3M7 21v-8h10v8" />
                        </svg>
                        <span data-save-label>Simpan Semua</span>
                    </button>

                    <a
                        id="selesaiLink"
                        href="{{ url('/user/serah-terima-dinas') }}"
                        class="action-btn btn-orange inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 bg-orange-600 text-white hover:bg-orange-700">
                        Selesai &amp; Generate Laporan Serah Terima
                    </a>
                </div>
            </div>
        </section>

        {{-- CATATAN DINAS --}}
        <section class="page-card print:hidden cd-quick rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" id="catatanDinasQuick">
            <div class="cd-quick-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="section-title mb-3 text-base font-bold text-slate-800">Catatan Dinas</div>
                    <p class="cd-quick-desc mt-1 text-sm text-slate-500">
                        Catat kejadian penting selama pelaksanaan dinas.
                    </p>
                </div>

                <div class="cd-quick-actions flex flex-wrap items-center gap-2">
                    <a href="{{ route('user.catatan-dinas') }}" class="cd-quick-link text-sm font-semibold text-blue-700 underline-offset-2 hover:underline">
                        Lihat semua catatan ?
                    </a>

                    <button
                        type="button"
                        class="add-button cd-open-btn inline-flex items-center justify-center rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50"
                        onclick="openCatatanModal()"
                        @disabled(!($dinasAktif ?? null))>
                        + Tambah Catatan
                    </button>
                </div>
            </div>

            @if (session('success'))
                <div class="cd-quick-alert success mt-3 rounded-lg border p-3 text-sm" role="status">
                    {{ session('success') }}
                </div>
            @endif

            @if (!($dinasAktif ?? null))
                <div class="cd-quick-alert error mt-3 rounded-lg border p-3 text-sm">
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
                class="cdm-overlay fixed inset-0 z-[100] hidden items-center justify-center overflow-y-auto bg-slate-950/50 p-4"
                id="catatanModal"
                aria-hidden="true"
                data-open-on-load="{{ $errors->any() ? '1' : '0' }}">

                <div
                    class="cdm-modal w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="catatanModalTitle">

                    <div class="cdm-header mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                        <h2 class="cdm-title text-lg font-bold text-slate-800" id="catatanModalTitle">
                            Tambah Catatan Dinas
                        </h2>

                        <button
                            type="button"
                            class="cdm-close rounded-lg px-3 py-1 text-2xl leading-none text-slate-500 hover:bg-slate-100"
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

                        <div class="cdm-group mb-4 flex flex-col gap-1">
                            <label class="cdm-label text-sm font-semibold text-slate-700" for="cdmTanggal">Tanggal</label>
                            <input
                                type="date"
                                class="cdm-input w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                id="cdmTanggal"
                                name="tanggal"
                                value="{{ $tanggalCatatan }}"
                                readonly
                                required>
                        </div>

                        <div class="cdm-group mb-4 flex flex-col gap-1">
                            <label class="cdm-label text-sm font-semibold text-slate-700" for="cdmPjl">PJL</label>
                            <input
                                type="text"
                                class="cdm-input w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                id="cdmPjl"
                                value="{{ auth()->user()->name ?? '' }}"
                                readonly>
                        </div>

                        <div class="cdm-group mb-4 flex flex-col gap-1">
                            <label class="cdm-label text-sm font-semibold text-slate-700" for="cdmShift">Shift</label>
                            <select
                                class="cdm-input cdm-select w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                id="cdmShift"
                                name="shift_id"
                                required>
                                <option value="{{ $dinasAktif->shift_id }}" selected>
                                    {{ $dinasAktif->shift?->nama_shift ?? '-' }}
                                    — {{ $tanggalCatatan }}
                                </option>
                            </select>
                        </div>

                        <div class="cdm-group mb-4 flex flex-col gap-1">
                            <label class="cdm-label text-sm font-semibold text-slate-700" for="cdmCatatan">Catatan</label>
                            <textarea
                                class="cdm-input cdm-textarea w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 min-h-32 resize-y"
                                id="cdmCatatan"
                                name="catatan"
                                maxlength="5000"
                                required
                                placeholder="Tuliskan catatan selama pelaksanaan dinas...">{{ old('catatan') }}</textarea>

                            <div class="cdm-help text-xs text-slate-500" id="cdmCounter">
                                Maksimal 5.000 karakter.
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="cdm-error mb-3 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700" role="alert">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="cdm-footer flex flex-wrap justify-end gap-2 border-t border-slate-200 pt-4">
                            <button
                                type="button"
                                class="cdm-btn cdm-cancel rounded-lg px-4 py-2 text-sm font-semibold border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                                onclick="closeCatatanModal()">
                                Batal
                            </button>

                            <button type="submit" class="cdm-btn cdm-save rounded-lg px-4 py-2 text-sm font-semibold bg-blue-700 text-white hover:bg-blue-800">
                                Simpan Catatan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </main>
@endsection

@push('scripts')
    @include('user.realisasi-ka.scripts.config')

    {{-- Helper dimuat sinkron sebelum partial yang menggunakannya. --}}
    <script src="{{ asset('js/user/realisasi-ka-time-window.js') }}"></script>
    <script src="{{ asset('js/user/realisasi-ka-interactions.js') }}"></script>

    <script>@include('user.realisasi-ka.scripts.01-data-utilitas-filter')</script>
    <script>@include('user.realisasi-ka.scripts.02-status-awas')</script>
    <script>@include('user.realisasi-ka.scripts.03-tabel-dan-state')</script>
    <script>@include('user.realisasi-ka.scripts.04-interaksi-ringkasan')</script>
    <script>@include('user.realisasi-ka.scripts.05-penyimpanan')</script>
    <script>@include('user.realisasi-ka.scripts.06-cetak-dan-ka-manual')</script>
    <script>@include('user.realisasi-ka.scripts.07-catatan-dinas')</script>
    <script>@include('user.realisasi-ka.scripts.08-inisialisasi')</script>
@endpush
