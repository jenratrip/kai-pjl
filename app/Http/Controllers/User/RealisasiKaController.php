<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\JadwalKa;
use App\Models\KeretaApi;
use App\Models\RealisasiKa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RealisasiKaController extends Controller
{
    /**
     * Menampilkan jadwal KA dan realisasi untuk dinas aktif.
     */
    public function index()
    {
        $dinas = Dinas::with([
            'jpl.resort.wilayah',
            'shift',
        ])
            ->where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->latest('id')
            ->first();

        $dataKA = [];

        if ($dinas) {
            /*
             * Jadwal khusus:
             * tanggal_berlaku harus sama dengan tanggal dinas.
             *
             * Jadwal rutin:
             * tanggal_berlaku harus NULL dan hari_berlaku
             * harus sama dengan hari dinas.
             */
            $jadwalQuery = JadwalKa::with('keretaApi')
                ->where('jpl_id', $dinas->jpl_id)
                ->where('is_active', true);

            $this->terapkanFilterJadwalBerlaku(
                $jadwalQuery,
                $dinas
            );

            /*
             * Dahulukan jadwal khusus pada tanggal tersebut.
             * Jika jadwal rutin dan khusus memiliki kereta serta
             * jam yang sama, jadwal khusus akan diprioritaskan.
             */
            $jadwalList = $jadwalQuery
                ->orderByRaw(
                    'CASE WHEN tanggal_berlaku IS NULL THEN 1 ELSE 0 END'
                )
                ->orderBy('jam_lewat')
                ->get();

            /*
             * Hindari menampilkan jadwal ganda untuk kereta dan
             * jam yang sama. Jadwal khusus diprioritaskan.
             */
            $jadwalList = $jadwalList
                ->unique(function ($jadwal) {
                    return ($jadwal->kereta_api_id ?? 'tanpa-ka')
                        . '|'
                        . ($jadwal->jam_lewat ?? 'tanpa-jam');
                })
                ->values();

            /*
             * Ambil realisasi yang sudah tersimpan.
             */
            $realisasiList = RealisasiKa::with('keretaApi')
                ->where('dinas_id', $dinas->id)
                ->where('is_manual', false)
                ->get()
                ->keyBy('jadwal_ka_id');

            /*
             * Gabungkan jadwal dengan realisasinya.
             */
            foreach ($jadwalList as $jadwal) {
                $realisasi = $realisasiList->get($jadwal->id);

                $dataKA[] = [
                    'no' => count($dataKA) + 1,

                    'nomor' => $jadwal->keretaApi?->nomor_ka ?? '-',
                    'nama' => $jadwal->keretaApi?->nama_ka ?? '-',

                    'jadwal' => $jadwal->jam_lewat
                        ? substr((string) $jadwal->jam_lewat, 0, 5)
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

                    'jadwal_ka_id' => $jadwal->id,
                    'realisasi_ka_id' => $realisasi?->id,
                    'is_manual' => false,
                ];
            }

            /*
             * Tambahkan KA manual yang telah tersimpan.
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

        
return view('user.realisasi-ka.index', [
    'dataKA' => $dataKA,
    'dinasAktif' => $dinas,
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
            * Pastikan dinas yang digunakan milik pengguna login
            * dan masih berstatus berlangsung.
            */
            $dinas = Dinas::where('user_id', auth()->id())
                ->where('status', 'berlangsung')
                ->latest('id')
                ->first();

            if (!$dinas) {
                return response()->json([
                    'message' => 'Tidak ada dinas aktif. Mulai dinas terlebih dahulu.',
                ], 422);
            }

            /*
            * Simpan seluruh realisasi dalam satu transaksi.
            * Setiap data mengembalikan index dan ID realisasi
            * agar ID tersebut dapat dipertahankan oleh frontend.
            */
            $hasilSimpan = DB::transaction(function () use ($validated, $dinas) {
                $hasil = [];

                foreach ($validated['data'] as $index => $item) {
                    $isManual = (bool) $item['is_manual'];

                    $realisasiId = $item['realisasi_ka_id'] ?? null;

                    if ($isManual) {
                        /*
                        * Cari atau buat data kereta berdasarkan nomor KA.
                        */
                        $nomorKA = trim($item['nomor']);
                        $namaKA = trim($item['nama'] ?? '');

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
                        * Perbarui nama KA apabila sebelumnya kosong.
                        */
                        if (empty($kereta->nama_ka) && $namaKA !== '') {
                            $kereta->nama_ka = $namaKA;
                            $kereta->save();
                        }

                        /*
                        * Jika frontend mengirim ID realisasi,
                        * pastikan ID tersebut merupakan KA manual
                        * milik dinas yang sedang berlangsung.
                        */
                        if ($realisasiId) {
                            $realisasi = RealisasiKa::where('id', $realisasiId)
                                ->where('dinas_id', $dinas->id)
                                ->where('is_manual', true)
                                ->whereNull('jadwal_ka_id')
                                ->first();

                            if (!$realisasi) {
                                throw ValidationException::withMessages([
                                    'data' => 'Realisasi KA manual tidak valid atau bukan milik dinas aktif.',
                                ]);
                            }
                        } else {
                            /*
                            * Jika belum memiliki ID, gunakan realisasi
                            * manual yang cocok jika sudah pernah tersimpan.
                            */
                            $realisasi = RealisasiKa::where(
                                'dinas_id',
                                $dinas->id
                            )
                                ->where('is_manual', true)
                                ->where('kereta_api_id', $kereta->id)
                                ->whereNull('jadwal_ka_id')
                                ->first();

                            if (!$realisasi) {
                                $realisasi = new RealisasiKa();

                                $realisasi->dinas_id = $dinas->id;
                                $realisasi->jadwal_ka_id = null;
                                $realisasi->is_manual = true;
                            }
                        }

                        /*
                        * Pastikan relasi kereta mengikuti data frontend.
                        */
                        $realisasi->kereta_api_id = $kereta->id;
                    } else {
                        /*
                        * Validasi jadwal terhadap JPL, tanggal,
                        * dan hari dinas aktif.
                        */
                        $jadwalId = $item['jadwal_ka_id'] ?? null;

                        if (!$jadwalId) {
                            throw ValidationException::withMessages([
                                'data' => 'ID jadwal KA wajib diisi untuk KA terjadwal.',
                            ]);
                        }

                        $jadwalQuery = JadwalKa::where('id', $jadwalId)
                            ->where('jpl_id', $dinas->jpl_id)
                            ->where('is_active', true);

                        $this->terapkanFilterJadwalBerlaku(
                            $jadwalQuery,
                            $dinas
                        );

                        $jadwal = $jadwalQuery->first();

                        if (!$jadwal) {
                            throw ValidationException::withMessages([
                                'data' => 'Jadwal KA tidak valid, tidak aktif, tidak sesuai hari/tanggal, atau bukan milik JPL dinas aktif.',
                            ]);
                        }

                        /*
                        * Jika ID realisasi tersedia, pastikan ID tersebut
                        * cocok dengan jadwal dan dinas yang bersangkutan.
                        */
                        if ($realisasiId) {
                            $realisasi = RealisasiKa::where('id', $realisasiId)
                                ->where('dinas_id', $dinas->id)
                                ->where('jadwal_ka_id', $jadwal->id)
                                ->where('is_manual', false)
                                ->first();

                            if (!$realisasi) {
                                throw ValidationException::withMessages([
                                    'data' => 'ID realisasi tidak cocok dengan jadwal KA atau dinas aktif.',
                                ]);
                            }
                        } else {
                            /*
                            * Gunakan realisasi yang sudah ada agar
                            * penyimpanan ulang tidak membuat duplikat.
                            */
                            $realisasi = RealisasiKa::firstOrNew([
                                'dinas_id' => $dinas->id,
                                'jadwal_ka_id' => $jadwal->id,
                                'is_manual' => false,
                            ]);
                        }

                        $realisasi->kereta_api_id = $jadwal->kereta_api_id;
                        $realisasi->is_manual = false;
                    }

                    /*
                    * Status perjalanan.
                    */
                    $realisasi->status_lewat = match ($item['status']) {
                        'JALAN' => 'centang',
                        'BATAL' => 'silang',
                        default => null,
                    };

                    /*
                    * Jam aktual.
                    */
                    $realisasi->jam_aktual = !empty($item['aktual'])
                        ? $item['aktual']
                        : null;

                    /*
                    * Indikator keselamatan.
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
                    * Kembalikan ID yang benar-benar tersimpan
                    * agar frontend dapat memperbarui dataKA.
                    */
                    $hasil[] = [
                        'index' => $index,
                        'realisasi_ka_id' => $realisasi->id,
                    ];
                }

                return $hasil;
            });

            return response()->json([
                'message' => 'Realisasi KA berhasil disimpan ke database.',
                'data' => $hasilSimpan,
            ]);
        }

            /**
             * Filter jadwal khusus dan jadwal rutin.
             *
             * Jadwal khusus: tanggal_berlaku sama dengan tanggal dinas.
             * Jadwal rutin: tanggal_berlaku NULL dan hari_berlaku cocok.
             */
            private function terapkanFilterJadwalBerlaku(
                $query,
                Dinas $dinas
            ): void {
                $tanggalDinas = Carbon::parse(
                    $dinas->tanggal_dinas
                );

                $namaHari = $this->namaHariIndonesia($tanggalDinas);

                $query->where(function ($q) use (
                    $dinas,
                    $namaHari
                ) {
                    $q->whereDate(
                        'tanggal_berlaku',
                        $dinas->tanggal_dinas
                    )->orWhere(function ($q2) use ($namaHari) {
                        $q2->whereNull('tanggal_berlaku')
                            ->whereRaw(
                                'LOWER(TRIM(hari_berlaku)) = ?',
                                [mb_strtolower($namaHari)]
                            );
                    });
                });
            }

    /**
     * Mengubah tanggal menjadi nama hari Indonesia.
     */
    private function namaHariIndonesia(Carbon $tanggal): string
    {
        $hari = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        return $hari[$tanggal->dayOfWeekIso];
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