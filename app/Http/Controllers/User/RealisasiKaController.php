<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\JadwalKa;
use App\Models\KeretaApi;
use App\Models\RealisasiKa;
use App\Models\RealisasiKaSetting;
use App\Services\ActivityLogService;
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
                'jpl.resort',
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
            $jadwalIds = collect($jadwalList)
                ->filter(fn ($jadwal) => is_array($jadwal) && isset($jadwal['jadwal_ka_id']))
                ->pluck('jadwal_ka_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values();
            $jadwalKeterangan = $jadwalIds->isEmpty()
                ? collect()
                : JadwalKa::query()
                    ->whereIn('id', $jadwalIds)
                    ->pluck('keterangan', 'id');

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
                    'is_bersamaan' => $this->isBersamaanSchedule(
                        $jadwalKeterangan->get($jadwalId)
                    ),

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
                        'is_bersamaan' => $this->isBersamaanSchedule(
                            $jadwal?->keterangan
                        ),
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
                    'is_bersamaan' => false,

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
        | - jpl.resort
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
    public function store(Request $request, ActivityLogService $activityLogService)
    {
        $validated = $request->validate([
            'save_mode' => [
                'nullable',
                Rule::in(['row', 'batch']),
            ],

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

        $saveMode = $validated['save_mode'] ?? (count($validated['data']) > 1 ? 'batch' : 'row');

        $hasilSimpan = DB::transaction(
            function () use ($validated, $dinas, $saveMode, $activityLogService) {
                $hasil = [];
                $changedRows = [];

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

                        $oldValues = $realisasi->exists
                            ? $this->activityValues($realisasi)
                            : null;

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

                        $oldValues = $realisasi->exists
                            ? $this->activityValues($realisasi)
                            : null;

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

                    $changedFields = array_values(array_diff(
                        array_keys($realisasi->getDirty()),
                        ['created_at', 'updated_at', 'diedit_terakhir_pada']
                    ));

                    $realisasi->save();

                    if ($changedFields !== []) {
                        $newValues = $this->activityValues($realisasi);
                        $newValues['changed_fields'] = $changedFields;
                        $indicatorFields = [
                            'status_s21',
                            'status_genta',
                            'status_pintu_perlintasan',
                            'status_s35',
                        ];
                        $oldHadStatusOrIndicator = $oldValues !== null && (
                            $oldValues['status_lewat'] !== null
                            || collect($indicatorFields)->contains(
                                fn (string $field): bool => $oldValues[$field] !== null
                            )
                        );
                        $newHasStatusOrIndicator = $newValues['status_lewat'] !== null
                            || collect($indicatorFields)->contains(
                                fn (string $field): bool => $newValues[$field] !== null
                            );
                        $newValues['status_reset'] = $oldHadStatusOrIndicator
                            && ! $newHasStatusOrIndicator;

                        $changedRows[] = [
                            'old' => $oldValues,
                            'new' => $newValues,
                        ];

                        if ($saveMode === 'row') {
                            $activityLogService->log(
                                $oldValues === null ? 'create' : 'update',
                                'realisasi_ka',
                                'Realisasi KA berhasil disimpan per baris.',
                                $realisasi,
                                $oldValues,
                                $newValues
                            );
                        }
                    }

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

                if ($saveMode === 'batch' && $changedRows !== []) {
                    $indicatorFields = [
                        'status_s21',
                        'status_genta',
                        'status_pintu_perlintasan',
                        'status_s35',
                    ];
                    $activitySummary = [
                        'submitted_count' => count($validated['data']),
                        'updated_count' => count($changedRows),
                        'jalan_count' => 0,
                        'batal_count' => 0,
                        'status_reset_count' => 0,
                        'indicator_rows_changed_count' => 0,
                    ];

                    foreach ($changedRows as $changedRow) {
                        $oldStatus = $changedRow['old']['status_lewat'] ?? null;
                        $newStatus = $changedRow['new']['status_lewat'];

                        if ($oldStatus !== $newStatus) {
                            if ($newStatus === 'centang') {
                                $activitySummary['jalan_count']++;
                            } elseif ($newStatus === 'silang') {
                                $activitySummary['batal_count']++;
                            }
                        }

                        if ($changedRow['new']['status_reset']) {
                            $activitySummary['status_reset_count']++;
                        }

                        if (array_intersect($indicatorFields, $changedRow['new']['changed_fields']) !== []) {
                            $activitySummary['indicator_rows_changed_count']++;
                        }
                    }

                    $activityLogService->log(
                        'bulk_update',
                        'realisasi_ka',
                        'Realisasi KA berhasil disimpan melalui Simpan Semua.',
                        $dinas,
                        null,
                        $activitySummary
                    );
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
     * @return array<string, bool|int|string|null>
     */
    private function activityValues(RealisasiKa $realisasi): array
    {
        return [
            'dinas_id' => (int) $realisasi->dinas_id,
            'jadwal_ka_id' => $realisasi->jadwal_ka_id === null
                ? null
                : (int) $realisasi->jadwal_ka_id,
            'kereta_api_id' => $realisasi->kereta_api_id === null
                ? null
                : (int) $realisasi->kereta_api_id,
            'is_manual' => (bool) $realisasi->is_manual,
            'status_lewat' => $realisasi->status_lewat,
            'jam_aktual' => $realisasi->jam_aktual,
            'status_s21' => $realisasi->status_s21,
            'status_genta' => $realisasi->status_genta,
            'status_pintu_perlintasan' => $realisasi->status_pintu_perlintasan,
            'status_s35' => $realisasi->status_s35,
        ];
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

    private function isBersamaanSchedule(?string $description): bool
    {
        return is_string($description)
            && str_contains(mb_strtolower($description), 'bersamaan');
    }
}
