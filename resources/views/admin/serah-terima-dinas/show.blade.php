@extends('layouts.admin')

@section('title', 'Detail Serah Terima Dinas')

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | NORMALISASI RINGKASAN
    |--------------------------------------------------------------------------
    */

    $ringkasan = $serahTerima->ringkasan;

    if (is_string($ringkasan)) {
        $decoded = json_decode($ringkasan, true);

        $ringkasan = is_array($decoded)
            ? $decoded
            : ['catatan_serah_terima' => $ringkasan];
    }

    $ringkasan = is_array($ringkasan) ? $ringkasan : [];

    /*
    |--------------------------------------------------------------------------
    | HELPER DATA
    |--------------------------------------------------------------------------
    */

    $ambil = function ($data, array $keys, $default = null) {
        foreach ($keys as $key) {
            $value = data_get($data, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    };

    $formatNilai = function ($nilai) use (&$formatNilai) {
        if (is_array($nilai)) {
            if ($nilai === []) {
                return '—';
            }

            $hasil = [];

            foreach ($nilai as $key => $value) {
                $teks = $formatNilai($value);

                if (is_string($key)) {
                    $label = ucwords(str_replace(
                        ['_', '-'],
                        ' ',
                        $key
                    ));

                    $hasil[] = $label . ': ' . $teks;
                } else {
                    $hasil[] = $teks;
                }
            }

            return implode('; ', $hasil);
        }

        if (is_object($nilai)) {
            if (method_exists($nilai, 'toArray')) {
                return $formatNilai($nilai->toArray());
            }

            return '—';
        }

        if (is_bool($nilai)) {
            return $nilai ? 'Ya' : 'Tidak';
        }

        if (is_scalar($nilai)) {
            $teks = trim((string) $nilai);

            return $teks !== '' ? $teks : '—';
        }

        return '—';
    };

    $formatJawaban = function ($nilai) use ($formatNilai) {
        if (is_bool($nilai)) {
            return $nilai ? '✓' : '✗';
        }

        if (is_scalar($nilai)) {
            return match (strtolower(trim((string) $nilai))) {
                'yes', 'ya', 'true', '1', 'centang' => '✓',
                'no', 'tidak', 'false', '0', 'silang' => '✗',
                default => $formatNilai($nilai),
            };
        }

        return $formatNilai($nilai);
    };

    $labelKunci = function ($key) {
        return ucwords(str_replace(
            ['_', '-'],
            ' ',
            (string) $key
        ));
    };

    $normalisasiDaftar = function ($data) {
        if (is_string($data)) {
            $decoded = json_decode($data, true);

            if (is_array($decoded)) {
                $data = $decoded;
            } else {
                return trim($data) !== '' ? [$data] : [];
            }
        }

        return is_array($data) ? $data : [];
    };

    $nilaiPertama = function ($data, array $keys, $default = '—') {
        foreach ($keys as $key) {
            $value = data_get($data, $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    };

    /*
    |--------------------------------------------------------------------------
    | STATUS PENGAJUAN
    |--------------------------------------------------------------------------
    */

    $status = $serahTerima->status;

    $statusClass = match ($status) {
        'selesai' => 'bg-green-100 text-green-800',
        'menunggu_penerima' => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-700',
    };

    $statusLabel = match ($status) {
        'selesai' => 'Selesai',
        'menunggu_penerima' => 'Menunggu konfirmasi penerima',
        default => ucfirst(str_replace(
            '_',
            ' ',
            $status ?? 'Tidak diketahui'
        )),
    };

    /*
    |--------------------------------------------------------------------------
    | DATA RINGKASAN
    |--------------------------------------------------------------------------
    */

    $alat = $ambil($ringkasan, [
        'alat',
        'alat_pengamanan',
        'kondisi_alat',
    ], []);

    $alatCatatan = $ambil($ringkasan, [
        'alat_catatan',
        'catatan_alat',
    ], []);

    $kaBatal = $ambil($ringkasan, [
        'ka_batal',
        'ka_batal_data',
    ], []);

    $kaBerjalan = $ambil($ringkasan, [
        'ka_berjalan',
        'ka_berjalan_data',
    ], []);

    $kaBelumLewat = $ambil($ringkasan, [
        'ka_belum_lewat',
        'ka_belum_lewat_data',
    ], []);

    $keretaApi = $ambil($ringkasan, [
        'kereta_api',
    ], []);

    $inventaris = $ambil($ringkasan, [
        'inventaris',
        'inventaris_data',
    ], []);

    $catatan = $ambil($ringkasan, [
        'catatan_serah_terima',
        'catatan_penting',
        'catatan',
    ]);

    $alat = $normalisasiDaftar($alat);
    $alatCatatan = $normalisasiDaftar($alatCatatan);
    $kaBatal = $normalisasiDaftar($kaBatal);
    $kaBerjalan = $normalisasiDaftar($kaBerjalan);
    $kaBelumLewat = $normalisasiDaftar($kaBelumLewat);
    $keretaApi = $normalisasiDaftar($keretaApi);
    $inventaris = $normalisasiDaftar($inventaris);

    /*
    |--------------------------------------------------------------------------
    | IDENTITAS PPKA / PENYERAH DAN PENERIMA
    |--------------------------------------------------------------------------
    |
    | PPKA dan penyerah ditampilkan sebagai satu identitas.
    | Data PPKA di ringkasan diprioritaskan, kemudian data penyerah
    | dari dinas digunakan sebagai fallback.
    |
    */

    $namaPpka = $ambil($ringkasan, [
        'nama_ppka',
        'ppka',
        'nama_penyerah',
    ]);

    $nipPpka = $ambil($ringkasan, [
        'nip_ppka',
        'nip_penyerah',
    ]);

    $namaPpka = $namaPpka
        ?: ($serahTerima->dinasPenyerah?->user?->name ?? '—');

    $nipPpka = $nipPpka
        ?: ($serahTerima->dinasPenyerah?->user?->nip ?? '—');

    $namaPenerima = data_get($ringkasan, 'nama_penerima')
        ?: ($serahTerima->penerima?->name ?? '—');

    $nipPenerima = data_get($ringkasan, 'nip_penerima')
        ?: ($serahTerima->penerima?->nip ?? '—');

    /*
    |--------------------------------------------------------------------------
    | INFORMASI TAMBAHAN
    |--------------------------------------------------------------------------
    |
    | Semua kunci yang sudah ditampilkan pada bagian khusus tidak
    | ditampilkan ulang sebagai informasi tambahan.
    |
    */

    $keysDikenal = [
        'alat',
        'alat_pengamanan',
        'kondisi_alat',
        'alat_catatan',
        'catatan_alat',

        'ka_batal',
        'ka_batal_data',
        'ka_berjalan',
        'ka_berjalan_data',
        'ka_belum_lewat',
        'ka_belum_lewat_data',
        'kereta_api',

        'inventaris',
        'inventaris_data',

        'catatan_serah_terima',
        'catatan_penting',
        'catatan',

        'nama_penerima',
        'nip_penerima',

        'ppka',
        'nama_ppka',
        'nip_ppka',
        'nama_penyerah',
        'nip_penyerah',
    ];

    $dataTambahan = collect($ringkasan)->reject(
        fn ($value, $key) => in_array(
            (string) $key,
            $keysDikenal,
            true
        )
    );

    /*
    |--------------------------------------------------------------------------
    | STATUS REVIEW
    |--------------------------------------------------------------------------
    */

    $statusReview = $serahTerima->status_review ?? 'menunggu_review';

    $statusReviewClass = match ($statusReview) {
        'disetujui' => 'bg-green-100 text-green-800',
        'perlu_revisi' => 'bg-red-100 text-red-800',
        default => 'bg-amber-100 text-amber-800',
    };

    $statusReviewLabel = match ($statusReview) {
        'disetujui' => 'Disetujui',
        'perlu_revisi' => 'Perlu Revisi',
        default => 'Menunggu Review',
    };

    $penerimaanTerkonfirmasi = filled(
        $serahTerima->tanda_tangan_penerima
    );

    $reviewSudahDisetujui = $statusReview === 'disetujui';

    /*
    |--------------------------------------------------------------------------
    | DATA MASTER JADWAL KA
    |--------------------------------------------------------------------------
    */

    $jadwalIds = collect($keretaApi)
        ->filter(fn ($item) => is_array($item))
        ->pluck('jadwal_ka_id')
        ->filter()
        ->unique()
        ->values();

    $jadwalMaster = collect();

    if ($jadwalIds->isNotEmpty()) {
        $jadwalMaster = \App\Models\JadwalKa::whereIn(
            'id',
            $jadwalIds
        )->get()->keyBy('id');
    }

    /*
    |--------------------------------------------------------------------------
    | FORMAT TANGGAL
    |--------------------------------------------------------------------------
    */

    $formatTanggal = function ($tanggal) {
        if (!$tanggal) {
            return '—';
        }

        try {
            return $tanggal instanceof \Carbon\CarbonInterface
                ? $tanggal->format('d/m/Y H:i')
                : \Illuminate\Support\Carbon::parse($tanggal)
                    ->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return (string) $tanggal;
        }
    };
@endphp

<div class="mx-auto max-w-7xl px-5 py-8 sm:px-8">

    {{-- HEADER --}}
    <div class="mb-6">
        <a
            href="{{ route('admin.serah-terima-dinas.index') }}"
            class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-blue-900"
        >
            <span aria-hidden="true">←</span>
            Kembali ke daftar pengajuan
        </a>

        <div class="mt-4 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-3xl font-bold text-[#1d2d50]">
                    Detail Serah Terima Dinas
                </h1>

                <p class="mt-2 text-slate-500">
                    Periksa informasi serah terima, tanda tangan petugas,
                    dan hasil review administrasi.
                </p>
            </div>

            <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-semibold {{ $statusClass }}">
                {{ $statusLabel }}
            </span>
        </div>
    </div>

    {{-- NOTIFIKASI --}}
    @if (session('success'))
        <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800">
            <p class="font-semibold">Pengajuan belum dapat diproses.</p>

            <ul class="mt-2 list-inside list-disc text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- INFORMASI PENGAJUAN --}}
    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="mb-5 text-lg font-bold text-[#1d2d50]">
            Informasi Pengajuan
        </h2>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">

            {{-- PPKA / PENYERAH --}}
            <div>
                <p class="text-sm text-slate-500">PPKA / Petugas Penyerah</p>

                <p class="mt-1 font-semibold text-slate-800">
                    {{ $namaPpka }}
                </p>

                <p class="text-sm text-slate-500">
                    NIP: {{ $nipPpka }}
                </p>
            </div>

            {{-- JPL --}}
            <div>
                <p class="text-sm text-slate-500">JPL</p>

                <p class="mt-1 font-semibold text-slate-800">
                    {{ $serahTerima->dinasPenyerah?->jpl?->nama_jpl
                        ?? $serahTerima->dinasPenyerah?->jpl?->kode_jpl
                        ?? '—' }}
                </p>
            </div>

            {{-- WAKTU SERAH TERIMA --}}
            <div>
                <p class="text-sm text-slate-500">Waktu Serah Terima</p>

                <p class="mt-1 font-semibold text-slate-800">
                    {{ $formatTanggal($serahTerima->waktu_serah_terima) }}
                </p>
            </div>

            {{-- SHIFT --}}
            <div>
                <p class="text-sm text-slate-500">Shift Penyerah</p>

                <p class="mt-1 font-semibold text-slate-800">
                    {{ $serahTerima->dinasPenyerah?->shift?->nama_shift ?? '—' }}
                </p>
            </div>

            {{-- PENERIMA --}}
            <div>
                <p class="text-sm text-slate-500">Petugas Penerima</p>

                <p class="mt-1 font-semibold text-slate-800">
                    {{ $namaPenerima }}
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    NIP: {{ $nipPenerima }}
                </p>
            </div>

            {{-- ID PENGAJUAN --}}
            <div>
                <p class="text-sm text-slate-500">ID Pengajuan</p>

                <p class="mt-1 font-semibold text-slate-800">
                    #{{ $serahTerima->id }}
                </p>
            </div>
        </div>
    </section>

    {{-- RINGKASAN --}}
    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-6">
            <h2 class="text-lg font-bold text-[#1d2d50]">
                Ringkasan Serah Terima
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Rangkuman kondisi alat, perjalanan KA, inventaris, dan catatan petugas.
            </p>
        </div>

        <div class="space-y-6">

            {{-- KONDISI ALAT --}}
            <section>
                <h3 class="mb-3 font-semibold text-slate-800">
                    1. Kondisi Alat Pengamanan
                </h3>

                @if (count($alat))
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($alat as $nama => $kondisi)
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm text-slate-500">
                                    {{ is_string($nama) ? $labelKunci($nama) : 'Peralatan' }}
                                </p>

                                <p class="mt-1 font-semibold text-slate-800">
                                    {{ $formatNilai($kondisi) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-xl bg-slate-50 p-4 text-slate-500">
                        Belum ada data kondisi alat.
                    </p>
                @endif
            </section>

            {{-- CATATAN ALAT --}}
            <section>
                <h3 class="mb-3 font-semibold text-slate-800">
                    2. Catatan Alat Pengamanan
                </h3>

                @if (count($alatCatatan))
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($alatCatatan as $nama => $catatanAlat)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-500">
                                    {{ is_string($nama) ? $labelKunci($nama) : 'Catatan alat' }}
                                </p>

                                <p class="mt-1 whitespace-pre-wrap break-words text-slate-800">
                                    {{ $formatNilai($catatanAlat) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-xl border border-slate-200 p-4 text-slate-500">
                        Tidak ada catatan alat.
                    </p>
                @endif
            </section>

            <div class="border-t border-slate-100"></div>

            {{-- DETAIL PERJALANAN KA --}}
            <section>
                <div class="mb-3">
                    <h3 class="font-semibold text-slate-800">
                        3. Detail Perjalanan Kereta Api
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Data jadwal, realisasi perjalanan KA, dan pemeriksaan
                        peralatan perlintasan pada saat serah terima.
                    </p>
                </div>

                @if (count($keretaApi) && is_array(reset($keretaApi)))
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-700">No.</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-700">Nomor KA</th>
                                    <th class="min-w-48 px-4 py-3 text-left font-semibold text-slate-700">Nama KA</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-700">Dari (Hulu)</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-700">Ke (Hilir)</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-700">Jadwal</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-700">Jam Aktual</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-700">Status</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-700">SEMBOYAN 21</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-700">GENTA</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-700">PINTU PERLINTASAN</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-slate-700">SEMBOYAN 35</th>
                                    <th class="min-w-40 px-4 py-3 text-left font-semibold text-slate-700">Keterangan</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($keretaApi as $index => $ka)
                                    @if (is_array($ka))
                                        @php
                                            $idJadwal = data_get($ka, 'jadwal_ka_id');

                                            $master = $idJadwal
                                                ? $jadwalMaster->get($idJadwal)
                                                : null;

                                            /*
                                             * Snapshot diprioritaskan agar
                                             * riwayat serah terima konsisten.
                                             */
                                            $nomorKa = $nilaiPertama($ka, [
                                                'nomor',
                                                'nomor_ka',
                                                'no_ka',
                                                'kode_ka',
                                            ]);

                                            $namaKa = $nilaiPertama($ka, [
                                                'nama',
                                                'nama_ka',
                                                'nama_kereta',
                                            ]);

                                            $jadwal = $nilaiPertama($ka, [
                                                'jadwal',
                                                'jam_jadwal',
                                                'jam',
                                                'jam_berangkat',
                                            ]);

                                            $aktual = $nilaiPertama($ka, [
                                                'aktual',
                                                'jam_aktual',
                                            ]);

                                            $statusKa = $nilaiPertama($ka, [
                                                'status',
                                                'status_realisasi',
                                            ]);

                                            $keteranganKa = $nilaiPertama($ka, [
                                                'keterangan',
                                                'catatan',
                                            ]);

                                            $semboyan21 = $nilaiPertama($ka, [
                                                'semboyan21',
                                                'semboyan_21',
                                            ]);

                                            $genta = $nilaiPertama($ka, [
                                                'genta',
                                            ]);

                                            $pintu = $nilaiPertama($ka, [
                                                'pintu',
                                                'pintu_perlintasan',
                                            ]);

                                            $semboyan35 = $nilaiPertama($ka, [
                                                'semboyan35',
                                                'semboyan_35',
                                            ]);

                                            $semboyan21Tampil = $formatJawaban($semboyan21);
                                            $gentaTampil = $formatJawaban($genta);
                                            $pintuTampil = $formatJawaban($pintu);
                                            $semboyan35Tampil = $formatJawaban($semboyan35);

                                            $dari = $nilaiPertama($ka, [
                                                'dari',
                                                'asal',
                                                'stasiun_asal',
                                                'stasiun_dari',
                                                'dari_stasiun',
                                                'hulu',
                                                'relasi_hulu',
                                            ], null);

                                            $ke = $nilaiPertama($ka, [
                                                'ke',
                                                'tujuan',
                                                'stasiun_tujuan',
                                                'stasiun_ke',
                                                'ke_stasiun',
                                                'hilir',
                                                'relasi_hilir',
                                            ], null);

                                            if ($master) {
                                                $atributMaster = $master->getAttributes();

                                                if ($nomorKa === '—') {
                                                    $nomorKa = $nilaiPertama(
                                                        $atributMaster,
                                                        [
                                                            'nomor',
                                                            'nomor_ka',
                                                            'no_ka',
                                                            'kode_ka',
                                                        ]
                                                    );
                                                }

                                                if ($namaKa === '—') {
                                                    $namaKa = $nilaiPertama(
                                                        $atributMaster,
                                                        [
                                                            'nama',
                                                            'nama_ka',
                                                            'nama_kereta',
                                                        ]
                                                    );
                                                }

                                                if ($dari === null || $dari === '—') {
                                                    $dari = $nilaiPertama(
                                                        $atributMaster,
                                                        [
                                                            'dari',
                                                            'asal',
                                                            'stasiun_asal',
                                                            'stasiun_dari',
                                                            'dari_stasiun',
                                                            'hulu',
                                                        ]
                                                    );
                                                }

                                                if ($ke === null || $ke === '—') {
                                                    $ke = $nilaiPertama(
                                                        $atributMaster,
                                                        [
                                                            'ke',
                                                            'tujuan',
                                                            'stasiun_tujuan',
                                                            'stasiun_ke',
                                                            'ke_stasiun',
                                                            'hilir',
                                                        ]
                                                    );
                                                }
                                            }

                                            $statusNormal = strtoupper(
                                                trim((string) $statusKa)
                                            );

                                            $kelasStatus = match ($statusNormal) {
                                                'JALAN',
                                                'BERJALAN',
                                                'SELESAI' =>
                                                    'bg-green-100 text-green-700',

                                                'BATAL',
                                                'DIBATALKAN',
                                                'TIDAK JALAN' =>
                                                    'bg-red-100 text-red-700',

                                                'BELUM' =>
                                                    'bg-amber-100 text-amber-700',

                                                default =>
                                                    'bg-slate-100 text-slate-700',
                                            };
                                        @endphp

                                        <tr class="transition hover:bg-slate-50">
                                            <td class="whitespace-nowrap px-4 py-3 text-slate-500">
                                                {{ $index + 1 }}
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-900">
                                                {{ $formatNilai($nomorKa) }}
                                            </td>

                                            <td class="px-4 py-3 text-slate-700">
                                                {{ $formatNilai($namaKa) }}
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                                {{ $formatNilai($dari) }}
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                                {{ $formatNilai($ke) }}
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                                {{ $formatNilai($jadwal) }}
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                                {{ $formatNilai($aktual) }}
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3">
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $kelasStatus }}">
                                                    {{ $formatNilai($statusKa) }}
                                                </span>
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                                <span @if ($semboyan21Tampil === '✓') role="img" aria-label="Ya" title="Ya" @elseif ($semboyan21Tampil === '✗') role="img" aria-label="Tidak" title="Tidak" @endif>
                                                    {{ $semboyan21Tampil }}
                                                </span>
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                                <span @if ($gentaTampil === '✓') role="img" aria-label="Ya" title="Ya" @elseif ($gentaTampil === '✗') role="img" aria-label="Tidak" title="Tidak" @endif>
                                                    {{ $gentaTampil }}
                                                </span>
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                                <span @if ($pintuTampil === '✓') role="img" aria-label="Ya" title="Ya" @elseif ($pintuTampil === '✗') role="img" aria-label="Tidak" title="Tidak" @endif>
                                                    {{ $pintuTampil }}
                                                </span>
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-slate-700">
                                                <span @if ($semboyan35Tampil === '✓') role="img" aria-label="Ya" title="Ya" @elseif ($semboyan35Tampil === '✗') role="img" aria-label="Tidak" title="Tidak" @endif>
                                                    {{ $semboyan35Tampil }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $formatNilai($keteranganKa) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ([
                            'KA Batal' => $kaBatal,
                            'KA Berjalan' => $kaBerjalan,
                            'KA Belum Lewat' => $kaBelumLewat,
                        ] as $judul => $daftarKA)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <h4 class="mb-2 font-semibold text-slate-700">
                                    {{ $judul }}
                                </h4>

                                @if (count($daftarKA))
                                    <ul class="list-inside list-disc space-y-2 text-slate-700">
                                        @foreach ($daftarKA as $ka)
                                            <li class="break-words">
                                                {{ $formatNilai($ka) }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-slate-500">
                                        Tidak ada data.
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- INVENTARIS --}}
            <section>
                <h3 class="mb-3 font-semibold text-slate-800">
                    4. Kondisi Inventaris
                </h3>

                @if (count($inventaris) && is_array(reset($inventaris)))
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">No.</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Kode Barang</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Nama Barang</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Kategori</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Jumlah</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Kondisi</th>
                                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Catatan</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($inventaris as $index => $barang)
                                    @if (is_array($barang))
                                        <tr>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $index + 1 }}
                                            </td>

                                            <td class="px-4 py-3 text-slate-700">
                                                {{ $nilaiPertama($barang, ['kode_barang']) }}
                                            </td>

                                            <td class="px-4 py-3 font-medium text-slate-800">
                                                {{ $nilaiPertama($barang, ['nama_barang', 'nama']) }}
                                            </td>

                                            <td class="px-4 py-3 text-slate-700">
                                                {{ $nilaiPertama($barang, ['kategori']) }}
                                            </td>

                                            <td class="px-4 py-3 text-slate-700">
                                                {{ $nilaiPertama($barang, ['jumlah_aktual', 'jumlah_master', 'jumlah']) }}
                                            </td>

                                            <td class="px-4 py-3 text-slate-700">
                                                {{ $nilaiPertama($barang, ['kondisi', 'status']) }}
                                            </td>

                                            <td class="px-4 py-3 text-slate-700">
                                                {{ $nilaiPertama($barang, ['catatan']) }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-slate-500">
                        Belum ada data inventaris pada snapshot serah terima ini.
                    </p>
                @endif
            </section>

            {{-- CATATAN SERAH TERIMA --}}
            <section>
                <h3 class="mb-3 font-semibold text-slate-800">
                    5. Catatan Serah Terima
                </h3>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="whitespace-pre-wrap break-words text-slate-700">{{ $formatNilai($catatan) }}</p>
                </div>
            </section>

            {{-- INFORMASI TAMBAHAN --}}
            @if ($dataTambahan->isNotEmpty())
                <section class="border-t border-slate-100 pt-5">
                    <h3 class="mb-3 font-semibold text-slate-800">
                        Informasi Tambahan
                    </h3>

                    <div class="space-y-3">
                        @foreach ($dataTambahan as $key => $value)
                            <div class="rounded-xl border border-slate-200 p-4">
                                <p class="text-sm text-slate-500">
                                    {{ $labelKunci($key) }}
                                </p>

                                <p class="mt-1 break-words text-slate-800">
                                    {{ $formatNilai($value) }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        </div>
    </section>

    {{-- TANDA TANGAN --}}
    <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">

        {{-- TANDA TANGAN PENYERAH --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-[#1d2d50]">
                Tanda Tangan Penyerah
            </h2>

            @if ($serahTerima->tanda_tangan_penyerah)
                <div class="flex min-h-48 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($serahTerima->tanda_tangan_penyerah) }}"
                        alt="Tanda tangan petugas penyerah"
                        class="max-h-40 max-w-full object-contain"
                    >
                </div>

                <p class="mt-3 text-sm text-green-700">
                    Tanda tangan penyerah tersedia.
                </p>
            @else
                <div class="flex min-h-48 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-center text-sm text-slate-500">
                    Tanda tangan penyerah belum tersedia.
                </div>
            @endif
        </section>

        {{-- TANDA TANGAN PENERIMA --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-bold text-[#1d2d50]">
                Tanda Tangan Penerima
            </h2>

            @if ($serahTerima->tanda_tangan_penerima)
                <div class="flex min-h-48 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <img
                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($serahTerima->tanda_tangan_penerima) }}"
                        alt="Tanda tangan petugas penerima"
                        class="max-h-40 max-w-full object-contain"
                    >
                </div>

                <p class="mt-3 text-sm text-green-700">
                    Penerimaan telah dikonfirmasi oleh petugas shift berikutnya.
                </p>
            @else
                <div class="flex min-h-48 flex-col items-center justify-center rounded-xl border border-dashed border-amber-300 bg-amber-50 p-6 text-center">
                    <p class="font-semibold text-amber-900">
                        Menunggu konfirmasi penerima
                    </p>

                    <p class="mt-1 text-sm text-amber-800">
                        Petugas PJL shift berikutnya harus mengonfirmasi penerimaan
                        dan membubuhkan tanda tangan melalui halaman petugas.
                    </p>
                </div>
            @endif
        </section>

    </div>

    {{-- REVIEW ADMIN --}}
    <section class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-[#1d2d50]">
                Review Admin
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Review administratif dilakukan setelah penerima mengonfirmasi dinas.
            </p>
        </div>

        <div class="mb-5">
            <span class="inline-flex rounded-full px-4 py-2 text-sm font-semibold {{ $statusReviewClass }}">
                {{ $statusReviewLabel }}
            </span>
        </div>

        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <p class="text-sm text-slate-500">Ditinjau oleh</p>

                <p class="mt-1 font-medium text-slate-800">
                    {{ $serahTerima->ditinjauOleh?->name ?? 'Belum ditinjau' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-slate-500">Waktu review</p>

                <p class="mt-1 font-medium text-slate-800">
                    {{ $formatTanggal($serahTerima->ditinjau_pada) }}
                </p>
            </div>
        </div>

        @if ($serahTerima->catatan_revisi)
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4">
                <h3 class="font-semibold text-red-800">
                    Alasan Revisi
                </h3>

                <p class="mt-2 whitespace-pre-line break-words text-sm text-red-700">{{ $serahTerima->catatan_revisi }}</p>
            </div>
        @endif

        @if (!$penerimaanTerkonfirmasi)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="font-semibold text-amber-900">
                    Review belum dapat dilakukan
                </p>

                <p class="mt-1 text-sm text-amber-800">
                    Menunggu penerima mengonfirmasi penerimaan dan membubuhkan tanda tangan.
                </p>
            </div>

        @elseif ($reviewSudahDisetujui)
            <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                <p class="font-semibold text-green-900">
                    Serah terima telah disetujui
                </p>

                <p class="mt-1 text-sm text-green-800">
                    Keputusan review administrasi telah tersimpan.
                </p>
            </div>

        @else
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="mb-4 text-sm text-slate-600">
                    Pastikan seluruh informasi telah diperiksa sebelum memilih keputusan.
                </p>

                @if (auth()->user()?->role?->name === 'admin')
                <div class="flex flex-col gap-3 sm:flex-row">
                    <form
                        action="{{ route('admin.serah-terima-dinas.update', $serahTerima->id) }}"
                        method="POST"
                        class="flex-1"
                        onsubmit="return confirm('Apakah kamu yakin ingin menyetujui serah terima ini?')"
                    >
                        @csrf
                        @method('PATCH')

                        <input type="hidden" name="status_review" value="disetujui">
                        <input type="hidden" name="catatan_revisi" value="">

                        <button
                            type="submit"
                            class="w-full rounded-xl bg-green-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-300 focus:ring-offset-2"
                        >
                            ✓ Setujui Serah Terima
                        </button>
                    </form>

                    <button
                        type="button"
                        id="bukaModalRevisi"
                        class="flex-1 rounded-xl bg-red-600 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2"
                    >
                        ✕ Tolak / Minta Revisi
                    </button>
                </div>
                @else
                    <p class="text-sm text-slate-600">Keputusan review hanya dapat dilakukan oleh Admin.</p>
                @endif
            </div>
        @endif
    </section>

    {{-- MODAL REVISI --}}
    @if ($penerimaanTerkonfirmasi && !$reviewSudahDisetujui && auth()->user()?->role?->name === 'admin')
        <div
            id="modalRevisi"
            class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-950/60 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="judulModalRevisi"
            aria-describedby="deskripsiModalRevisi"
        >
            <div class="my-auto w-full max-w-lg rounded-2xl bg-white shadow-2xl">

                <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-6">
                    <div>
                        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-red-100 text-xl text-red-600">
                            ✕
                        </div>

                        <h2 id="judulModalRevisi" class="text-xl font-bold text-slate-900">
                            Tolak / Minta Revisi
                        </h2>

                        <p id="deskripsiModalRevisi" class="mt-2 text-sm leading-6 text-slate-500">
                            Tuliskan alasan yang jelas agar petugas PJL mengetahui bagian yang perlu diperbaiki.
                        </p>
                    </div>

                    <button
                        type="button"
                        id="tutupModalRevisi"
                        aria-label="Tutup modal"
                        class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    >
                        ✕
                    </button>
                </div>

                <form
                    action="{{ route('admin.serah-terima-dinas.update', $serahTerima->id) }}"
                    method="POST"
                    id="formRevisi"
                >
                    @csrf
                    @method('PATCH')

                    <input type="hidden" name="status_review" value="perlu_revisi">

                    <div class="p-6">
                        <label for="alasanRevisi" class="mb-2 block text-sm font-semibold text-slate-700">
                            Alasan revisi <span class="text-red-600">*</span>
                        </label>

                        <textarea
                            id="alasanRevisi"
                            name="catatan_revisi"
                            rows="5"
                            minlength="5"
                            maxlength="5000"
                            required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-red-500 focus:ring-4 focus:ring-red-100"
                            placeholder="Jelaskan bagian yang harus diperbaiki..."
                        >{{ old('catatan_revisi', $serahTerima->catatan_revisi) }}</textarea>

                        <div class="mt-2 flex items-start justify-between gap-3">
                            <p class="text-xs text-slate-500">
                                Minimal 5 karakter dan maksimal 5.000 karakter.
                            </p>

                            <span id="jumlahKarakterRevisi" class="shrink-0 text-xs text-slate-400">
                                0/5000
                            </span>
                        </div>

                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <p class="text-xs leading-5 text-amber-800">
                                Keputusan ini akan tercatat sebagai permintaan revisi.
                                Pastikan alasannya spesifik dan dapat ditindaklanjuti.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50 p-6 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            id="batalModalRevisi"
                            class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 font-semibold text-slate-700 transition hover:bg-slate-100"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            id="kirimRevisi"
                            class="rounded-xl bg-red-600 px-5 py-2.5 font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300"
                        >
                            Kirim Keputusan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- KEMBALI --}}
    <div class="flex justify-start pb-4">
        <a
            href="{{ route('admin.serah-terima-dinas.index') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 font-medium text-slate-700 transition hover:bg-slate-50"
        >
            ← Kembali ke Daftar Pengajuan
        </a>
    </div>

</div>
@endsection

{{-- JAVASCRIPT MODAL --}}
@if ($penerimaanTerkonfirmasi && !$reviewSudahDisetujui && auth()->user()?->role?->name === 'admin')
    @push('scripts')
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('modalRevisi');
            const buka = document.getElementById('bukaModalRevisi');
            const tutup = document.getElementById('tutupModalRevisi');
            const batal = document.getElementById('batalModalRevisi');
            const alasan = document.getElementById('alasanRevisi');
            const form = document.getElementById('formRevisi');
            const jumlahKarakter = document.getElementById('jumlahKarakterRevisi');

            if (!modal || !buka || !tutup || !batal || !alasan || !form) {
                return;
            }

            let elemenSebelumnya = null;

            function perbaruiJumlahKarakter() {
                if (jumlahKarakter) {
                    jumlahKarakter.textContent =
                        `${alasan.value.length}/5000`;
                }
            }

            function bukaModal() {
                elemenSebelumnya = document.activeElement;

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');

                perbaruiJumlahKarakter();
                alasan.focus();
            }

            function tutupModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');

                if (elemenSebelumnya) {
                    elemenSebelumnya.focus();
                }
            }

            buka.addEventListener('click', bukaModal);
            tutup.addEventListener('click', tutupModal);
            batal.addEventListener('click', tutupModal);

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    tutupModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (
                    event.key === 'Escape'
                    && !modal.classList.contains('hidden')
                ) {
                    tutupModal();
                }
            });

            alasan.addEventListener('input', function () {
                alasan.setCustomValidity('');
                perbaruiJumlahKarakter();
            });

            form.addEventListener('submit', function (event) {
                const nilai = alasan.value.trim();

                if (nilai.length < 5) {
                    event.preventDefault();

                    alasan.setCustomValidity(
                        'Alasan revisi wajib diisi minimal 5 karakter.'
                    );

                    alasan.reportValidity();
                    alasan.focus();
                    return;
                }

                if (nilai.length > 5000) {
                    event.preventDefault();

                    alasan.setCustomValidity(
                        'Alasan revisi maksimal 5.000 karakter.'
                    );

                    alasan.reportValidity();
                    alasan.focus();
                    return;
                }

                alasan.setCustomValidity('');
                alasan.value = nilai;
            });

            perbaruiJumlahKarakter();

            @if ($errors->has('catatan_revisi'))
                bukaModal();
            @endif
        });
        </script>
    @endpush
@endif
