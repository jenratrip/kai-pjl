<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Realisasi KA - Sistem PJL</title>

    <link rel="stylesheet" href="{{ asset('css/user/realisasi-ka.css') }}">
</head>

<body>

    <!-- HEADER -->
    <header class="top-header">
        <div class="header-left">
            <button
                type="button"
                id="hamburgerBtn"
                class="hamburger-btn"
                onclick="toggleSidebar()"
                aria-label="Buka menu"
                aria-expanded="false">
                ☰
            </button>

            <div class="header-brand">
                <div class="header-logo">
                    <img src="{{ asset('images/logo-kai.jpg') }}" alt="Logo KAI">
                </div>

                <div>
                    <div class="header-title">Sistem PJL</div>
                    <div class="header-subtitle">Realisasi KA</div>
                </div>
            </div>
        </div>

        <div class="header-right">
            <div class="header-page">REALISASI KA</div>
            <div class="header-avatar">
                {{ strtoupper(substr(auth()->user()->name ?? 'P', 0, 1)) }}
            </div>
        </div>
    </header>

    <!-- OVERLAY SIDEBAR -->
    <div
        class="sidebar-overlay"
        id="sidebarOverlay"
        onclick="closeSidebar()">
    </div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-title">KAI OPERASIONAL</div>
            <div class="sidebar-subtitle">SISTEM DIGITAL PJL</div>
        </div>

        <nav class="sidebar-menu">
            <a href="{{ url('/user/dashboard') }}" class="menu-item">
                <span class="menu-icon">⌂</span>
                <span>Dashboard</span>
            </a>

            <a href="{{ url('/user/realisasi-ka') }}" class="menu-item active">
                <span class="menu-icon">▣</span>
                <span>Realisasi KA</span>
            </a>

            <a href="{{  route('user.serah-terima-dinas') }}" class="menu-item">
                <span class="menu-icon">⇄</span>
                <span>Serah Terima Dinas</span>
            </a>

            <a href="{{ url('/user/catatan-dinas') }}" class="menu-item">
                <span class="menu-icon">◧</span>
                <span>Catatan Dinas</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="profile-box">
                <div class="profile-label">PENGGUNA LOGIN</div>

                <div class="profile-name">
                    {{ auth()->user()->name ?? 'Pengguna PJL' }}
                </div>

                <div class="profile-nip">
                    @if (auth()->user() && auth()->user()->nip)
                        NIP: {{ auth()->user()->nip }}
                    @else
                        Akun PJL
                    @endif
                </div>

                <div class="profile-shift">
                    @if ($dinasAktif ?? false)
                        Dinas aktif ·
                        {{ \Carbon\Carbon::parse($dinasAktif->tanggal_dinas)->format('d-m-Y') }}
                    @else
                        Tidak ada dinas aktif
                    @endif
                </div>
            </div>

            <form action="{{ route('logout') }}" method="POST" class="logout-form">
                @csrf
                <button type="submit" class="logout-btn">
                    <span>↪</span>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="main">
        <section class="page-card">

            <div class="company">
                <div class="company-name">PT KERETA API INDONESIA (PERSERO)</div>
                <div class="company-address">DIREKTORAT KESELAMATAN DAN KEAMANAN</div>
            </div>

            <h1 class="page-title">REALISASI KA YANG LEWAT JPL</h1>

            <div class="page-subtitle">
                Buku Catatan Harian Penjagaan Jalan Perlintasan
            </div>

            <!-- INFORMASI DINAS -->
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

            <!-- DAFTAR KA -->
            <div class="section-title">Daftar KA Terjadwal</div>

            <div class="section-description">
                Jadwal diambil dari database untuk JPL yang sedang dijaga.
                KA terjadwal hanya ditampilkan dalam rentang ±1 jam dari waktu WIB sekarang.
                KA manual tanpa jadwal selalu ditampilkan.
            </div>

            <div class="time-window-info">
                <span>
                    Filter jadwal: <strong>±1 jam dari waktu sekarang</strong>.
                    KA manual tetap ditampilkan.
                </span>

                <span id="windowRange">Memuat waktu...</span>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NOMOR KA</th>
                            <th>NAMA KA</th>
                            <th>JAM JADWAL</th>
                            <th>JAM AKTUAL</th>
                            <th>STATUS</th>
                            <th>SEMBOYAN 2.1</th>
                            <th>GENTA</th>
                            <th>PINTU PERLINTASAN</th>
                            <th>SEMBOYAN 3.5</th>
                            <th>KETERANGAN</th>
                        </tr>
                    </thead>

                    <tbody id="kaTableBody"></tbody>
                </table>
            </div>

            <!-- TAMBAH KA MANUAL -->
            <section class="add-section">
                <div class="add-box">
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
                            <label for="manualAktual">JAM AKTUAL</label>
                            <input
                                id="manualAktual"
                                type="time"
                                class="small-input">
                        </div>

                        <div class="small-field">
                            <label>SEMBOYAN 2.1</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inactive"
                                    data-group="manual21" data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inactive"
                                    data-group="manual21" data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field">
                            <label>GENTA</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inactive"
                                    data-group="manualGenta" data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inactive"
                                    data-group="manualGenta" data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field">
                            <label>PINTU PERLINTASAN</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inactive"
                                    data-group="manualPintu" data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inactive"
                                    data-group="manualPintu" data-value="no"
                                    onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field">
                            <label>SEMBOYAN 3.5</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inactive"
                                    data-group="manual35" data-value="yes"
                                    onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inactive"
                                    data-group="manual35" data-value="no"
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

                    <button type="button" class="add-button" onclick="tambahKAManual()">
                        + Tambah KA Manual
                    </button>
                </div>
            </section>

            <!-- RINGKASAN -->
            <section class="summary-section">
                <div class="section-title">Ringkasan Realisasi Shift</div>

                <div class="section-description">
                    Ringkasan otomatis berdasarkan KA yang sedang tampil pada tabel.
                </div>

                <div class="summary-box">
                    <div class="summary-cards">
                        <div class="summary-card">
                            <div class="summary-label">TOTAL KA</div>
                            <div id="totalKA" class="summary-number">0</div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-label">KA SUDAH LEWAT (JALAN)</div>
                            <div id="jalanKA" class="summary-number green">0</div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-label">KA BATAL</div>
                            <div id="batalKA" class="summary-number red">0</div>
                        </div>

                        <div class="summary-card">
                            <div class="summary-label">KA BELUM DIPROSES</div>
                            <div id="belumKA" class="summary-number">0</div>
                        </div>
                    </div>

                    <div class="progress-title">
                        <span>Progress realisasi</span>
                        <span id="progressText">0%</span>
                    </div>

                    <div class="progress-bar">
                        <div id="progressGreen" class="progress-green" style="width: 0%"></div>
                        <div id="progressRed" class="progress-red" style="width: 0%"></div>
                        <div id="progressGray" class="progress-gray" style="width: 100%"></div>
                    </div>

                    <div class="progress-description">
                        Progress dihitung dari jumlah KA yang sudah berstatus JALAN atau BATAL.
                    </div>
                </div>
            </section>

            <!-- AKSI -->
            <div class="bottom-row">
                <div class="digital-note">
                    Formulir realisasi digital untuk pencatatan dinas PJL.
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
                        ▣ &nbsp; Simpan Realisasi
                    </button>

                    <a
                        href="{{ url('/user/serah-terima-dinas') }}"
                        class="action-btn btn-orange">
                        Selesai &amp; Generate Laporan Serah Terima
                    </a>
                </div>
            </div>

        </section>
    </main>

    <script>
        window.realisasiKaConfig = {
            data: @json($dataKA ?? []),
            storeUrl: @json(route('user.realisasi-ka.store'))
        };
    </script>

    <script src="{{ asset('js/user/realisasi-ka.js') }}"></script>
</body>

</html>