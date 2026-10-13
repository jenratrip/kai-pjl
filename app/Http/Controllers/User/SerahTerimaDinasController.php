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
     * Menampilkan seluruh informasi serah terima dinas.
     */
    public function index()
    {
        $dinas = $this->dinasAktif();

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
        $serahTerima = collect();

        if ($dinas) {
            $dataRealisasiKA = $this->ambilDataKA($dinas);

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

            $catatan = CatatanDinas::with([
                'attachments',
                'user',
            ])
                ->where('dinas_id', $dinas->id)
                ->latest('waktu_catatan')
                ->get();

            $inventaris = Inventaris::where('is_active', true)
                ->where('jpl_id', $dinas->jpl_id)
                ->orderBy('nama_barang')
                ->get();

            $realisasi = RealisasiInventaris::where(
                'dinas_id',
                $dinas->id
            )
                ->get()
                ->keyBy('inventaris_id');

            $serahTerima = SerahTerima::with('penerima')
                ->where('dinas_id', $dinas->id)
                ->latest('id')
                ->get();
        }

        return view('user.serahterimacatatan.index', compact(
            'dinas',
            'admins',
            'catatan',
            'inventaris',
            'realisasi',
            'serahTerima',
            'dataRealisasiKA',
            'ringkasanKA'
        ));
    }

    /**
     * Menampilkan data jadwal dan realisasi KA.
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
            $realisasi = $realisasiTerjadwal->get($jadwal->id);

            $dataKA[] = [
                'id' => $realisasi?->id,
                'nomor' => $jadwal->keretaApi?->nomor_ka ?? '-',
                'nama' => $jadwal->keretaApi?->nama_ka ?? '-',
                'jadwal' => $this->formatJam($jadwal->jam_lewat),
                'aktual' => $this->formatJam($realisasi?->jam_aktual),
                'status' => $this->formatStatus($realisasi?->status_lewat),
                'semboyan21' => $this->checkValue($realisasi?->status_s21),
                'genta' => $this->checkValue($realisasi?->status_genta),
                'pintu' => $this->checkValue($realisasi?->status_pintu_perlintasan),
                'semboyan35' => $this->checkValue($realisasi?->status_s35),
                'keterangan' => $realisasi?->catatan ?? '',
                'jadwal_ka_id' => $jadwal->id,
                'is_manual' => false,
            ];
        }

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
                'semboyan21' => $this->checkValue($realisasi->status_s21),
                'genta' => $this->checkValue($realisasi->status_genta),
                'pintu' => $this->checkValue($realisasi->status_pintu_perlintasan),
                'semboyan35' => $this->checkValue($realisasi->status_s35),
                'keterangan' => $realisasi->catatan ?? '',
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

    private function namaHariIndonesia(Carbon $tanggal): string
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
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
     * Mengirim pengajuan serah terima kepada admin.
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
            'tanda_tangan_penyerah' => [
                'required',
                'string',
                'max:1500000',
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

        $ringkasan = json_decode(
            $validated['ringkasan'],
            true
        );

        if (!is_array($ringkasan)) {
            return back()
                ->withErrors([
                    'ringkasan' => 'Format ringkasan serah terima tidak valid.',
                ])
                ->withInput();
        }

        $signaturePenyerah = null;

        try {
            $signaturePenyerah = $this->saveSignature(
                $validated['tanda_tangan_penyerah'],
                'penyerah'
            );

            $pengajuan = DB::transaction(function () use (
                $dinas,
                $validated,
                $ringkasan,
                $signaturePenyerah
            ) {
                return SerahTerima::create([
                    'dinas_id' => $dinas->id,
                    'dinas_penerima_id' => null,
                    'penerima_user_id' => $validated['penerima_user_id'],
                    'waktu_serah_terima' => now(),
                    'ringkasan' => $ringkasan,
                    'tanda_tangan_penyerah' => $signaturePenyerah,
                    'tanda_tangan_penerima' => null,
                    'status' => 'menunggu_penerima',
                ]);
            });
        } catch (\Throwable $e) {
            if ($signaturePenyerah) {
                Storage::disk('public')->delete($signaturePenyerah);
            }

            throw $e;
        }

        return redirect()
            ->route('user.serah-terima-dinas')
            ->with(
                'success',
                'Pengajuan serah terima berhasil dikirim kepada admin.'
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
            DB::transaction(function () use (
                $data,
                $signaturePenerima
            ) {
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

        if (!Storage::disk('public')->put($path, $image)) {
            throw new \RuntimeException(
                'Gagal menyimpan tanda tangan ke storage.'
            );
        }

        return $path;
    }
}
