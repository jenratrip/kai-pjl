<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\KeretaApi;
use App\Models\RealisasiKa;
use App\Models\RealisasiKaSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RealisasiKaController extends Controller
{
    /**
     * Menampilkan jadwal KA, realisasi, dan konfigurasi Admin
     * untuk dinas aktif pengguna.
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | AMBIL DINAS AKTIF DAN IDENTITAS PETUGAS
        |--------------------------------------------------------------------------
        |
        | user:
        | Petugas yang sedang menjalankan dinas.
        |
        | userDigantikan:
        | Petugas reguler yang digantikan apabila petugas
        | yang sedang berdinas merupakan PJL Wakil.
        |
        */

        $dinasId = $request->session()->get('dinas_id');
        $dinas = null;

        if (is_numeric($dinasId) && (int) $dinasId > 0) {
            $dinas = Dinas::with([
                'jpl.resort.wilayah',
                'shift',
                'user',
                'userDigantikan',
            ])
                ->whereKey((int) $dinasId)
                ->where('user_id', auth()->id())
                ->where('status', 'berlangsung')
                ->first();
        }

        $dataKA = [];

        if ($dinas) {
            // Snapshot null atau kosong tidak boleh mengambil jadwal aktif sebagai pengganti.
            $snapshot = $dinas->jadwal_ka_snapshot;
            $jadwalList = is_array($snapshot) && is_array($snapshot['jadwal'] ?? null)
                ? $snapshot['jadwal']
                : [];

            /*
            |--------------------------------------------------------------------------
            | AMBIL REALISASI KA TERJADWAL
            |--------------------------------------------------------------------------
            */

            $realisasiList = RealisasiKa::with(['keretaApi', 'jadwalKa'])
                ->where('dinas_id', $dinas->id)
                ->where('is_manual', false)
                ->orderBy('id')
                ->get()
                ->keyBy('jadwal_ka_id');

            /*
            |--------------------------------------------------------------------------
            | GABUNGKAN JADWAL DENGAN REALISASI
            |--------------------------------------------------------------------------
            */

            foreach ($jadwalList as $jadwal) {
                if (! is_array($jadwal) || ! isset($jadwal['jadwal_ka_id'])) {
                    continue;
                }

                $jadwalId = (int) $jadwal['jadwal_ka_id'];
                $realisasi = $realisasiList->get($jadwalId);

                /*
                |--------------------------------------------------------------------------
                | HULU, HILIR, DAN RELASI
                |--------------------------------------------------------------------------
                |
                | HULU = jadwal_ka.dari
                | HILIR = jadwal_ka.ke
                | RELASI = dari + " - " + ke
                |
                */

                $dari = trim((string) ($jadwal['dari'] ?? ''));
                $ke = trim((string) ($jadwal['ke'] ?? ''));

                $relasi = ($dari !== '' && $ke !== '')
                    ? "{$dari} - {$ke}"
                    : ($dari !== '' ? $dari : $ke);

                $dataKA[] = [
                    'no' => count($dataKA) + 1,

                    'nomor' => $jadwal['nomor_ka'] ?? '-',
                    'nama' => $jadwal['nama_ka'] ?? '-',

                    'dari' => $dari,
                    'ke' => $ke,
                    'hulu' => $dari,
                    'hilir' => $ke,
                    'relasi' => $relasi,

                    'jadwal' => ! empty($jadwal['jam_lewat'])
                        ? substr((string) $jadwal['jam_lewat'], 0, 5)
                        : '',

                    'aktual' => $realisasi?->jam_aktual
                        ? substr((string) $realisasi->jam_aktual, 0, 5)
                        : '',

                    'status' => match ($realisasi?->status_lewat) {
                        'centang' => 'JALAN',
                        'silang' => 'BATAL',
                        default => 'BELUM',
                    },

                    'semboyan21' => $this->checkValue(
                        $realisasi?->status_s21
                    ),

                    'genta' => $this->checkValue(
                        $realisasi?->status_genta
                    ),

                    'pintu' => $this->checkValue(
                        $realisasi?->status_pintu_perlintasan
                    ),

                    'semboyan35' => $this->checkValue(
                        $realisasi?->status_s35
                    ),

                    'keterangan' => $realisasi?->catatan ?? '',

                    'jadwal_ka_id' => $jadwalId,
                    'realisasi_ka_id' => $realisasi?->id,
                    'is_manual' => false,
                ];
            }

            // Dinas legacy tanpa snapshot hanya menampilkan realisasi terjadwal yang sudah tersimpan.
            if ($snapshot === null) {
                foreach ($realisasiList as $realisasi) {
                    if ($realisasi->jadwal_ka_id === null) {
                        continue;
                    }

                    $jadwal = $realisasi->jadwalKa;
                    $dari = trim((string) ($jadwal?->dari ?? $realisasi->hulu ?? ''));
                    $ke = trim((string) ($jadwal?->ke ?? $realisasi->hilir ?? ''));
                    $relasi = ($dari !== '' && $ke !== '')
                        ? "{$dari} - {$ke}"
                        : ($dari !== '' ? $dari : $ke);

                    $dataKA[] = [
                        'no' => count($dataKA) + 1,
                        'nomor' => $realisasi->keretaApi?->nomor_ka ?? '-',
                        'nama' => $realisasi->keretaApi?->nama_ka ?? '-',
                        'dari' => $dari,
                        'ke' => $ke,
                        'hulu' => $dari,
                        'hilir' => $ke,
                        'relasi' => $relasi,
                        'jadwal' => $jadwal?->jam_lewat
                            ? substr((string) $jadwal->jam_lewat, 0, 5)
                            : '',
                        'aktual' => $realisasi->jam_aktual
                            ? substr((string) $realisasi->jam_aktual, 0, 5)
                            : '',
                        'status' => match ($realisasi->status_lewat) {
                            'centang' => 'JALAN',
                            'silang' => 'BATAL',
                            default => 'BELUM',
                        },
                        'semboyan21' => $this->checkValue($realisasi->status_s21),
                        'genta' => $this->checkValue($realisasi->status_genta),
                        'pintu' => $this->checkValue($realisasi->status_pintu_perlintasan),
                        'semboyan35' => $this->checkValue($realisasi->status_s35),
                        'keterangan' => $realisasi->catatan ?? '',
                        'jadwal_ka_id' => $realisasi->jadwal_ka_id,
                        'realisasi_ka_id' => $realisasi->id,
                        'is_manual' => false,
                    ];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | TAMBAHKAN KA MANUAL
            |--------------------------------------------------------------------------
            */

            $manualList = RealisasiKa::with('keretaApi')
                ->where('dinas_id', $dinas->id)
                ->where('is_manual', true)
                ->orderBy('id')
                ->get();

            foreach ($manualList as $realisasi) {
                $dataKA[] = [
                    'no' => count($dataKA) + 1,

                    'nomor' => $realisasi->keretaApi?->nomor_ka ?? '-',
                    'nama' => $realisasi->keretaApi?->nama_ka ?? '-',

                    /*
                    |--------------------------------------------------------------------------
                    | KA MANUAL TIDAK MEMILIKI JADWAL_KA
                    |--------------------------------------------------------------------------
                    */

                    'dari' => $realisasi->hulu ?? '',
                    'ke' => $realisasi->hilir ?? '',
                    'hulu' => $realisasi->hulu ?? '',
                    'hilir' => $realisasi->hilir ?? '',
                    'relasi' => $realisasi->relasi ?? '',

                    'jadwal' => '-',

                    'aktual' => $realisasi->jam_aktual
                        ? substr((string) $realisasi->jam_aktual, 0, 5)
                        : '',

                    'status' => match ($realisasi->status_lewat) {
                        'centang' => 'JALAN',
                        'silang' => 'BATAL',
                        default => 'BELUM',
                    },

                    'semboyan21' => $this->checkValue(
                        $realisasi->status_s21
                    ),

                    'genta' => $this->checkValue(
                        $realisasi->status_genta
                    ),

                    'pintu' => $this->checkValue(
                        $realisasi->status_pintu_perlintasan
                    ),

                    'semboyan35' => $this->checkValue(
                        $realisasi->status_s35
                    ),

                    'keterangan' => $realisasi->catatan ?? '',

                    'jadwal_ka_id' => null,
                    'realisasi_ka_id' => $realisasi->id,
                    'is_manual' => true,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | AMBIL KONFIGURASI REALISASI KA DARI ADMIN
        |--------------------------------------------------------------------------
        */

        $realisasiKaSetting = RealisasiKaSetting::query()->first()
            ?? new RealisasiKaSetting([
                'toleransi_keterlambatan_menit' => 5,
                'peringatan_keterlambatan_tinggi_menit' => 10,
                'jarak_minimum_antar_ka_menit' => 0,
                'warning_antar_ka_aktif' => false,
                'batas_penutupan_palang_menit' => 0,
                'warning_penutupan_palang_aktif' => false,
                'batas_lebih_awal_menit' => 2,
                'warning_lebih_awal_aktif' => true,
                'batas_awas_2_arah_menit' => 5,
                'warning_awas_2_arah_aktif' => false,
                'warning_keterlambatan_aktif' => false,
                'warning_keterlambatan_tinggi_aktif' => false,
            ]);

        /*
        |--------------------------------------------------------------------------
        | KIRIM DATA KE VIEW
        |--------------------------------------------------------------------------
        |
        | $dinasAktif sudah memuat:
        | - jpl.resort.wilayah
        | - shift
        | - user
        | - userDigantikan
        |
        | Data tersebut dapat digunakan langsung oleh header.
        |
        */

        return view('user.realisasi-ka.index', [
            'dataKA' => $dataKA,
            'dinasAktif' => $dinas,
            'realisasiKaSetting' => $realisasiKaSetting,
        ]);
    }

    /**
     * Menyimpan realisasi KA ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'data' => [
                'required',
                'array',
            ],

            'data.*.nomor' => [
                'required',
                'string',
                'max:50',
            ],

            'data.*.nama' => [
                'nullable',
                'string',
                'max:255',
            ],

            'data.*.hulu' => [
                'nullable',
                'string',
                'max:255',
            ],

            'data.*.hilir' => [
                'nullable',
                'string',
                'max:255',
            ],

            'data.*.relasi' => [
                'nullable',
                'string',
                'max:255',
            ],

            'data.*.jadwal_ka_id' => [
                'nullable',
                'integer',
            ],

            'data.*.realisasi_ka_id' => [
                'nullable',
                'integer',
            ],

            'data.*.is_manual' => [
                'required',
                'boolean',
            ],

            'data.*.aktual' => [
                'nullable',
                'date_format:H:i',
            ],

            'data.*.status' => [
                'required',
                Rule::in(['JALAN', 'BATAL', 'BELUM']),
            ],

            'data.*.semboyan21' => [
                'nullable',
                Rule::in(['yes', 'no']),
            ],

            'data.*.genta' => [
                'nullable',
                Rule::in(['yes', 'no']),
            ],

            'data.*.pintu' => [
                'nullable',
                Rule::in(['yes', 'no']),
            ],

            'data.*.semboyan35' => [
                'nullable',
                Rule::in(['yes', 'no']),
            ],

            'data.*.keterangan' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | PASTIKAN DINAS AKTIF MILIK PENGGUNA LOGIN
        |--------------------------------------------------------------------------
        */

        $dinasId = $request->session()->get('dinas_id');
        $dinas = is_numeric($dinasId) && (int) $dinasId > 0
            ? Dinas::query()
                ->whereKey((int) $dinasId)
                ->where('user_id', auth()->id())
                ->where('status', 'berlangsung')
                ->first()
            : null;

        if (! $dinas) {
            return response()->json([
                'message' => 'Tidak ada dinas aktif. Mulai dinas terlebih dahulu.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | SIMPAN SELURUH REALISASI DALAM TRANSAKSI DATABASE
        |--------------------------------------------------------------------------
        */

        $hasilSimpan = DB::transaction(
            function () use ($validated, $dinas) {
                $hasil = [];

                foreach ($validated['data'] as $index => $item) {
                    $isManual = (bool) $item['is_manual'];

                    $realisasiId = $item['realisasi_ka_id'] ?? null;

                    if ($isManual) {
                        /*
                        |--------------------------------------------------------------------------
                        | PROSES KA MANUAL
                        |--------------------------------------------------------------------------
                        */

                        $nomorKA = trim($item['nomor']);
                        $namaKA = trim($item['nama'] ?? '');

                        /*
                        | Cari atau buat data KA berdasarkan nomor KA.
                        */

                        $kereta = KeretaApi::firstOrCreate(
                            [
                                'nomor_ka' => $nomorKA,
                            ],
                            [
                                'nama_ka' => $namaKA !== ''
                                    ? $namaKA
                                    : null,
                            ]
                        );

                        /*
                        | Perbarui nama KA apabila sebelumnya kosong.
                        */

                        if (empty($kereta->nama_ka) && $namaKA !== '') {
                            $kereta->nama_ka = $namaKA;
                            $kereta->save();
                        }

                        /*
                        | Jika ID realisasi tersedia, pastikan ID tersebut
                        | merupakan KA manual milik dinas aktif.
                        */

                        if ($realisasiId) {
                            $realisasi = RealisasiKa::where(
                                'id',
                                $realisasiId
                            )
                                ->where('dinas_id', $dinas->id)
                                ->where('is_manual', true)
                                ->whereNull('jadwal_ka_id')
                                ->first();

                            if (! $realisasi) {
                                throw ValidationException::withMessages([
                                    'data' => 'Realisasi KA manual tidak valid atau bukan milik dinas aktif.',
                                ]);
                            }
                        } else {
                            /*
                            | Gunakan realisasi manual yang cocok
                            | jika sudah ada agar tidak duplikat.
                            */

                            $realisasi = RealisasiKa::where(
                                'dinas_id',
                                $dinas->id
                            )
                                ->where('is_manual', true)
                                ->where('kereta_api_id', $kereta->id)
                                ->whereNull('jadwal_ka_id')
                                ->first();

                            if (! $realisasi) {
                                $realisasi = new RealisasiKa;

                                $realisasi->dinas_id = $dinas->id;
                                $realisasi->jadwal_ka_id = null;
                                $realisasi->is_manual = true;
                            }
                        }

                        $realisasi->kereta_api_id = $kereta->id;

                        // Simpan detail perjalanan KA manual.
                        $realisasi->hulu = $item['hulu'] ?? null;
                        $realisasi->hilir = $item['hilir'] ?? null;
                        $realisasi->relasi = $item['relasi'] ?? null;
                    } else {
                        /*
                        |--------------------------------------------------------------------------
                        | PROSES KA TERJADWAL
                        |--------------------------------------------------------------------------
                        |
                        | Validasi jadwal terhadap JPL, tanggal,
                        | dan hari dinas aktif.
                        |
                        */

                        $jadwalId = $item['jadwal_ka_id'] ?? null;

                        if (! $jadwalId) {
                            throw ValidationException::withMessages([
                                'data' => 'ID jadwal KA wajib diisi untuk KA terjadwal.',
                            ]);
                        }

                        $snapshot = $dinas->jadwal_ka_snapshot;
                        $snapshotSchedules = is_array($snapshot) && is_array($snapshot['jadwal'] ?? null)
                            ? $snapshot['jadwal']
                            : [];
                        $jadwal = collect($snapshotSchedules)->first(
                            fn ($schedule) => is_array($schedule)
                                && isset($schedule['jadwal_ka_id'])
                                && (int) $schedule['jadwal_ka_id'] === (int) $jadwalId
                                && (int) ($schedule['jpl_id'] ?? 0) === (int) $dinas->jpl_id
                        );

                        if (! $jadwal) {
                            throw ValidationException::withMessages([
                                'data' => 'Jadwal KA tidak terdapat dalam snapshot Dinas yang sedang berjalan.',
                            ]);
                        }

                        /*
                        | Jika ID realisasi tersedia, pastikan cocok
                        | dengan jadwal dan dinas.
                        */

                        if ($realisasiId) {
                            $realisasi = RealisasiKa::where(
                                'id',
                                $realisasiId
                            )
                                ->where('dinas_id', $dinas->id)
                                ->where('jadwal_ka_id', $jadwal['jadwal_ka_id'])
                                ->where('is_manual', false)
                                ->first();

                            if (! $realisasi) {
                                throw ValidationException::withMessages([
                                    'data' => 'ID realisasi tidak cocok dengan jadwal KA atau dinas aktif.',
                                ]);
                            }
                        } else {
                            /*
                            | Gunakan realisasi yang sudah ada agar
                            | penyimpanan ulang tidak membuat duplikat.
                            */

                            $realisasi = RealisasiKa::firstOrNew([
                                'dinas_id' => $dinas->id,
                                'jadwal_ka_id' => $jadwal['jadwal_ka_id'],
                                'is_manual' => false,
                            ]);
                        }

                        $realisasi->kereta_api_id = $jadwal['kereta_api_id'] ?? null;
                        $realisasi->is_manual = false;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | STATUS PERJALANAN
                    |--------------------------------------------------------------------------
                    */

                    $realisasi->status_lewat = match ($item['status']) {
                        'JALAN' => 'centang',
                        'BATAL' => 'silang',
                        default => null,
                    };

                    /*
                    |--------------------------------------------------------------------------
                    | JAM AKTUAL
                    |--------------------------------------------------------------------------
                    */

                    $realisasi->jam_aktual = ! empty($item['aktual'])
                        ? $item['aktual']
                        : null;

                    /*
                    |--------------------------------------------------------------------------
                    | INDIKATOR KESELAMATAN
                    |--------------------------------------------------------------------------
                    */

                    $realisasi->status_s21 = $this->databaseCheck(
                        $item['semboyan21'] ?? null
                    );

                    $realisasi->status_genta = $this->databaseCheck(
                        $item['genta'] ?? null
                    );

                    $realisasi->status_pintu_perlintasan = $this->databaseCheck(
                        $item['pintu'] ?? null
                    );

                    $realisasi->status_s35 = $this->databaseCheck(
                        $item['semboyan35'] ?? null
                    );

                    $realisasi->catatan = $item['keterangan'] ?? null;
                    $realisasi->diedit_terakhir_pada = now();

                    $realisasi->save();

                    /*
                    |--------------------------------------------------------------------------
                    | KEMBALIKAN ID REALISASI YANG TERSIMPAN
                    |--------------------------------------------------------------------------
                    */

                    $hasil[] = [
                        'index' => $index,
                        'realisasi_ka_id' => $realisasi->id,
                    ];
                }

                return $hasil;
            }
        );

        return response()->json([
            'message' => 'Realisasi KA berhasil disimpan ke database.',
            'data' => $hasilSimpan,
        ]);
    }

    /**
     * Format status indikator untuk frontend.
     */
    private function checkValue(?string $value): string
    {
        return match ($value) {
            'centang' => 'yes',
            'silang' => 'no',
            default => '',
        };
    }

    /**
     * Format status indikator untuk database.
     */
    private function databaseCheck(?string $value): ?string
    {
        return match ($value) {
            'yes' => 'centang',
            'no' => 'silang',
            default => null,
        };
    }
}
