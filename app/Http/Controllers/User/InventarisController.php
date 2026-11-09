<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\Inventaris;
use App\Models\RealisasiInventaris;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InventarisController extends Controller
{
    /**
     * Mendapatkan dinas aktif milik pengguna yang login.
     */
    private function dinasAktif(Request $request): ?Dinas
    {
        $dinasId = $request->session()->get('dinas_id');

        if (! is_numeric($dinasId) || (int) $dinasId < 1) {
            return null;
        }

        return Dinas::query()
            ->with(['jpl', 'shift'])
            ->whereKey($dinasId)
            ->where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->first();
    }

    /**
     * Menentukan Dinas utama sebagai sumber pemeriksaan bersama untuk petugas wakilan.
     */
    private function dinasSumberInventaris(Dinas $dinas): Dinas
    {
        if (! $dinas->is_pengganti || ! $dinas->user_digantikan_id) {
            return $dinas;
        }

        return Dinas::query()
            ->where('jpl_id', $dinas->jpl_id)
            ->where('shift_id', $dinas->shift_id)
            ->whereDate('tanggal_dinas', $dinas->tanggal_dinas)
            ->where('is_pengganti', false)
            ->where('user_id', $dinas->user_digantikan_id)
            ->latest('id')
            ->first() ?? $dinas;
    }

    /**
     * Mengambil ID Dinas utama dan wakilan pada periode yang sama.
     *
     * @return array<int, int>
     */
    private function idDinasSatuPeriode(Dinas $dinasSumber): array
    {
        return Dinas::query()
            ->where('jpl_id', $dinasSumber->jpl_id)
            ->where('shift_id', $dinasSumber->shift_id)
            ->whereDate('tanggal_dinas', $dinasSumber->tanggal_dinas)
            ->where(function ($query) use ($dinasSumber): void {
                $query->where('id', $dinasSumber->id)
                    ->orWhere(function ($wakilan) use ($dinasSumber): void {
                        $wakilan->where('is_pengganti', true)
                            ->where('user_digantikan_id', $dinasSumber->user_id);
                    });
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Menyalin pemeriksaan terakhir ke Dinas berjalan tanpa mengubah histori.
     *
     * @return int Jumlah pemeriksaan yang baru disalin.
     */
    private function siapkanRealisasiInventaris(Dinas $dinasSumber): int
    {
        $inventarisAktif = Inventaris::query()
            ->where('jpl_id', $dinasSumber->jpl_id)
            ->where('is_active', true)
            ->get();

        if ($inventarisAktif->isEmpty()) {
            return 0;
        }

        $idDinasPeriodeIni = $this->idDinasSatuPeriode($dinasSumber);
        $jumlahDisalin = 0;

        foreach ($inventarisAktif as $item) {
            $sudahAda = RealisasiInventaris::query()
                ->where('dinas_id', $dinasSumber->id)
                ->where('inventaris_id', $item->id)
                ->exists();

            if ($sudahAda) {
                continue;
            }

            $hasilTerakhir = RealisasiInventaris::query()
                ->join('dinas', 'dinas.id', '=', 'realisasi_inventaris.dinas_id')
                ->where('dinas.jpl_id', $dinasSumber->jpl_id)
                ->whereDate('dinas.tanggal_dinas', '<=', $dinasSumber->tanggal_dinas)
                ->whereNotIn('dinas.id', $idDinasPeriodeIni)
                ->where('realisasi_inventaris.inventaris_id', $item->id)
                ->orderByDesc('dinas.tanggal_dinas')
                ->orderByDesc('dinas.jam_mulai')
                ->orderByDesc('dinas.id')
                ->select('realisasi_inventaris.*')
                ->first();

            if (! $hasilTerakhir) {
                continue;
            }

            RealisasiInventaris::create([
                'dinas_id' => $dinasSumber->id,
                'inventaris_id' => $item->id,
                'status' => $hasilTerakhir->status,
                'kondisi' => $hasilTerakhir->kondisi,
                'catatan' => $hasilTerakhir->catatan,
                'jumlah_aktual' => $hasilTerakhir->jumlah_aktual,
                'diperiksa_pada' => $hasilTerakhir->diperiksa_pada,
                'diperiksa_oleh' => $hasilTerakhir->diperiksa_oleh,
            ]);

            $jumlahDisalin++;
        }

        return $jumlahDisalin;
    }

    public function index(Request $request)
    {
        $dinas = $this->dinasAktif($request);

        if (! $dinas) {
            return view('user.inventaris.index', [
                'dinas' => null,
                'inventaris' => collect(),
                'realisasi' => collect(),
            ]);
        }

        $dinasSumber = $this->dinasSumberInventaris($dinas);

        $inventaris = Inventaris::query()
            ->where('is_active', true)
            ->where('jpl_id', $dinas->jpl_id)
            ->orderBy('nama_barang')
            ->get();

        $realisasi = RealisasiInventaris::query()
            ->where('dinas_id', $dinasSumber->id)
            ->whereIn('inventaris_id', $inventaris->pluck('id'))
            ->get()
            ->keyBy('inventaris_id');

        return view('user.inventaris.index', compact(
            'dinas',
            'inventaris',
            'realisasi'
        ));
    }

    /**
     * Menampilkan bukti foto pemeriksaan hanya kepada PJL pada Dinas terkait.
     */
    public function fotoBukti(
        Request $request,
        Inventaris $inventaris
    ): BinaryFileResponse {
        $dinas = $this->dinasAktif($request);
        abort_unless($dinas, 404);
        abort_unless((int) $inventaris->jpl_id === (int) $dinas->jpl_id, 404);

        $dinasSumber = $this->dinasSumberInventaris($dinas);
        $realisasi = RealisasiInventaris::query()
            ->where('dinas_id', $dinasSumber->id)
            ->where('inventaris_id', $inventaris->id)
            ->firstOrFail();

        $path = $realisasi->bukti_foto_path;
        abort_unless($this->buktiFotoTersedia($path), 404);

        $mimeType = Storage::disk('local')->mimeType($path);
        abort_unless(
            in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true),
            404
        );

        $response = response()->file(
            Storage::disk('local')->path($path),
            ['X-Content-Type-Options' => 'nosniff']
        );
        $response->setPrivate();
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * Menyalin pemeriksaan terakhir ke Dinas berjalan atas permintaan PJL.
     */
    public function prepare(
        Request $request,
        ActivityLogService $activityLogService
    ): RedirectResponse {
        $dinas = $this->dinasAktif($request);
        abort_unless($dinas, 404, 'Dinas aktif tidak ditemukan untuk sesi ini.');

        $dinasSumber = $this->dinasSumberInventaris($dinas);

        $jumlahDisalin = DB::transaction(function () use (
            $dinasSumber,
            $activityLogService
        ): int {
            $dinasTerkunci = Dinas::query()
                ->lockForUpdate()
                ->findOrFail($dinasSumber->id);

            $jumlahDisalin = $this->siapkanRealisasiInventaris($dinasTerkunci);

            if ($jumlahDisalin > 0) {
                $activityLogService->log(
                    'copy_previous_inspections',
                    'inventaris',
                    'Pemeriksaan inventaris periode sebelumnya disalin.',
                    $dinasTerkunci,
                    null,
                    [
                        'copied_count' => $jumlahDisalin,
                    ]
                );
            }

            return $jumlahDisalin;
        });

        return redirect()
            ->route('user.inventaris.index')
            ->with(
                'success',
                $jumlahDisalin > 0
                    ? "{$jumlahDisalin} pemeriksaan terakhir berhasil disalin ke Dinas ini."
                    : 'Tidak ada pemeriksaan sebelumnya yang perlu disalin.'
            );
    }

    /**
     * Menyimpan pemeriksaan satu inventaris.
     *
     * Aturan sama dengan SerahTerimaDinasController::storeInventaris():
     * - Bermasalah jika kondisi bukan "baik" atau jumlah aktual
     *   berbeda dari jumlah master.
     * - jumlah_aktual opsional; jika kosong dianggap sama dengan
     *   jumlah master (inventaris lengkap).
     * - Inventaris bermasalah ditandai untuk ditinjau admin.
     *
     * Mendukung request biasa (redirect) maupun fetch/AJAX (JSON).
     */
    public function update(
        Request $request,
        Inventaris $inventaris,
        ActivityLogService $activityLogService
    ) {
        $validated = $request->validate([
            'dinas_id' => ['required', 'integer', 'exists:dinas,id'],
            'kondisi' => [
                'required',
                Rule::in([
                    'baik',
                    'perlu_perbaikan',
                    'rusak',
                    'hilang',
                ]),
            ],
            'jumlah_aktual' => ['nullable', 'integer', 'min:0'],
            'catatan' => [
                'nullable',
                'string',
                'max:2000',
                Rule::requiredIf(in_array(
                    $request->input('kondisi'),
                    ['rusak', 'hilang'],
                    true
                )),
            ],
            'bukti_foto' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        if (
            $request->hasFile('bukti_foto')
            && ! in_array($validated['kondisi'], ['rusak', 'hilang'], true)
        ) {
            throw ValidationException::withMessages([
                'bukti_foto' => 'Foto bukti hanya dapat dilampirkan untuk kondisi rusak atau hilang.',
            ]);
        }

        $dinas = Dinas::query()
            ->whereKey($validated['dinas_id'])
            ->whereKey($request->session()->get('dinas_id'))
            ->where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->firstOrFail();

        abort_unless(
            $inventaris->is_active
                && (int) $inventaris->jpl_id === (int) $dinas->jpl_id,
            403,
            'Inventaris ini tidak termasuk dalam penempatan JPL dinas Anda.'
        );

        $jumlahMaster = (int) $inventaris->jumlah;

        $jumlahAktual = isset($validated['jumlah_aktual'])
            ? (int) $validated['jumlah_aktual']
            : $jumlahMaster;

        $adaMasalah = $validated['kondisi'] !== 'baik'
            || $jumlahAktual !== $jumlahMaster;

        $dinasSumber = $this->dinasSumberInventaris($dinas);
        $fotoUnggahan = $request->file('bukti_foto');
        $fotoBaruPath = null;
        $fotoLamaPath = null;

        try {
            $realisasi = DB::transaction(function () use (
                $dinasSumber,
                $inventaris,
                $validated,
                $jumlahAktual,
                $adaMasalah,
                $activityLogService,
                $fotoUnggahan,
                &$fotoBaruPath,
                &$fotoLamaPath
            ): RealisasiInventaris {
                $dinasTerkunci = Dinas::query()
                    ->lockForUpdate()
                    ->findOrFail($dinasSumber->id);

                $existingRealisasi = RealisasiInventaris::query()
                    ->where('dinas_id', $dinasTerkunci->id)
                    ->where('inventaris_id', $inventaris->id)
                    ->lockForUpdate()
                    ->first();

                $fotoLamaPath = $existingRealisasi?->bukti_foto_path;
                $fotoLamaTersedia = $this->buktiFotoTersedia($fotoLamaPath);

                if (
                    $validated['kondisi'] === 'rusak'
                    && ! $fotoLamaTersedia
                    && ! $fotoUnggahan
                ) {
                    throw ValidationException::withMessages([
                        'bukti_foto' => 'Foto bukti kerusakan wajib dilampirkan.',
                    ]);
                }

                if ($fotoUnggahan) {
                    $fotoBaruPath = $fotoUnggahan->store(
                        'inventaris/bukti',
                        'local'
                    );

                    if (! is_string($fotoBaruPath)) {
                        throw ValidationException::withMessages([
                            'bukti_foto' => 'Foto bukti gagal disimpan. Silakan coba kembali.',
                        ]);
                    }
                }

                $fotoTersimpanPath = $fotoBaruPath
                    ?? ($fotoLamaTersedia ? $fotoLamaPath : null);

                $oldValues = $existingRealisasi === null
                    ? null
                    : [
                        'dinas_id' => (int) $existingRealisasi->dinas_id,
                        'inventaris_id' => (int) $existingRealisasi->inventaris_id,
                        'status' => $existingRealisasi->status,
                        'kondisi' => $existingRealisasi->kondisi,
                        'jumlah_aktual' => $existingRealisasi->jumlah_aktual,
                        'status_tinjauan' => $inventaris->status_tinjauan,
                    ];

                $realisasiSimpan = RealisasiInventaris::updateOrCreate(
                    [
                        'dinas_id' => $dinasTerkunci->id,
                        'inventaris_id' => $inventaris->id,
                    ],
                    [
                        'status' => $adaMasalah ? 'silang' : 'centang',
                        'kondisi' => $validated['kondisi'],
                        'jumlah_aktual' => $jumlahAktual,
                        'catatan' => $validated['catatan'] ?? null,
                        'bukti_foto_path' => $fotoTersimpanPath,
                        'diperiksa_pada' => now(),
                        'diperiksa_oleh' => auth()->id(),
                    ]
                );

                $inventaris->jumlah_aktual_terakhir = $jumlahAktual;
                $inventaris->kondisi_terakhir = $validated['kondisi'];

                if ($adaMasalah) {
                    if (
                        ! $inventaris->dilaporkan_pada
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

                $activityLogService->log(
                    'inspection',
                    'inventaris',
                    'Pemeriksaan inventaris oleh PJL berhasil disimpan.',
                    $realisasiSimpan,
                    $oldValues,
                    [
                        'dinas_id' => (int) $dinasTerkunci->id,
                        'inventaris_id' => (int) $inventaris->id,
                        'status' => $realisasiSimpan->status,
                        'kondisi' => $realisasiSimpan->kondisi,
                        'jumlah_aktual' => $realisasiSimpan->jumlah_aktual,
                        'status_tinjauan' => $inventaris->status_tinjauan,
                    ]
                );

                return $realisasiSimpan;
            });
        } catch (\Throwable $exception) {
            if (is_string($fotoBaruPath)) {
                Storage::disk('local')->delete($fotoBaruPath);
            }

            throw $exception;
        }

        if (
            is_string($fotoLamaPath)
            && $fotoLamaPath !== $realisasi->bukti_foto_path
            && str_starts_with($fotoLamaPath, 'inventaris/bukti/')
            && ! str_contains($fotoLamaPath, '..')
        ) {
            Storage::disk('local')->delete($fotoLamaPath);
        }

        $pesan = $adaMasalah
            ? 'Pemeriksaan tersimpan. Inventaris memerlukan perhatian dan menunggu tinjauan admin.'
            : 'Kondisi inventaris berhasil disimpan.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'inventaris_id' => $inventaris->id,
                'status' => $adaMasalah ? 'silang' : 'centang',
                'ada_masalah' => $adaMasalah,
                'bukti_foto_url' => $realisasi->bukti_foto_path
                    ? route('user.inventaris.foto-bukti', $inventaris)
                    : null,
                'message' => $pesan,
            ]);
        }

        return redirect()
            ->route('user.inventaris.index')
            ->with('success', $pesan);
    }

    private function buktiFotoTersedia(?string $path): bool
    {
        return is_string($path)
            && str_starts_with($path, 'inventaris/bukti/')
            && ! str_contains($path, '..')
            && Storage::disk('local')->exists($path);
    }
}
