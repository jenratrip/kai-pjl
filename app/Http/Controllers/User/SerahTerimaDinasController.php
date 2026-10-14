<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CatatanDinas;
use App\Models\Dinas;
use App\Models\Inventaris;
use App\Models\JadwalKa;
use App\Models\RealisasiInventaris;
use App\Models\RealisasiKa;
use App\Models\SerahTerima;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SerahTerimaDinasController extends Controller
{
    /**
     * Mendapatkan dinas aktif milik pengguna yang login.
     */
    private function dinasAktif(): ?Dinas
    {
        return Dinas::with([
            'user',
            'jpl.resort.wilayah',
            'shift',
        ])
            ->where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->latest('id')
            ->first();
    }

    /**
     * Menampilkan halaman serah terima dinas.
     */
    public function index()
    {
        $dinasAktif = $this->dinasAktif();

        $admins = User::whereIn('role_id', [2, 3])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'nip', 'role_id']);

        $dataRealisasiKA = collect();

        $ringkasanKA = [
            'batal' => [],
            'berjalan' => [],
            'belum' => [],
        ];

        $catatan = collect();
        $inventaris = collect();
        $realisasi = collect();
        $serahTerima = null;

        if ($dinasAktif) {
            $dataRealisasiKA = $this->ambilDataKA($dinasAktif);
            $ringkasanKA = $this->buatRingkasanKA($dataRealisasiKA);

            $catatan = CatatanDinas::with([
                'attachments',
                'user',
            ])
                ->where('dinas_id', $dinasAktif->id)
                ->latest('waktu_catatan')
                ->get();

            $inventaris = Inventaris::where('is_active', true)
                ->where('jpl_id', $dinasAktif->jpl_id)
                ->orderBy('nama_barang')
                ->get();

            $realisasi = RealisasiInventaris::where(
                'dinas_id',
                $dinasAktif->id
            )
                ->get()
                ->keyBy('inventaris_id');

            $serahTerima = SerahTerima::with('penerima')
                ->where('dinas_id', $dinasAktif->id)
                ->latest('id')
                ->first();
        }

        return view('user.serahterimacatatan.index', [
            'dinasAktif' => $dinasAktif,
            'dinas' => $dinasAktif,
            'admins' => $admins,
            'catatan' => $catatan,
            'inventaris' => $inventaris,
            'realisasi' => $realisasi,
            'serahTerima' => $serahTerima,
            'dataRealisasiKA' => $dataRealisasiKA,
            'ringkasanKA' => $ringkasanKA,
        ]);
    }

    /**
     * Mengelompokkan KA berdasarkan status realisasi.
     */
    private function buatRingkasanKA($dataRealisasiKA): array
    {
        $dataRealisasiKA = collect($dataRealisasiKA);

        return [
            'batal' => $dataRealisasiKA
                ->filter(
                    fn ($ka) => strtoupper($ka['status'] ?? '') === 'BATAL'
                )
                ->values()
                ->all(),

            'berjalan' => $dataRealisasiKA
                ->filter(
                    fn ($ka) => strtoupper($ka['status'] ?? '') === 'JALAN'
                )
                ->values()
                ->all(),

            'belum' => $dataRealisasiKA
                ->filter(
                    fn ($ka) => strtoupper($ka['status'] ?? '') === 'BELUM'
                )
                ->values()
                ->all(),
        ];
    }

    /**
     * Mengubah daftar KA menjadi teks.
     * Hanya menampilkan nomor dan nama KA.
     */
    private function daftarKAKeTeks(array $daftar): string
    {
        if (empty($daftar)) {
            return 'Tidak ada';
        }

        return collect($daftar)
            ->map(function ($ka) {
                $nomor = trim((string) ($ka['nomor'] ?? '-'));
                $nama = trim((string) ($ka['nama'] ?? ''));

                if ($nomor === '' || $nomor === '-') {
                    return $nama !== '' && $nama !== '-'
                        ? $nama
                        : '-';
                }

                if ($nama !== '' && $nama !== '-') {
                    return $nomor . ' - ' . $nama;
                }

                return $nomor;
            })
            ->unique()
            ->implode("\n");
    }

    /**
     * Mengambil jadwal KA dan realisasi milik dinas aktif.
     */
    private function ambilDataKA(Dinas $dinas)
    {
        $jadwalQuery = JadwalKa::with('keretaApi')
            ->where('jpl_id', $dinas->jpl_id)
            ->where('is_active', true);

        $this->terapkanFilterJadwalBerlaku($jadwalQuery, $dinas);

        $jadwalList = $jadwalQuery
            ->orderByRaw(
                'CASE WHEN tanggal_berlaku IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy('jam_lewat')
            ->orderBy('id')
            ->get()
            ->unique(function ($jadwal) {
                return ($jadwal->kereta_api_id ?? 'tanpa-ka')
                    . '|'
                    . ($jadwal->jam_lewat ?? 'tanpa-jam');
            })
            ->values();

        $realisasiTerjadwal = RealisasiKa::with('keretaApi')
            ->where('dinas_id', $dinas->id)
            ->where('is_manual', false)
            ->orderByDesc('id')
            ->get()
            ->unique('jadwal_ka_id')
            ->keyBy('jadwal_ka_id');

        $dataKA = [];

        foreach ($jadwalList as $jadwal) {
            $realisasiKA = $realisasiTerjadwal->get($jadwal->id);

            $dataKA[] = [
                'id' => $realisasiKA?->id,
                'nomor' => $jadwal->keretaApi?->nomor_ka ?? '-',
                'nama' => $jadwal->keretaApi?->nama_ka ?? '-',
                'jadwal' => $this->formatJam($jadwal->jam_lewat),
                'aktual' => $this->formatJam($realisasiKA?->jam_aktual),
                'status' => $this->formatStatus(
                    $realisasiKA?->status_lewat
                ),
                'semboyan21' => $this->checkValue(
                    $realisasiKA?->status_s21
                ),
                'genta' => $this->checkValue(
                    $realisasiKA?->status_genta
                ),
                'pintu' => $this->checkValue(
                    $realisasiKA?->status_pintu_perlintasan
                ),
                'semboyan35' => $this->checkValue(
                    $realisasiKA?->status_s35
                ),
                'keterangan' => $realisasiKA?->catatan ?? '',
                'jadwal_ka_id' => $jadwal->id,
                'is_manual' => false,
            ];
        }

        // KA manual tetap disertakan.
        $manualList = RealisasiKa::with('keretaApi')
            ->where('dinas_id', $dinas->id)
            ->where('is_manual', true)
            ->orderBy('id')
            ->get();

        foreach ($manualList as $realisasiKA) {
            $dataKA[] = [
                'id' => $realisasiKA->id,
                'nomor' => $realisasiKA->keretaApi?->nomor_ka ?? '-',
                'nama' => $realisasiKA->keretaApi?->nama_ka ?? '-',
                'jadwal' => '-',
                'aktual' => $this->formatJam($realisasiKA->jam_aktual),
                'status' => $this->formatStatus(
                    $realisasiKA->status_lewat
                ),
                'semboyan21' => $this->checkValue(
                    $realisasiKA->status_s21
                ),
                'genta' => $this->checkValue(
                    $realisasiKA->status_genta
                ),
                'pintu' => $this->checkValue(
                    $realisasiKA->status_pintu_perlintasan
                ),
                'semboyan35' => $this->checkValue(
                    $realisasiKA->status_s35
                ),
                'keterangan' => $realisasiKA->catatan ?? '',
                'jadwal_ka_id' => null,
                'is_manual' => true,
            ];
        }

        return collect($dataKA)->values();
    }

    /**
     * Memfilter jadwal berdasarkan tanggal atau hari berlakunya.
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

        $query->where(function ($q) use ($tanggalDinas, $namaHari) {
            $q->whereDate(
                'tanggal_berlaku',
                $tanggalDinas->toDateString()
            )
                ->orWhere(function ($q2) use ($namaHari) {
                    $q2->whereNull('tanggal_berlaku')
                        ->where(function ($hariQuery) use ($namaHari) {
                            $hariQuery
                                ->whereRaw(
                                    'LOWER(TRIM(hari_berlaku)) = ?',
                                    [$namaHari]
                                )
                                ->orWhereRaw(
                                    "LOWER(TRIM(hari_berlaku)) = 'setiap hari'"
                                );
                        });
                });
        });
    }

    private function namaHariIndonesia(Carbon $tanggal): string
    {
        return [
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
            7 => 'minggu',
        ][$tanggal->dayOfWeekIso];
    }

    /**
     * Menyimpan catatan dinas.
     */
    public function storeCatatan(Request $request)
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'string', 'max:100'],
            'isi_catatan' => ['required', 'string', 'max:10000'],
            'tindak_lanjut' => ['nullable', 'string', 'max:5000'],
        ]);

        $dinas = $this->dinasAktif();

        if (!$dinas) {
            return back()
                ->withErrors([
                    'dinas' => 'Tidak ada dinas aktif. Catatan tidak dapat disimpan.',
                ])
                ->withInput();
        }

        CatatanDinas::create([
            'dinas_id' => $dinas->id,
            'user_id' => auth()->id(),
            'judul' => $validated['judul'],
            'kategori' => $validated['kategori'],
            'isi_catatan' => $validated['isi_catatan'],
            'status' => 'baru',
            'tindak_lanjut' => $validated['tindak_lanjut'] ?? null,
            'waktu_catatan' => now(),
        ]);

        return back()->with(
            'success',
            'Catatan dinas berhasil disimpan.'
        );
    }

    /**
     * Menyimpan hasil pemeriksaan inventaris.
     */
    public function storeInventaris(Request $request)
    {
        $validated = $request->validate([
            'inventaris_id' => [
                'required',
                'integer',
                'exists:inventaris,id',
            ],
            'kondisi' => [
                'required',
                Rule::in([
                    'baik',
                    'perlu_perbaikan',
                    'rusak',
                    'hilang',
                ]),
            ],
            'jumlah_aktual' => [
                'required',
                'integer',
                'min:0',
            ],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $dinas = $this->dinasAktif();

        if (!$dinas) {
            return back()
                ->withErrors([
                    'dinas' => 'Tidak ada dinas aktif untuk pemeriksaan inventaris.',
                ])
                ->withInput();
        }

        $inventaris = Inventaris::whereKey(
            $validated['inventaris_id']
        )
            ->where('is_active', true)
            ->firstOrFail();

        abort_unless(
            (int) $inventaris->jpl_id === (int) $dinas->jpl_id,
            403,
            'Inventaris ini bukan bagian dari penempatan JPL dinas Anda.'
        );

        $jumlahAktual = (int) $validated['jumlah_aktual'];
        $jumlahMaster = (int) $inventaris->jumlah;

        $adaMasalah = $validated['kondisi'] !== 'baik'
            || $jumlahAktual !== $jumlahMaster;

        DB::transaction(function () use (
            $dinas,
            $inventaris,
            $validated,
            $jumlahAktual,
            $adaMasalah
        ) {
            RealisasiInventaris::updateOrCreate(
                [
                    'dinas_id' => $dinas->id,
                    'inventaris_id' => $inventaris->id,
                ],
                [
                    'status' => $adaMasalah ? 'silang' : 'centang',
                    'kondisi' => $validated['kondisi'],
                    'jumlah_aktual' => $jumlahAktual,
                    'catatan' => $validated['catatan'] ?? null,
                    'diperiksa_pada' => now(),
                    'diperiksa_oleh' => auth()->id(),
                ]
            );

            $inventaris->jumlah_aktual_terakhir = $jumlahAktual;
            $inventaris->kondisi_terakhir = $validated['kondisi'];

            if ($adaMasalah) {
                if (
                    !$inventaris->dilaporkan_pada
                    || $inventaris->status_tinjauan === 'normal'
                ) {
                    $inventaris->status_tinjauan = 'perlu_tinjauan';
                    $inventaris->catatan_masalah =
                        $validated['catatan']
                        ?: 'Kondisi atau jumlah aktual berbeda dari data inventaris.';
                    $inventaris->dilaporkan_pada = now();
                    $inventaris->dilaporkan_oleh = auth()->id();
                } else {
                    $inventaris->status_tinjauan =
                        $inventaris->status_tinjauan ?: 'perlu_tinjauan';
                }
            }

            $inventaris->save();
        });

        return back()->with(
            'success',
            $adaMasalah
                ? 'Pemeriksaan tersimpan. Inventaris memerlukan perhatian dan menunggu tinjauan admin.'
                : 'Pemeriksaan inventaris berhasil disimpan.'
        );
    }

    /**
     * Menyimpan atau memperbarui serah terima dinas.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'penerima_user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query
                        ->whereIn('role_id', [2, 3])
                        ->where('is_active', true)
                ),
            ],

            'ringkasan' => ['required', 'string', 'max:10000'],
            'catatan_penting' => ['nullable', 'string', 'max:10000'],
            'ppka' => ['nullable', 'string', 'max:255'],
            'nama_penerima' => ['nullable', 'string', 'max:255'],
            'nip_penerima' => ['nullable', 'string', 'max:50'],
            'nip_penyerah' => ['nullable', 'string', 'max:50'],

            'tanda_tangan_penyerah' => [
                'nullable',
                'string',
                'max:1500000',
                'required_if:tanda_tangan_penyerah_action,set',
            ],

            'tanda_tangan_penerima' => [
                'nullable',
                'string',
                'max:1500000',
                'required_if:tanda_tangan_penerima_action,set',
            ],

            'tanda_tangan_penyerah_action' => [
                'nullable',
                Rule::in(['keep', 'set', 'clear']),
            ],

            'tanda_tangan_penerima_action' => [
                'nullable',
                Rule::in(['keep', 'set', 'clear']),
            ],
        ]);

        $dinas = $this->dinasAktif();

        if (!$dinas) {
            return back()
                ->withErrors([
                    'dinas' => 'Tidak ada dinas aktif untuk diserahterimakan.',
                ])
                ->withInput();
        }

        $ringkasanMasuk = json_decode(
            $validated['ringkasan'],
            true
        );

        if (!is_array($ringkasanMasuk)) {
            return back()
                ->withErrors([
                    'ringkasan' => 'Format ringkasan serah terima tidak valid.',
                ])
                ->withInput();
        }

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

        $actionPenyerah = $validated['tanda_tangan_penyerah_action']
            ?? (!empty($validated['tanda_tangan_penyerah'])
                ? 'set'
                : 'keep');

        $actionPenerima = $validated['tanda_tangan_penerima_action']
            ?? (!empty($validated['tanda_tangan_penerima'])
                ? 'set'
                : 'keep');

        $signaturePenyerah = null;
        $signaturePenerima = null;

        try {
            if ($actionPenyerah === 'set') {
                $signaturePenyerah = $this->saveSignature(
                    $validated['tanda_tangan_penyerah'] ?? null,
                    'penyerah'
                );
            }

            if ($actionPenerima === 'set') {
                $signaturePenerima = $this->saveSignature(
                    $validated['tanda_tangan_penerima'] ?? null,
                    'penerima'
                );
            }

            // Ambil data KA terbaru dari database.
            $dataKA = $this->ambilDataKA($dinas)->all();
            $ringkasanKA = $this->buatRingkasanKA($dataKA);

            $ringkasan = array_merge(
                $ringkasanMasuk,
                [
                    'ka_batal' => $this->daftarKAKeTeks(
                        $ringkasanKA['batal']
                    ),
                    'ka_berjalan' => $this->daftarKAKeTeks(
                        $ringkasanKA['berjalan']
                    ),
                    'ka_belum_lewat' => $this->daftarKAKeTeks(
                        $ringkasanKA['belum']
                    ),
                    'kereta_api' => $dataKA,
                    'catatan_penting' => $validated['catatan_penting'] ?? '',
                    'ppka' => $validated['ppka'] ?? '',
                    'nama_penerima' => $validated['nama_penerima'] ?? '',
                    'nip_penerima' => $validated['nip_penerima'] ?? '',
                    'nip_penyerah' => $validated['nip_penyerah'] ?? '',
                ]
            );

            DB::transaction(function () use (
                $dinas,
                $validated,
                $ringkasan,
                $signaturePenyerah,
                $signaturePenerima,
                $actionPenyerah,
                $actionPenerima,
                $serahTerimaLama,
                $tandaTanganPenyerahLama,
                $tandaTanganPenerimaLama
            ) {
                $penyerahFinal = match ($actionPenyerah) {
                    'clear' => null,
                    'set' => $signaturePenyerah,
                    default => $tandaTanganPenyerahLama,
                };

                $penerimaFinal = match ($actionPenerima) {
                    'clear' => null,
                    'set' => $signaturePenerima,
                    default => $tandaTanganPenerimaLama,
                };

                $tandaTanganLengkap =
                    !empty($penyerahFinal)
                    && !empty($penerimaFinal);

                $dataSimpan = [
                    'dinas_id' => $dinas->id,
                    'dinas_penerima_id' => null,
                    'penerima_user_id' => $validated['penerima_user_id'],
                    'waktu_serah_terima' => now(),
                    'ringkasan' => $ringkasan,
                    'tanda_tangan_penyerah' => $penyerahFinal,
                    'tanda_tangan_penerima' => $penerimaFinal,
                    'status' => $tandaTanganLengkap
                        ? 'selesai'
                        : (!empty($penyerahFinal)
                            ? 'menunggu_penerima'
                            : 'draft'),
                ];

                if ($serahTerimaLama) {
                    $serahTerimaLama->update($dataSimpan);
                } else {
                    SerahTerima::create($dataSimpan);
                }
            });

            // Hapus file tanda tangan lama setelah penyimpanan berhasil.
            if (
                $tandaTanganPenyerahLama
                && (
                    $actionPenyerah === 'clear'
                    || (
                        $actionPenyerah === 'set'
                        && $signaturePenyerah
                        && $signaturePenyerah !== $tandaTanganPenyerahLama
                    )
                )
            ) {
                Storage::disk('public')->delete(
                    $tandaTanganPenyerahLama
                );
            }

            if (
                $tandaTanganPenerimaLama
                && (
                    $actionPenerima === 'clear'
                    || (
                        $actionPenerima === 'set'
                        && $signaturePenerima
                        && $signaturePenerima !== $tandaTanganPenerimaLama
                    )
                )
            ) {
                Storage::disk('public')->delete(
                    $tandaTanganPenerimaLama
                );
            }
        } catch (\Throwable $e) {
            if ($signaturePenyerah) {
                Storage::disk('public')->delete($signaturePenyerah);
            }

            if ($signaturePenerima) {
                Storage::disk('public')->delete($signaturePenerima);
            }

            throw $e;
        }

        return redirect()
            ->route('user.serah-terima-dinas')
            ->with(
                'success',
                'Data serah terima berhasil disimpan.'
            );
    }

    /**
     * Konfirmasi serah terima oleh admin penerima.
     */
    public function konfirmasi(Request $request, $serahTerima)
    {
        abort_unless(
            in_array((int) auth()->user()->role_id, [2, 3], true),
            403,
            'Hanya admin atau superadmin yang dapat mengonfirmasi serah terima.'
        );

        $validated = $request->validate([
            'tanda_tangan_penerima' => [
                'required',
                'string',
                'max:1500000',
            ],
        ]);

        $data = SerahTerima::findOrFail($serahTerima);

        abort_unless(
            (int) $data->penerima_user_id === (int) auth()->id(),
            403,
            'Pengajuan ini ditujukan kepada akun admin lain.'
        );

        abort_unless(
            $data->status === 'menunggu_penerima',
            422,
            'Serah terima ini tidak sedang menunggu konfirmasi.'
        );

        $signaturePenerima = $this->saveSignature(
            $validated['tanda_tangan_penerima'],
            'penerima'
        );

        try {
            DB::transaction(function () use ($data, $signaturePenerima) {
                $data->update([
                    'tanda_tangan_penerima' => $signaturePenerima,
                    'status' => 'selesai',
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($signaturePenerima);

            throw $e;
        }

        return back()->with(
            'success',
            'Serah terima berhasil dikonfirmasi.'
        );
    }

    private function formatStatus(?string $status): string
    {
        return match ($status) {
            'centang' => 'JALAN',
            'silang' => 'BATAL',
            default => 'BELUM',
        };
    }

    private function checkValue(?string $value): string
    {
        return match ($value) {
            'centang' => 'yes',
            'silang' => 'no',
            default => '',
        };
    }

    private function formatJam($jam): string
    {
        if (!$jam) {
            return '-';
        }

        return substr((string) $jam, 0, 5);
    }

    /**
     * Memvalidasi dan menyimpan data URL tanda tangan PNG.
     */
    private function saveSignature(
        ?string $dataUrl,
        string $jenis
    ): ?string {
        if (empty($dataUrl)) {
            throw ValidationException::withMessages([
                'tanda_tangan_' . $jenis =>
                    'Tanda tangan baru belum tersedia. Silakan tanda tangan kembali.',
            ]);
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

        if (!Storage::disk('public')->put($path, $image)) {
            throw new \RuntimeException(
                'Gagal menyimpan tanda tangan ke storage.'
            );
        }

        return $path;
    }
}