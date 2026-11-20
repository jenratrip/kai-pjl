@extends('layouts.user')

@section('title', 'Realisasi KA - Sistem PJL')

@push('styles')
    <style>
        /* =========================
           TABEL REALISASI KA
        ========================= */
        .table-wrapper {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow: hidden;
        }

        .realisasi-table {
            width: 100%;
            max-width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border-spacing: 0;
        }

        .realisasi-table th,
        .realisasi-table td {
            min-width: 0;
            box-sizing: border-box;
            vertical-align: middle;
            overflow-wrap: anywhere;
            word-break: normal;
        }

        .realisasi-table th {
            padding: 8px 4px;
            font-size: 10px;
            line-height: 1.3;
            font-weight: 700;
            text-align: center;
            white-space: normal;
        }

        .realisasi-table td {
            padding: 7px 4px;
            font-size: 11px;
            line-height: 1.35;
        }

        .realisasi-table input,
        .realisasi-table select,
        .realisasi-table textarea {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            font-size: 11px;
        }

        .realisasi-table button {
            max-width: 100%;
            box-sizing: border-box;
        }

        .realisasi-table .note-input {
            width: 100%;
            min-width: 0;
            padding: 5px;
            font-size: 10px;
        }

        .realisasi-table .row-save-btn {
            max-width: 100%;
            padding: 5px 6px;
            font-size: 10px;
            white-space: normal;
        }

        .realisasi-table .check-btn {
            min-width: 26px;
            min-height: 26px;
            padding: 3px;
            font-size: 10px;
        }

        .realisasi-table .actual-time,
        .realisasi-table .status-badge {
            max-width: 100%;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        /* =========================
           HEADER CETAK
        ========================= */
        .print-document-header-row {
            display: none;
        }

        .print-document-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: .4rem .6rem;
            color: #1d2d50;
            text-align: left;
        }

        .print-document-brand,
        .print-document-identity {
            display: flex;
            align-items: center;
            gap: .65rem;
        }

        .print-document-brand img {
            width: 2.6rem;
            height: 2.6rem;
            object-fit: contain;
        }

        .print-document-brand div,
        .print-document-identity {
            display: flex;
            flex-direction: column;
            gap: .12rem;
        }

        .print-document-brand strong,
        .print-document-identity strong {
            font-size: .75rem;
        }

        .print-document-brand span,
        .print-document-identity span {
            font-size: .62rem;
        }

        /* =========================
           RESPONSIVE
        ========================= */
        @media (max-width: 768px) {
            .realisasi-table th {
                padding: 6px 2px;
                font-size: 9px;
                line-height: 1.2;
            }

            .realisasi-table td {
                padding: 5px 2px;
                font-size: 10px;
            }

            .realisasi-table input,
            .realisasi-table select,
            .realisasi-table textarea {
                font-size: 10px;
            }

            .realisasi-table .check-btn {
                min-width: 23px;
                min-height: 23px;
            }
        }

        /* =========================
           PRINT A4 LANDSCAPE
        ========================= */
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            body {
                background: #fff !important;
            }

            .table-wrapper {
                width: 100% !important;
                max-width: 100% !important;
                overflow: visible !important;
            }

            .realisasi-table {
                width: 100% !important;
                max-width: 100% !important;
                table-layout: fixed !important;
                font-size: 7.5pt;
            }

            .realisasi-table th,
            .realisasi-table td {
                padding: 1mm .8mm !important;
                font-size: 7.5pt !important;
                overflow-wrap: anywhere;
            }

            .realisasi-table thead {
                display: table-header-group;
            }

            .realisasi-table .print-document-header-row {
                display: table-row !important;
            }

            .realisasi-table tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .add-section,
            .bottom-row,
            #catatanDinasQuick,
            #catatanModal {
                display: none !important;
            }

            .realisasi-table th:last-child,
            .realisasi-table td:last-child {
                display: none !important;
            }
        }

        /* =========================
           BARIS AWAS 2 ARAH
        ========================= */
        .realisasi-table tr.awas-row > td {
            background-color: #fff1f2 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .realisasi-table tr.awas-row > td:first-child {
            box-shadow: inset 4px 0 0 #ef5145;
        }

        .realisasi-table tr.awas-row:hover > td {
            background-color: #ffe4e6 !important;
        }

        /* =========================
           RUNNING TEXT PESAN PEMBINAAN
        ========================= */
        .running-text {
            position: relative;
            min-width: 0;
            overflow: hidden;
        }

        .running-text-track {
            display: inline-flex;
            align-items: center;
            gap: 2.5rem;
            padding-left: 100%;
            white-space: nowrap;
            animation: running-text-scroll var(--rt-duration, 30s) linear infinite;
        }

        .running-text:hover .running-text-track,
        .running-text:focus-within .running-text-track {
            animation-play-state: paused;
        }

        @keyframes running-text-scroll {
            from { transform: translateX(0); }
            to { transform: translateX(-100%); }
        }

        @media (prefers-reduced-motion: reduce) {
            .running-text {
                overflow-x: auto;
            }

            .running-text-track {
                padding-left: 0;
                animation: none;
            }
        }
    </style>
@endpush

@section('content')
    <main class="main mx-auto w-full max-w-[1680px] space-y-4 px-3 py-4 sm:px-5">
        <section class="page-card rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">

            @php
                // Hari / tanggal dinas (dipakai di bar filter dan kolom cetak).
                $tanggalTampil = ($dinasAktif ?? null)
                    ? \Carbon\Carbon::parse($dinasAktif->tanggal_dinas)->locale('id')->translatedFormat('l, d F Y')
                    : \Carbon\Carbon::now('Asia/Jakarta')->locale('id')->translatedFormat('l, d F Y');

                // Pesan pembinaan untuk running text (dikirim dari controller).
                $tickerItems = collect($runningTextMessages ?? [])->map(function ($pesan) {
                    $isi = trim((string) preg_replace('/\s+/u', ' ', (string) $pesan->body));

                    return [
                        'subject' => trim((string) $pesan->subject),
                        'body' => \Illuminate\Support\Str::limit($isi, 180),
                        'unread' => $pesan->read_at === null,
                        'url' => \Illuminate\Support\Facades\Route::has('user.coaching-messages.show')
                            ? route('user.coaching-messages.show', $pesan)
                            : null,
                    ];
                })->values();

                $tickerChars = $tickerItems->sum(fn ($item) => mb_strlen($item['subject'] . $item['body']) + 6);
                $tickerDurasi = max(20, (int) round($tickerChars * 0.15) + 15);
            @endphp

            {{-- RUNNING TEXT PESAN PEMBINAAN --}}
            <div class="mb-3 flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 print:hidden" role="region" aria-label="Pesan pembinaan">
                <a
                    href="{{ \Illuminate\Support\Facades\Route::has('user.coaching-messages.index') ? route('user.coaching-messages.index') : '#' }}"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-[#1d2d50] px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-white hover:bg-[#14213d]">
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="h-3.5 w-3.5 fill-none stroke-current stroke-2">
                        <path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1z" />
                        <path d="M15.5 8.5a5 5 0 0 1 0 7" />
                    </svg>
                    Pembinaan
                </a>

                <div class="running-text flex-1 text-sm text-slate-700" style="--rt-duration: {{ $tickerDurasi }}s;">
                    @if ($tickerItems->isEmpty())
                        <span class="text-slate-500">Belum ada pesan pembinaan.</span>
                    @else
                        <div class="running-text-track">
                            @foreach ($tickerItems as $item)
                                <span class="inline-flex items-center gap-2">
                                    @if ($item['unread'])
                                        <span class="inline-block h-2 w-2 shrink-0 rounded-full bg-amber-400" title="Belum dibaca"></span>
                                    @endif

                                    @if ($item['url'])
                                        <a href="{{ $item['url'] }}" class="hover:underline">
                                            <strong>{{ $item['subject'] }}</strong>@if ($item['body'] !== '') — {{ $item['body'] }}@endif
                                        </a>
                                    @else
                                        <span>
                                            <strong>{{ $item['subject'] }}</strong>@if ($item['body'] !== '') — {{ $item['body'] }}@endif
                                        </span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- INFORMASI DINAS: tidak tampil di layar, tetap ada untuk cetak dan script --}}
            <div class="filter-grid mb-3 hidden grid-cols-1 gap-3 print:grid sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="tanggal" class="field-label mb-1 block text-xs font-semibold tracking-wide text-slate-600">HARI / TANGGAL</label>
                    <input
                        type="text"
                        id="tanggal"
                        class="field-input w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-800"
                        value="{{ $tanggalTampil }}"
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

            {{-- FILTER JADWAL + HARI/TANGGAL + JAM SEKARANG --}}
            <div class="time-window-info mb-3 flex flex-col gap-2 rounded-xl bg-blue-50 p-3 text-sm text-blue-900 md:flex-row md:items-center md:justify-between">
                <span>
                    Filter jadwal: <strong>60 menit ke depan dari waktu sekarang</strong>.
                    KA manual tetap ditampilkan.
                </span>

                <div class="waktu-info flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                    <span class="font-semibold">{{ $tanggalTampil }}</span>

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

                <div class="summary-progress flex items-center gap-2 text-xs text-slate-600">
                    <div class="progress-bar relative flex min-w-0 flex-1 overflow-hidden rounded-full bg-slate-200" style="height: 18px;">
                        <div id="progressGreen" class="progress-green h-full bg-green-500" style="width: 0%"></div>
                        <div id="progressYellow" class="progress-yellow h-full bg-amber-400" style="width: 0%"></div>
                        <div id="progressBlue" class="progress-blue h-full bg-blue-500" style="width: 0%"></div>
                        <div id="progressRed" class="progress-red h-full bg-rose-500" style="width: 0%"></div>
                        <div id="progressGray" class="progress-gray h-full bg-slate-300" style="width: 100%"></div>

                        {{-- Tulisan progres berada di dalam bar --}}
                        <div class="pointer-events-none absolute inset-0 flex items-center justify-center gap-1 text-[10px] font-bold text-slate-900" style="text-shadow: 0 0 3px #fff, 0 0 3px #fff;">
                            <span class="progress-label">Progres</span>
                            <span id="progressText" class="progress-text">0%</span>
                        </div>
                    </div>

                    {{-- TOMBOL KETERANGAN WARNA (ikon mata buka/tutup) --}}
                    <button
                        type="button"
                        id="legendToggleBtn"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 print:hidden"
                        aria-haspopup="dialog"
                        aria-controls="legendModal"
                        aria-expanded="false"
                        aria-label="Keterangan warna"
                        title="Keterangan warna"
                        onclick="toggleLegendModal()">
                        {{-- Mata terbuka: keterangan tersembunyi, klik untuk membuka --}}
                        <svg id="legendIconOpen" aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current stroke-2">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>

                        {{-- Mata tercoret: keterangan sedang terbuka, klik untuk menutup --}}
                        <svg id="legendIconClosed" aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="hidden h-4 w-4 fill-none stroke-current stroke-2">
                            <path d="M17.94 17.94A10.9 10.9 0 0 1 12 19c-6.5 0-10-7-10-7a18.5 18.5 0 0 1 4.06-5.06M9.9 4.24A9.1 9.1 0 0 1 12 5c6.5 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24" />
                            <path d="M1 1l22 22" />
                        </svg>
                    </button>

                    {{-- TOMBOL RINGKASAN ANGKA (ikon segitiga peringatan) --}}
                    <button
                        type="button"
                        id="summaryToggleBtn"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-100 print:hidden"
                        aria-controls="summaryChips"
                        aria-expanded="false"
                        aria-label="Tampilkan ringkasan angka"
                        title="Ringkasan angka"
                        onclick="toggleSummaryChips()">
                        <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" class="h-4 w-4 fill-none stroke-current stroke-2">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            <path d="M12 9v4" />
                            <path d="M12 17h.01" />
                        </svg>
                    </button>
                </div>

                {{-- RINGKASAN ANGKA (tersembunyi, dibuka dengan tombol segitiga) --}}
                <div id="summaryChips" class="summary-chips mt-3 hidden flex-wrap gap-2 print:flex">
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
                        <col style="width:8%">
                        <col style="width:10%">
                        <col style="width:11%">
                        <col style="width:9%">
                        <col style="width:8%">
                        <col style="width:8%">
                        <col style="width:6%">
                        <col style="width:6%">
                        <col style="width:7%">
                        <col style="width:6%">
                        <col style="width:13%">
                        <col style="width:8%">
                    </colgroup>

                    <thead class="bg-[#1d2d50] text-white">
                        <tr class="print-document-header-row">
                            <th colspan="12">
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
                            <input id="manualNomor" type="text" class="small-input w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800" placeholder="Nomor KA">
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label for="manualNama">NAMA KA</label>
                            <input id="manualNama" type="text" class="small-input w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800" placeholder="Nama KA">
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label for="manualRelasi">RELASI</label>
                            <input id="manualRelasi" type="text" class="small-input w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800" placeholder="Contoh: YK - SBI">
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label for="manualAktual">JAM AKTUAL</label>
                            <input id="manualAktual" type="time" class="small-input w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800">
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label>S21</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200" data-group="manual21" data-value="yes" onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200" data-group="manual21" data-value="no" onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label>GENTA</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200" data-group="manualGenta" data-value="yes" onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200" data-group="manualGenta" data-value="no" onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label>PINTU PERLINTASAN</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200" data-group="manualPintu" data-value="yes" onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200" data-group="manualPintu" data-value="no" onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label>S35</label>
                            <div class="mini-checks">
                                <button type="button" class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200" data-group="manual35" data-value="yes" onclick="setManualCheck(this)">✓</button>
                                <button type="button" class="mini-check inline-flex h-9 flex-1 min-w-0 items-center justify-center rounded-[9px] border border-slate-300 bg-slate-50 px-2 text-lg font-medium leading-none text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-200" data-group="manual35" data-value="no" onclick="setManualCheck(this)">×</button>
                            </div>
                        </div>

                        <div class="small-field flex min-w-0 flex-col gap-1 text-xs font-semibold text-slate-600">
                            <label for="manualKeterangan">KETERANGAN</label>
                            <input id="manualKeterangan" type="text" class="small-input w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800" placeholder="Keterangan">
                        </div>

                        <div class="add-actions flex flex-nowrap items-center gap-1 whitespace-nowrap">
                            <button type="button" class="add-button inline-flex items-center justify-center rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50" onclick="tambahKAManual()">
                                Simpan KA
                            </button>

                            <button type="button" class="action-btn btn-outline inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 border border-slate-300 bg-white text-slate-700 hover:bg-slate-50" onclick="toggleFormTambahKA(false)">
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

                    <div id="saveStatus" class="save-status idle mt-1 text-xs font-medium text-slate-600" role="status" aria-live="polite">
                        <span class="save-dot"></span>
                        <span id="saveStatusText">Perubahan hanya tersimpan setelah tombol Simpan Semua ditekan.</span>
                    </div>
                </div>

                <a id="selesaiLink" href="{{ url('/user/serah-terima-dinas') }}" class="action-btn btn-orange inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50 bg-orange-600 text-white hover:bg-orange-700">
                    Selesai &amp; Generate Laporan Serah Terima
                </a>
            </div>
        </section>

        {{-- CATATAN DINAS --}}
        @php
            // Catatan satu siklus (shift 1-3). Dikirim dari controller sebagai
            // $catatanSiklus; fallback ini hanya cadangan agar tetap berjalan.
            $catatanSiklus = $catatanSiklus
                ?? app(\App\Services\CatatanSiklusService::class)->untuk($dinasAktif ?? null);
        @endphp

        <section class="page-card print:hidden cd-quick rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" id="catatanDinasQuick">
            <div class="cd-quick-header flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="section-title mb-1 text-base font-bold text-slate-800">Catatan Dinas</div>
                    <p class="cd-quick-desc mt-1 text-sm text-slate-500">
                        Catatan siklus shift 1 sampai 3. Akan diganti saat shift 1 berikutnya dimulai.
                    </p>
                </div>

                <div class="cd-quick-actions flex flex-wrap items-center gap-2">
                    <a href="{{ route('user.catatan-dinas') }}" class="cd-quick-link text-sm font-semibold text-blue-700 underline-offset-2 hover:underline">
                        Lihat semua catatan &rarr;
                    </a>

                    <button type="button" class="add-button cd-open-btn inline-flex items-center justify-center rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-50" onclick="openCatatanModal()" @disabled(!($dinasAktif ?? null))>
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
            @else
                {{-- DAFTAR CATATAN SIKLUS INI --}}
                <div class="mt-4 space-y-2">
                    @forelse ($catatanSiklus as $item)
                        @php
                            $shiftCatatan = $item->dinas?->shift?->nama_shift ?? '-';
                            $penulisCatatan = $item->user?->name ?? $item->dinas?->user?->name ?? '-';
                            $waktuCatatan = $item->waktu_catatan
                                ? \Carbon\Carbon::parse($item->waktu_catatan)
                                    ->timezone('Asia/Jakarta')
                                    ->locale('id')
                                    ->translatedFormat('d M Y, H:i')
                                : '-';
                        @endphp

                        <article class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="mb-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                <span class="inline-block rounded-full bg-[#edf3fb] px-2 py-0.5 text-[10px] font-semibold text-[#1e3156]">
                                    {{ $shiftCatatan }}
                                </span>
                                <span class="font-semibold text-slate-700">{{ $penulisCatatan }}</span>
                                <span>&bull;</span>
                                <span>{{ $waktuCatatan }} WIB</span>
                            </div>

                            <p class="m-0 whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-700">{{ $item->isi_catatan }}</p>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 px-3 py-6 text-center text-sm text-slate-500">
                            Belum ada catatan pada siklus dinas ini.
                        </div>
                    @endforelse
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

                <div class="cdm-modal w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="catatanModalTitle">
                    <div class="cdm-header mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                        <h2 class="cdm-title text-lg font-bold text-slate-800" id="catatanModalTitle">
                            Tambah Catatan Dinas
                        </h2>

                        <button type="button" class="cdm-close rounded-lg px-3 py-1 text-2xl leading-none text-slate-500 hover:bg-slate-100" aria-label="Tutup" onclick="closeCatatanModal()">
                            ×
                        </button>
                    </div>

                    <form id="catatanForm" action="{{ route('user.catatan-dinas.store') }}" method="POST">
                        @csrf

                        <input type="hidden" name="redirect_to" value="realisasi-ka">

                        <div class="cdm-group mb-4 flex flex-col gap-1">
                            <label class="cdm-label text-sm font-semibold text-slate-700" for="cdmTanggal">Tanggal</label>
                            <input type="date" class="cdm-input w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" id="cdmTanggal" name="tanggal" value="{{ $tanggalCatatan }}" readonly required>
                        </div>

                        <div class="cdm-group mb-4 flex flex-col gap-1">
                            <label class="cdm-label text-sm font-semibold text-slate-700" for="cdmPjl">PJL</label>
                            <input type="text" class="cdm-input w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" id="cdmPjl" value="{{ auth()->user()->name ?? '' }}" readonly>
                        </div>

                        <div class="cdm-group mb-4 flex flex-col gap-1">
                            <label class="cdm-label text-sm font-semibold text-slate-700" for="cdmShift">Shift</label>
                            <select class="cdm-input cdm-select w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100" id="cdmShift" name="shift_id" required>
                                <option value="{{ $dinasAktif->shift_id }}" selected>
                                    {{ $dinasAktif->shift?->nama_shift ?? '-' }}
                                    — {{ $tanggalCatatan }}
                                </option>
                            </select>
                        </div>

                        <div class="cdm-group mb-4 flex flex-col gap-1">
                            <label class="cdm-label text-sm font-semibold text-slate-700" for="cdmCatatan">Catatan</label>
                            <textarea class="cdm-input cdm-textarea w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-800 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 min-h-32 resize-y" id="cdmCatatan" name="catatan" maxlength="5000" required placeholder="Tuliskan catatan selama pelaksanaan dinas...">{{ old('catatan') }}</textarea>

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
                            <button type="button" class="cdm-btn cdm-cancel rounded-lg px-4 py-2 text-sm font-semibold border border-slate-300 bg-white text-slate-700 hover:bg-slate-50" onclick="closeCatatanModal()">
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

        {{-- MODAL KETERANGAN WARNA (nilai mengikuti konfigurasi Admin) --}}
        @php
            $legendSetting = $realisasiKaSetting ?? null;

            $legendToleransi = (int) data_get($legendSetting, 'toleransi_keterlambatan_menit', 5);
            $legendLebihCepat = (int) data_get($legendSetting, 'batas_lebih_awal_menit', 2);
            $legendAwas2Arah = (int) data_get($legendSetting, 'batas_awas_2_arah_menit', 5);
        @endphp

        <div
            class="print:hidden fixed inset-0 z-[100] hidden items-center justify-center overflow-y-auto bg-slate-950/50 p-4"
            id="legendModal"
            aria-hidden="true"
            onclick="if (event.target === this) closeLegendModal()">

            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="legendModalTitle">
                <div class="mb-4 flex items-center justify-between border-b border-slate-200 pb-3">
                    <h2 class="text-lg font-bold text-slate-800" id="legendModalTitle">
                        Keterangan Warna
                    </h2>

                    <button type="button" data-legend-close class="rounded-lg px-3 py-1 text-2xl leading-none text-slate-500 hover:bg-slate-100" aria-label="Tutup" onclick="closeLegendModal()">
                        ×
                    </button>
                </div>

                <ul class="flex flex-col gap-3 text-sm text-slate-700">
                    <li class="flex items-start gap-3">
                        <span class="mt-1 inline-block h-3 w-3 shrink-0 rounded-full bg-green-500"></span>
                        <span><strong>Hijau</strong> — KA tepat waktu.</span>
                    </li>

                    <li class="flex items-start gap-3">
                        <span class="mt-1 inline-block h-3 w-3 shrink-0 rounded-full bg-amber-400"></span>
                        <span><strong>Kuning</strong> — KA terlambat melebihi toleransi {{ $legendToleransi }} menit.</span>
                    </li>

                    <li class="flex items-start gap-3">
                        <span class="mt-1 inline-block h-3 w-3 shrink-0 rounded-full bg-blue-500"></span>
                        <span><strong>Biru</strong> — KA lebih cepat dari {{ $legendLebihCepat }} menit.</span>
                    </li>

                    <li class="flex items-start gap-3">
                        <span class="mt-1 inline-block h-3 w-3 shrink-0 rounded-full bg-rose-500"></span>
                        <span><strong>Merah</strong> — KA dibatalkan.</span>
                    </li>

                    <li class="flex items-start gap-3">
                        <span class="mt-1 inline-block h-3 w-3 shrink-0 rounded-full bg-slate-300"></span>
                        <span><strong>Abu-abu</strong> — realisasi belum diproses.</span>
                    </li>

                    <li class="flex items-start gap-3">
                        <span class="mt-1 inline-block h-3 w-3 shrink-0 rounded-sm" style="background:#fff1f2;border:1px solid #fca5a5;box-shadow:inset 3px 0 0 #ef5145;"></span>
                        <span><strong>Merah muda</strong> — AWAS 2 ARAH: KA berlawanan arah dengan selisih &le; {{ $legendAwas2Arah }} menit.</span>
                    </li>

                    <li class="flex items-start gap-3">
                        <span class="flex shrink-0 gap-1">
                            <span class="legend-mark mk-yes">✓</span>
                            <span class="legend-mark mk-no">×</span>
                        </span>
                        <span><strong>Pemeriksaan</strong> — ✓ Ya, × Tidak, abu-abu belum diisi.</span>
                    </li>
                </ul>

                <div class="mt-5 flex justify-end border-t border-slate-200 pt-4">
                    <button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" onclick="closeLegendModal()">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </main>
@endsection

@push('scripts')
    @include('user.realisasi-ka.scripts.config')

    <script>
        /* Modal keterangan warna (tombol ikon mata) */
        function setLegendState(open) {
            const modal = document.getElementById('legendModal');
            const btn = document.getElementById('legendToggleBtn');
            const iconOpen = document.getElementById('legendIconOpen');
            const iconClosed = document.getElementById('legendIconClosed');

            if (!modal) {
                return;
            }

            modal.classList.toggle('hidden', !open);
            modal.classList.toggle('flex', open);
            modal.setAttribute('aria-hidden', open ? 'false' : 'true');

            if (btn) {
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            }

            if (iconOpen) {
                iconOpen.classList.toggle('hidden', open);
            }

            if (iconClosed) {
                iconClosed.classList.toggle('hidden', !open);
            }
        }

        function openLegendModal() {
            setLegendState(true);

            const closeBtn = document.querySelector('#legendModal [data-legend-close]');

            if (closeBtn) {
                closeBtn.focus();
            }
        }

        function closeLegendModal() {
            setLegendState(false);

            const btn = document.getElementById('legendToggleBtn');

            if (btn) {
                btn.focus();
            }
        }

        function toggleSummaryChips() {
            const chips = document.getElementById('summaryChips');
            const btn = document.getElementById('summaryToggleBtn');

            if (!chips) {
                return;
            }

            const open = chips.classList.contains('hidden');

            chips.classList.toggle('hidden', !open);
            chips.classList.toggle('flex', open);

            if (btn) {
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                btn.setAttribute(
                    'aria-label',
                    open ? 'Sembunyikan ringkasan angka' : 'Tampilkan ringkasan angka'
                );
                btn.classList.toggle('bg-slate-100', open);
            }
        }

        function toggleLegendModal() {
            const modal = document.getElementById('legendModal');

            if (modal && modal.classList.contains('hidden')) {
                openLegendModal();
            } else {
                closeLegendModal();
            }
        }

        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeLegendModal();
            }
        });
    </script>

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