<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\JadwalKa;
use App\Models\RealisasiKa;
use App\Models\SerahTerima;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SerahTerimaDinasController extends Controller
{
    /**
     * Menampilkan halaman serah terima dinas.
     */
    public function index()
    {
        $dinasAktif = Dinas::with([
            'user',
            'jpl.resort.wilayah',
            'shift',
        ])
            ->where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->latest('id')
            ->first();

        $dataRealisasiKA = collect();
        $serahTerima = null;

        if ($dinasAktif) {
            $dataRealisasiKA = $this->ambilDataKA($dinasAktif);

            $serahTerima = SerahTerima::where(
                'dinas_id',
                $dinasAktif->id
            )
                ->latest('id')
                ->first();
        }

        $ringkasanKA = [
            'batal' => $dataRealisasiKA
                ->where('status', 'BATAL')
                ->values()
                ->all(),

            'berjalan' => $dataRealisasiKA
                ->where('status', 'JALAN')
                ->values()
                ->all(),

            'belum' => $dataRealisasiKA
                ->where('status', 'BELUM')
                ->values()
                ->all(),
        ];

        return view('user.serah-terima-dinas', [
            'dinasAktif' => $dinasAktif,
            'dataRealisasiKA' => $dataRealisasiKA,
            'ringkasanKA' => $ringkasanKA,
            'serahTerima' => $serahTerima,
        ]);
    }

    /**
     * Mengambil jadwal dan realisasi KA untuk satu dinas.
     * Setiap jadwal perjalanan dihitung sebagai satu entri.
     */
    private function ambilDataKA(Dinas $dinas)
    {
        $jadwalQuery = JadwalKa::with('keretaApi')
            ->where('jpl_id', $dinas->jpl_id)
            ->where('is_active', true);

        $this->terapkanFilterJadwalBerlaku(
            $jadwalQuery,
            $dinas
        );

        $jadwalList = $jadwalQuery
            ->orderByRaw(
                'CASE WHEN tanggal_berlaku IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy('jam_lewat')
            ->orderBy('id')
            ->get();

        /*
         * Jadwal khusus diurutkan terlebih dahulu.
         * Duplikasi hanya dihapus jika KA dan jamnya sama.
         */
        $jadwalList = $jadwalList
            ->unique(function ($jadwal) {
                return ($jadwal->kereta_api_id ?? 'tanpa-ka')
                    . '|'
                    . ($jadwal->jam_lewat ?? 'tanpa-jam');
            })
            ->values();

        /*
         * Ambil realisasi terbaru untuk setiap jadwal.
         */
        $realisasiTerjadwal = RealisasiKa::with('keretaApi')
            ->where('dinas_id', $dinas->id)
            ->where('is_manual', false)
            ->orderByDesc('id')
            ->get()
            ->unique('jadwal_ka_id')
            ->keyBy('jadwal_ka_id');

        $dataKA = [];

        foreach ($jadwalList as $jadwal) {
            $realisasi = $realisasiTerjadwal->get($jadwal->id);

            $dataKA[] = [
                'id' => $realisasi?->id,
                'nomor' => $jadwal->keretaApi?->nomor_ka ?? '-',
                'nama' => $jadwal->keretaApi?->nama_ka ?? '-',
                'jadwal' => $this->formatJam($jadwal->jam_lewat),
                'aktual' => $this->formatJam($realisasi?->jam_aktual),
                'status' => $this->formatStatus($realisasi?->status_lewat),

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
                'is_manual' => false,
            ];
        }

        /*
         * Tambahkan KA manual tanpa menggabungkannya
         * dengan jadwal KA terjadwal.
         */
        $manualList = RealisasiKa::with('keretaApi')
            ->where('dinas_id', $dinas->id)
            ->where('is_manual', true)
            ->orderBy('id')
            ->get();

        foreach ($manualList as $realisasi) {
            $dataKA[] = [
                'id' => $realisasi->id,
                'nomor' => $realisasi->keretaApi?->nomor_ka ?? '-',
                'nama' => $realisasi->keretaApi?->nama_ka ?? '-',
                'jadwal' => '-',
                'aktual' => $this->formatJam($realisasi->jam_aktual),
                'status' => $this->formatStatus($realisasi->status_lewat),

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
                'is_manual' => true,
            ];
        }

        return collect($dataKA)->values();
    }

    /**
     * Memfilter jadwal khusus dan rutin berdasarkan tanggal dinas.
     */
    private function terapkanFilterJadwalBerlaku(
        $query,
        Dinas $dinas
    ): void {
        if (!$dinas->tanggal_dinas) {
            $query->whereRaw('1 = 0');

            return;
        }

        $tanggalDinas = Carbon::parse($dinas->tanggal_dinas);

        $namaHari = mb_strtolower(
            $this->namaHariIndonesia($tanggalDinas)
        );

        /*
         * Kedua variabel yang dipakai closure
         * dimasukkan ke dalam use.
         */
        $query->where(function ($q) use (
            $tanggalDinas,
            $namaHari
        ) {
            $q->whereDate(
                'tanggal_berlaku',
                $tanggalDinas->toDateString()
            )->orWhere(function ($q2) use ($namaHari) {
                $q2->whereNull('tanggal_berlaku')
                    ->whereRaw(
                        'LOWER(TRIM(hari_berlaku)) = ?',
                        [$namaHari]
                    );
            });
        });
    }

    /**
     * Mengubah hari menjadi nama hari Indonesia.
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
     * Menyimpan atau memperbarui dokumen serah terima.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_serah_terima' => [
                'nullable',
                'date',
            ],

            'jam_serah_terima' => [
                'nullable',
                'date_format:H:i',
            ],

            'catatan_penting' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'ppka' => [
                'nullable',
                'string',
                'max:255',
            ],

            'nama_penerima' => [
                'nullable',
                'string',
                'max:255',
            ],

            'nip_penerima' => [
                'nullable',
                'string',
                'max:50',
            ],

            'nip_penyerah' => [
                'nullable',
                'string',
                'max:50',
            ],

            'ringkasan' => [
                'nullable',
                'array',
            ],

            'tanda_tangan_penyerah' => [
                'nullable',
                'string',
                'max:1500000',
            ],

            'tanda_tangan_penerima' => [
                'nullable',
                'string',
                'max:1500000',
            ],
        ]);

        /*
         * Pastikan dinas aktif merupakan milik pengguna login.
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

        $tanggalSerahTerima =
            $validated['tanggal_serah_terima']
            ?? now('Asia/Jakarta')->format('Y-m-d');

        $jamSerahTerima =
            $validated['jam_serah_terima']
            ?? now('Asia/Jakarta')->format('H:i');

        $waktuSerahTerima = Carbon::createFromFormat(
            '!Y-m-d H:i',
            $tanggalSerahTerima . ' ' . $jamSerahTerima,
            'Asia/Jakarta'
        );

        $signaturePenyerah = null;
        $signaturePenerima = null;

        /*
         * Cari dokumen yang sudah ada sebelum menyimpan tanda tangan.
         * Dokumen terbaru untuk dinas ini akan diperbarui.
         */
        $serahTerimaLama = SerahTerima::where(
            'dinas_id',
            $dinas->id
        )
            ->latest('id')
            ->first();

        $tandaTanganPenyerahLama =
            $serahTerimaLama?->tanda_tangan_penyerah;

        $tandaTanganPenerimaLama =
            $serahTerimaLama?->tanda_tangan_penerima;

        try {
            $signaturePenyerah = $this->saveSignature(
                $validated['tanda_tangan_penyerah'] ?? null,
                'penyerah'
            );

            $signaturePenerima = $this->saveSignature(
                $validated['tanda_tangan_penerima'] ?? null,
                'penerima'
            );

            /*
             * Ambil data KA dari database agar ringkasan
             * tidak bergantung pada daftar KA dari browser.
             */
            $dataKA = $this->ambilDataKA($dinas)->all();

            $ringkasanMasuk = $validated['ringkasan'] ?? [];

            $ringkasan = array_merge(
                $ringkasanMasuk,
                [
                    'kereta_api' => $dataKA,

                    'catatan_penting' =>
                        $validated['catatan_penting'] ?? '',

                    'ppka' =>
                        $validated['ppka'] ?? '',

                    'nama_penerima' =>
                        $validated['nama_penerima'] ?? '',

                    'nip_penerima' =>
                        $validated['nip_penerima'] ?? '',

                    'nip_penyerah' =>
                        $validated['nip_penyerah'] ?? '',
                ]
            );

            $serahTerima = DB::transaction(
                function () use (
                    $dinas,
                    $ringkasan,
                    $signaturePenyerah,
                    $signaturePenerima,
                    $waktuSerahTerima,
                    $serahTerimaLama,
                    $tandaTanganPenyerahLama,
                    $tandaTanganPenerimaLama
                ) {
                    /*
                     * Pertahankan tanda tangan lama jika
                     * tanda tangan baru tidak dikirim.
                     */
                    $penyerahFinal = $signaturePenyerah
                        ?? $tandaTanganPenyerahLama;

                    $penerimaFinal = $signaturePenerima
                        ?? $tandaTanganPenerimaLama;

                    $tandaTanganLengkap =
                        !empty($penyerahFinal)
                        && !empty($penerimaFinal);

                    $dataSimpan = [
                        'waktu_serah_terima' => $waktuSerahTerima,
                        'ringkasan' => $ringkasan,

                        'tanda_tangan_penyerah' =>
                            $penyerahFinal,

                        'tanda_tangan_penerima' =>
                            $penerimaFinal,

                        'status' => $tandaTanganLengkap
                            ? 'selesai'
                            : 'draft',
                    ];

                    if ($serahTerimaLama) {
                        $serahTerimaLama->update($dataSimpan);

                        return $serahTerimaLama->fresh();
                    }

                    return SerahTerima::create(array_merge(
                        $dataSimpan,
                        [
                            'dinas_id' => $dinas->id,
                            'dinas_penerima_id' => null,
                        ]
                    ));
                }
            );

            /*
             * Hapus tanda tangan lama setelah penyimpanan berhasil
             * dan hanya jika tanda tangan tersebut diganti.
             */
            if (
                $signaturePenyerah
                && $tandaTanganPenyerahLama
                && $signaturePenyerah !== $tandaTanganPenyerahLama
            ) {
                Storage::disk('public')->delete(
                    $tandaTanganPenyerahLama
                );
            }

            if (
                $signaturePenerima
                && $tandaTanganPenerimaLama
                && $signaturePenerima !== $tandaTanganPenerimaLama
            ) {
                Storage::disk('public')->delete(
                    $tandaTanganPenerimaLama
                );
            }
        } catch (\Throwable $e) {
            /*
             * Hapus file tanda tangan baru jika proses gagal.
             */
            if ($signaturePenyerah) {
                Storage::disk('public')->delete(
                    $signaturePenyerah
                );
            }

            if ($signaturePenerima) {
                Storage::disk('public')->delete(
                    $signaturePenerima
                );
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Dokumen serah terima berhasil disimpan.',
            'id' => $serahTerima->id,
            'status' => $serahTerima->status,
        ], 200);
    }

    /**
     * Mengubah status database menjadi status tampilan.
     */
    private function formatStatus(?string $status): string
    {
        return match ($status) {
            'centang' => 'JALAN',
            'silang' => 'BATAL',
            default => 'BELUM',
        };
    }

    /**
     * Mengubah indikator database menjadi nilai frontend.
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
     * Memformat jam menjadi HH:MM.
     */
    private function formatJam($jam): string
    {
        if (!$jam) {
            return '-';
        }

        return substr((string) $jam, 0, 5);
    }

    /**
     * Memvalidasi dan menyimpan tanda tangan PNG.
     */
    private function saveSignature(
        ?string $dataUrl,
        string $jenis
    ): ?string {
        if (empty($dataUrl)) {
            return null;
        }

        if (!preg_match(
            '/^data:image\/png;base64,([A-Za-z0-9+\/=\r\n]+)$/',
            $dataUrl,
            $matches
        )) {
            throw ValidationException::withMessages([
                'tanda_tangan_' . $jenis =>
                    'Format tanda tangan harus berupa gambar PNG.',
            ]);
        }

        $image = base64_decode($matches[1], true);

        if (
            $image === false
            || strlen($image) > 1024 * 1024
            || !str_starts_with($image, "\x89PNG\r\n\x1a\n")
        ) {
            throw ValidationException::withMessages([
                'tanda_tangan_' . $jenis =>
                    'Gambar tanda tangan tidak valid atau melebihi 1 MB.',
            ]);
        }

        $path = 'serah-terima/'
            . auth()->id()
            . '/'
            . uniqid($jenis . '_', true)
            . '.png';

        $berhasil = Storage::disk('public')->put(
            $path,
            $image
        );

        if (!$berhasil) {
            throw new \RuntimeException(
                'Gagal menyimpan tanda tangan ke storage.'
            );
        }

        return $path;
    }
}