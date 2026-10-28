 @extends('layouts.user')

@section('title', 'Realisasi KA - Sistem PJL')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user/realisasi-ka.css') }}">
@endpush

@section('content')
    <div class="w-full min-w-0 px-2 py-6 sm:px-5 lg:px-8 lg:py-7">
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
                hidden
            ></div>

            {{-- RINGKASAN REALISASI --}}
            <div
                class="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-4"
                role="group"
                aria-label="Ringkasan realisasi shift"
                title="Progress dihitung dari KA yang sudah berstatus JALAN atau BATAL berdasarkan KA yang tampil pada tabel.">

                <div class="summary-chips">
                    <div class="summary-chip">
                        <span class="chip-label">Total</span>
                        <span id="totalKA" class="chip-number">0</span>
                    </div>

                    <div class="summary-chip">
                        <span class="chip-dot dot-green"></span>
                        <span class="chip-label">Jalan</span>
                        <span id="jalanKA" class="chip-number green">0</span>
                    </div>

                    <div class="summary-chip">
                        <span class="chip-dot dot-yellow"></span>
                        <span class="chip-label">Terlambat</span>
                        <span id="telatKA" class="chip-number yellow">0</span>
                    </div>

                    <div class="summary-chip">
                        <span class="chip-dot dot-blue"></span>
                        <span class="chip-label">Lebih cepat</span>
                        <span id="cepatKA" class="chip-number blue">0</span>
                    </div>

                    <div class="summary-chip">
                        <span class="chip-dot dot-red"></span>
                        <span class="chip-label">Batal</span>
                        <span id="batalKA" class="chip-number red">0</span>
                    </div>

                    <div class="summary-chip">
                        <span class="chip-dot dot-gray"></span>
                        <span class="chip-label">Belum diproses</span>
                        <span id="belumKA" class="chip-number">0</span>
                    </div>
                </div>

                <div class="summary-progress">
                    <div class="progress-bar">
                        <div id="progressGreen" class="progress-green" style="width: 0%"></div>
                        <div id="progressYellow" class="progress-yellow" style="width: 0%"></div>
                        <div id="progressBlue" class="progress-blue" style="width: 0%"></div>
                        <div id="progressRed" class="progress-red" style="width: 0%"></div>
                        <div id="progressGray" class="progress-gray" style="width: 100%"></div>
                    </div>

                    <span class="progress-label">Progres</span>
                    <span id="progressText" class="progress-text">0%</span>
                </div>
            </div>

            {{-- KETERANGAN WARNA --}}
            <div class="mb-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-slate-600" role="group" aria-label="Keterangan warna">
                <div class="legend-title">Keterangan Warna</div>

                <div class="legend-list">
                    <div class="legend-item">
                        <span class="legend-swatch sw-green"></span>
                        <span class="legend-text">Hijau: tepat waktu</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-swatch sw-yellow"></span>
                        <span class="legend-text">
                            Kuning: terlambat melewati batas peringatan yang dikonfigurasi
                        </span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-swatch sw-blue"></span>
                        <span class="legend-text">Biru: lebih cepat dari 2 menit</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-swatch sw-red"></span>
                        <span class="legend-text">Merah: KA batal</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-swatch sw-gray"></span>
                        <span class="legend-text">Abu-abu: belum diproses</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-swatch sw-awas"></span>
                        <span class="legend-text">Merah muda: AWAS 2 ARAH</span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-mark mk-yes">✓</span>
                        <span class="legend-mark mk-no">×</span>
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
            <section class="inline-flex min-h-11 items-center justify-center rounded-lg bg-[#1d2d50] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#263e6a] focus:outline-none focus:ring-2 focus:ring-[#55769a]/40">
                <button
                    type="button"
                    id="toggleTambahKa"
                    class="add-button"
                    aria-expanded="false"
                    aria-controls="formTambahKa"
                    onclick="toggleFormTambahKA()">
                    + Tambah KA
                </button>

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:p-5" id="formTambahKa" hidden>
                    <div class="mb-5 text-base font-bold text-[#1d2d50]">Tambah KA Manual</div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div class="min-w-0">
                            <label for="manualNomor" class="mb-2 block text-sm font-semibold text-slate-700">NOMOR KA</label>
                            <input
                                id="manualNomor"
                                type="text"
                                class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                                placeholder="Nomor KA">
                        </div>

                        <div class="min-w-0">
                            <label for="manualNama" class="mb-2 block text-sm font-semibold text-slate-700">NAMA KA</label>
                            <input
                                id="manualNama"
                                type="text"
                                class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                                placeholder="Nama KA">
                        </div>

                        <div class="min-w-0">
                            <label for="manualHulu" class="mb-2 block text-sm font-semibold text-slate-700  ">HULU</label>
                            <input
                                id="manualHulu"
                                type="text"
                                class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                                placeholder="Hulu">
                        </div>

                        <div class="min-w-0">
                            <label for="manualHilir" class="mb-2 block text-sm font-semibold text-slate-700">HILIR</label>
                            <input
                                id="manualHilir"
                                type="text"
                                class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                                placeholder="Hilir">
                        </div>

                        <div class="min-w-0">
                            <label for="manualRelasi" class="mb-2 block text-sm font-semibold text-slate-700">RELASI</label>
                            <input
                                id="manualRelasi"
                                type="text"
                                class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                                placeholder="Contoh: YK - SBI">
                        </div>

                        <div class="min-w-0">
                            <label for="manualAktual" class="mb-2 block text-sm font-semibold text-slate-700">JAM AKTUAL</label>
                            <input
                                id="manualAktual"
                                type="time"
                                class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20">
                        </div>

                        <div class="min-w-0">
                            <label for="manualSemboyan21" class="mb-2 block text-sm font-semibold text-slate-700">SEMBOYAN 21</label>
                            <div class="mini-checks">
                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manual21"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manual21"
                                    data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <label for="manualGenta" class="mb-2 block text-sm font-semibold text-slate-700">GENTA</label>
                            <div class="mini-checks">
                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manualGenta"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manualGenta"
                                    data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <label for="manualPintu" class="mb-2 block text-sm font-semibold text-slate-700">PINTU PERLINTASAN</label>
                            <div class="mini-checks">
                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manualPintu"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manualPintu"
                                    data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <label for="manualSemboyan35" class="mb-2 block text-sm font-semibold text-slate-700">SEMBOYAN 35</label>
                            <div class="mini-checks">
                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manual35"
                                    data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>

                                <button
                                    type="button"
                                    class="mini-check inactive"
                                    data-group="manual35"
                                    data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <label for="manualKeterangan" class="mb-2 block text-sm font-semibold text-slate-700">KETERANGAN</label>
                            <input
                                id="manualKeterangan"
                                type="text"
                                class="min-h-11 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/20"
                                placeholder="Keterangan">
                        </div>
                    </div>

                    <div class="add-actions">
                        <button
                            type="button"
                            class="add-button"
                            onclick="tambahKAManual()">
                            + Simpan KA Manual
                        </button>

                        <button
                            type="button"
                            class="action-btn btn-outline"
                            onclick="toggleFormTambahKA(false)">
                            Batal
                        </button>
                    </div>
                </div>
            </section>

            {{-- AKSI REALISASI --}}
            <div class="mt-6 flex flex-col gap-4 border-t border-slate-200 pt-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="digital-note">
                    Formulir realisasi digital untuk pencatatan dinas PJL.

                    <div
                        id="saveStatus"
                        class="save-status idle"
                        role="status"
                        aria-live="polite">
                        <span class="save-dot"></span>
                        <span id="saveStatusText">Perubahan belum disimpan</span>
                    </div>
                </div>

                <div class="actions">
                    <button
                        type="button"
                        class="action-btn btn-outline"
                        onclick="cetakDokumen()">
                        ▣ &nbsp; Cetak Dokumen JPL
                    </button>

                    <button
                        type="button"
                        id="simpanBtn"
                        class="action-btn btn-dark"
                        onclick="simpanRealisasi()">
                        ▣ &nbsp; Simpan Semua
                    </button>

                    <a
                        id="selesaiLink"
                        href="{{ url('/user/serah-terima-dinas') }}"
                        class="action-btn btn-orange">
                        Selesai &amp; Generate Laporan Serah Terima
                    </a>
                </div>
            </div>
        </section>

        {{-- CATATAN DINAS --}}
        <section class="mt-6 w-full rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" id="catatanDinasQuick">
            <div class="cd-quick-header">
                <div>
                    <div class="section-title">Catatan Dinas</div>
                    <p class="cd-quick-desc">
                        Catat kejadian penting selama pelaksanaan dinas.
                    </p>
                </div>

                <div class="cd-quick-actions">
                    <a href="{{ route('user.catatan-dinas') }}" class="cd-quick-link">
                        Lihat semua catatan →
                    </a>

                    <button
                        type="button"
                        class="add-button cd-open-btn"
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
                class="cdm-overlay fixed inset-0 z-[1000] items-center justify-center overflow-y-auto bg-slate-900/55 p-3 backdrop-blur-sm sm:p-5"
                id="catatanModal"
                aria-hidden="true"
                data-open-on-load="{{ $errors->any() ? '1' : '0' }}">

                <div
                    class="cdm-modal my-auto max-h-[calc(100vh-2rem)] w-full max-w-[600px] overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl sm:p-8"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="catatanModalTitle">

                    <div class="cdm-header mb-6 flex items-center justify-between gap-3 border-b border-slate-200 pb-5">
                        <h2 class="cdm-title m-0 text-xl font-extrabold text-[#1d2d50] sm:text-2xl" id="catatanModalTitle">
                            Tambah Catatan Dinas
                        </h2>

                        <button
                            type="button"
                            class="cdm-close inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-2xl leading-none text-slate-500 transition hover:bg-slate-100"
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

                        <div class="cdm-group mb-5">
                            <label class="cdm-label mb-2 block text-sm font-bold text-slate-800" for="cdmTanggal">Tanggal</label>
                            <input
                                type="date"
                                class="cdm-input block h-[52px] w-full rounded-xl border border-slate-200 bg-white px-4 text-[15px] text-slate-700 outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/15 read-only:bg-slate-50 read-only:text-slate-500"
                                id="cdmTanggal"
                                name="tanggal"
                                value="{{ $tanggalCatatan }}"
                                readonly
                                required>
                        </div>

                        <div class="cdm-group mb-5">
                            <label class="cdm-label mb-2 block text-sm font-bold text-slate-800" for="cdmPjl">PJL</label>
                            <input
                                type="text"
                                class="cdm-input block h-[52px] w-full rounded-xl border border-slate-200 bg-white px-4 text-[15px] text-slate-700 outline-none transition focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/15 read-only:bg-slate-50 read-only:text-slate-500"
                                id="cdmPjl"
                                value="{{ auth()->user()->name ?? '' }}"
                                readonly>
                        </div>

                        <div class="cdm-group mb-5">
                            <label class="cdm-label mb-2 block text-sm font-bold text-slate-800" for="cdmShift">Shift</label>
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

                        <div class="cdm-group mb-5">
                            <label class="cdm-label mb-2 block text-sm font-bold text-slate-800" for="cdmCatatan">Catatan</label>
                            <textarea
                                class="cdm-textarea block min-h-[150px] w-full resize-y rounded-xl border border-slate-200 bg-white px-4 py-3 text-[15px] leading-relaxed text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-[#55769a] focus:ring-2 focus:ring-[#55769a]/15"
                                id="cdmCatatan"
                                name="catatan"
                                maxlength="5000"
                                required
                                placeholder="Tuliskan catatan selama pelaksanaan dinas...">{{ old('catatan') }}</textarea>

                            <div class="cdm-help mt-2 text-xs text-slate-500" id="cdmCounter">
                                Maksimal 5.000 karakter.
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="cdm-error mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold leading-relaxed text-red-900" role="alert">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="cdm-error mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold leading-relaxed text-red-900">
                            <button
                                type="button"
                                class="cdm-btn cdm-cancel min-h-[48px] min-w-[88px] rounded-xl border border-slate-200 bg-white px-6 text-sm font-bold text-slate-700 transition hover:bg-slate-50 max-sm:w-full"
                                onclick="closeCatatanModal()">
                                Batal
                            </button>

                            <button type="submit" class="cdm-btn cdm-save min-h-[48px] min-w-[88px] rounded-xl border border-[#1d2d50] bg-[#1d2d50] px-6 text-sm font-bold text-white transition hover:bg-[#263e6a] max-sm:w-full">
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
            shiftStart: @json($dinasAktif?->shift?->jam_mulai),
            shiftEnd: @json($dinasAktif?->shift?->jam_selesai),
            timeZone: 'Asia/Jakarta',
            locale: 'id-ID',

            settings: {
                toleransiKeterlambatanMenit: {{ (int) ($realisasiKaSetting->toleransi_keterlambatan_menit ?? 5) }},
                peringatanKeterlambatanTinggiMenit: {{ (int) ($realisasiKaSetting->peringatan_keterlambatan_tinggi_menit ?? 10) }},
                jarakMinimumAntarKaMenit: {{ (int) ($realisasiKaSetting->jarak_minimum_antar_ka_menit ?? 0) }},
                batasPenutupanPalangMenit: {{ (int) ($realisasiKaSetting->batas_penutupan_palang_menit ?? 0) }},

                warningKeterlambatanAktif: @json((bool) ($realisasiKaSetting->warning_keterlambatan_aktif ?? false)),
                warningKeterlambatanTinggiAktif: @json((bool) ($realisasiKaSetting->warning_keterlambatan_tinggi_aktif ?? false)),
                warningAntarKaAktif: @json((bool) ($realisasiKaSetting->warning_antar_ka_aktif ?? false)),
                warningPenutupanPalangAktif: @json((bool) ($realisasiKaSetting->warning_penutupan_palang_aktif ?? false))
            }
        };
    </script>

    {{-- Jam realtime, filter jadwal, tabel, dan autosave --}}
    <script src="{{ asset('js/user/realisasi-ka.js') }}" defer></script>
@endpush