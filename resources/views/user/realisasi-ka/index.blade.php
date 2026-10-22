@extends('layouts.user-operasional')

@section('title', 'Realisasi KA - Sistem PJL')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/user/realisasi-ka.css') }}">
@endpush

@section('content')
    <main class="main">
        <section class="page-card">

            {{-- INFORMASI DINAS --}}
            <div class="filter-grid">
                <div>
                    <label for="tanggal" class="field-label">HARI / TANGGAL</label>
                    <input
                        type="text"
                        id="tanggal"
                        class="field-input"
                        value="{{ ($dinasAktif ?? null)
                            ? \Carbon\Carbon::parse($dinasAktif->tanggal_dinas)->locale('id')->translatedFormat('l, d F Y')
                            : \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y') }}"
                        readonly>
                </div>

                <div>
                    <label for="nomorJpl" class="field-label">NOMOR JPL</label>
                    <input
                        id="nomorJpl"
                        type="text"
                        class="field-input"
                        value="{{ ($dinasAktif ?? null) ? ($dinasAktif->jpl?->kode_jpl ?? '-') : '-' }}"
                        readonly>
                </div>

                <div>
                    <label for="stasiunRegional" class="field-label">STASIUN / REGIONAL</label>
                    <input
                        id="stasiunRegional"
                        type="text"
                        class="field-input"
                        value="{{ ($dinasAktif ?? null) ? ($dinasAktif->jpl?->nama_jpl ?? '-') : '-' }}"
                        readonly>
                </div>

                <div>
                    <label for="shift" class="field-label">SHIFT</label>
                    <input
                        id="shift"
                        type="text"
                        class="field-input"
                        value="{{ ($dinasAktif ?? null) ? ($dinasAktif->shift?->nama_shift ?? '-') : '-' }}"
                        readonly>
                </div>
            </div>

            {{-- DAFTAR KA --}}
            <div class="section-title">Daftar KA Terjadwal</div>

            {{-- JAM SEKARANG DAN RENTANG FILTER --}}
            <div class="time-window-info">
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
                class="awas-banner"
                role="alert"
                aria-live="assertive"
                hidden>
            </div>

            {{-- RINGKASAN REALISASI --}}
            <div
                class="summary-compact"
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

            {{-- KETERANGAN WARNA SEDERHANA --}}
            <div class="color-legend" role="group" aria-label="Keterangan warna">
                <div class="legend-title">Keterangan Warna</div>

                <div class="legend-list">
                    <div class="legend-item">
                        <span class="legend-swatch sw-green"></span>
                        <span class="legend-text">
                            Hijau: tepat waktu
                        </span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-swatch sw-yellow"></span>
                        <span class="legend-text">
                            Kuning: terlambat lebih dari 5 menit
                        </span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-swatch sw-blue"></span>
                        <span class="legend-text">
                            Biru: lebih cepat dari 2 menit
                        </span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-swatch sw-red"></span>
                        <span class="legend-text">
                            Merah: KA batal
                        </span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-swatch sw-gray"></span>
                        <span class="legend-text">
                            Abu-abu: belum diproses
                        </span>
                    </div>

                    <div class="legend-item">
                        <span class="legend-swatch sw-awas"></span>
                        <span class="legend-text">
                            Merah muda: AWAS 2 ARAH
                        </span>
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
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NOMOR KA</th>
                            <th>NAMA KA</th>
                            <th>HULU</th>
                            <th>HILIR</th>
                            <th>RELASI</th>
                            <th>JAM JADWAL</th>
                            <th>JAM AKTUAL</th>
                            <th>STATUS</th>
                            <th>SEMBOYAN 21</th>
                            <th>GENTA</th>
                            <th>PINTU PERLINTASAN</th>
                            <th>SEMBOYAN 35</th>
                            <th>KETERANGAN</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody id="kaTableBody"></tbody>
                </table>
            </div>

            {{-- TAMBAH KA MANUAL --}}
            <section class="add-section">
                <button
                    type="button"
                    id="toggleTambahKa"
                    class="add-button"
                    aria-expanded="false"
                    aria-controls="formTambahKa"
                    onclick="toggleFormTambahKA()">
                    + Tambah KA
                </button>

                <div class="add-box" id="formTambahKa" hidden>
                    <div class="add-heading">Tambah KA Manual</div>

                    <div class="add-grid">
                        <div class="small-field">
                            <label for="manualNomor">NOMOR KA</label>
                            <input
                                id="manualNomor"
                                type="text"
                                class="small-input"
                                placeholder="Nomor KA">
                        </div>

                        <div class="small-field">
                            <label for="manualNama">NAMA KA</label>
                            <input
                                id="manualNama"
                                type="text"
                                class="small-input"
                                placeholder="Nama KA">
                        </div>

                        <div class="small-field">
                            <label for="manualHulu">HULU</label>
                            <input
                                id="manualHulu"
                                type="text"
                                class="small-input"
                                placeholder="Hulu">
                        </div>

                        <div class="small-field">
                            <label for="manualHilir">HILIR</label>
                            <input
                                id="manualHilir"
                                type="text"
                                class="small-input"
                                placeholder="Hilir">
                        </div>

                        <div class="small-field">
                            <label for="manualRelasi">RELASI</label>
                            <input
                                id="manualRelasi"
                                type="text"
                                class="small-input"
                                placeholder="Contoh: YK - SBI">
                        </div>

                        <div class="small-field">
                            <label for="manualAktual">JAM AKTUAL</label>
                            <input
                                id="manualAktual"
                                type="time"
                                class="small-input">
                        </div>

                        <div class="small-field">
                            <label>SEMBOYAN 21</label>
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

                        <div class="small-field">
                            <label>GENTA</label>
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

                        <div class="small-field">
                            <label>PINTU PERLINTASAN</label>
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

                        <div class="small-field">
                            <label>SEMBOYAN 35</label>
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

                        <div class="small-field">
                            <label for="manualKeterangan">KETERANGAN</label>
                            <input
                                id="manualKeterangan"
                                type="text"
                                class="small-input"
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
            <div class="bottom-row">
                <div class="digital-note">
                    Formulir realisasi digital untuk pencatatan dinas PJL.

                    <div
                        id="saveStatus"
                        class="save-status idle"
                        role="status"
                        aria-live="polite">
                        <span class="save-dot"></span>
                        <span id="saveStatusText">Penyimpanan otomatis aktif</span>
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
        <section class="page-card cd-quick" id="catatanDinasQuick">
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
                class="cdm-overlay"
                id="catatanModal"
                aria-hidden="true"
                data-open-on-load="{{ $errors->any() ? '1' : '0' }}">

                <div
                    class="cdm-modal"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="catatanModalTitle">

                    <div class="cdm-header">
                        <h2 class="cdm-title" id="catatanModalTitle">
                            Tambah Catatan Dinas
                        </h2>

                        <button
                            type="button"
                            class="cdm-close"
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

                        <div class="cdm-group">
                            <label class="cdm-label" for="cdmTanggal">Tanggal</label>
                            <input
                                type="date"
                                class="cdm-input"
                                id="cdmTanggal"
                                name="tanggal"
                                value="{{ $tanggalCatatan }}"
                                readonly
                                required>
                        </div>

                        <div class="cdm-group">
                            <label class="cdm-label" for="cdmPjl">PJL</label>
                            <input
                                type="text"
                                class="cdm-input"
                                id="cdmPjl"
                                value="{{ auth()->user()->name ?? '' }}"
                                readonly>
                        </div>

                        <div class="cdm-group">
                            <label class="cdm-label" for="cdmShift">Shift</label>
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

                        <div class="cdm-group">
                            <label class="cdm-label" for="cdmCatatan">Catatan</label>
                            <textarea
                                class="cdm-input cdm-textarea"
                                id="cdmCatatan"
                                name="catatan"
                                maxlength="5000"
                                required
                                placeholder="Tuliskan catatan selama pelaksanaan dinas...">{{ old('catatan') }}</textarea>

                            <div class="cdm-help" id="cdmCounter">
                                Maksimal 5.000 karakter.
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="cdm-error" role="alert">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="cdm-footer">
                            <button
                                type="button"
                                class="cdm-btn cdm-cancel"
                                onclick="closeCatatanModal()">
                                Batal
                            </button>

                            <button type="submit" class="cdm-btn cdm-save">
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
    <script>
        window.realisasiKaConfig = {
            data: @json($dataKA ?? []),
            storeUrl: @json(route('user.realisasi-ka.store')),
            shiftStart: @json($dinasAktif?->shift?->jam_mulai),
            shiftEnd: @json($dinasAktif?->shift?->jam_selesai),
            timeZone: 'Asia/Jakarta',
            locale: 'id-ID'
        };
    </script>

    {{-- Jam realtime dan filter jadwal ditangani oleh satu file JavaScript --}}
    <script src="{{ asset('js/user/realisasi-ka.js') }}" defer></script>
@endpush