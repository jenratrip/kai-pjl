<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\Inventaris;
use App\Models\RealisasiInventaris;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventarisController extends Controller
{
    /**
     * Mendapatkan Dinas aktif milik pengguna yang login.
     *
     * Dinas utama dan wakilan tetap memiliki record masing-masing.
     */
    private function dinasAktif(): ?Dinas
    {
        return Dinas::query()
            ->where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->latest('tanggal_dinas')
            ->latest('id')
            ->first();
    }

    /**
     * Menentukan Dinas yang menjadi sumber bersama pemeriksaan inventaris.
     *
     * Jika pengguna adalah wakilan, gunakan Dinas utama yang digantikan.
     * Jika Dinas utama belum ditemukan, gunakan Dinas wakilan itu sendiri.
     */
    private function dinasSumberInventaris(Dinas $dinas): Dinas
    {
        if (
            ! $dinas->is_pengganti
            || ! $dinas->user_digantikan_id
        ) {
            return $dinas;
        }

        $dinasUtama = Dinas::query()
            ->where('jpl_id', $dinas->jpl_id)
            ->where('shift_id', $dinas->shift_id)
            ->whereDate('tanggal_dinas', $dinas->tanggal_dinas)
            ->where('is_pengganti', false)
            ->where('user_id', $dinas->user_digantikan_id)
            ->latest('id')
            ->first();

        return $dinasUtama ?? $dinas;
    }

    /**
     * Mendapatkan seluruh ID Dinas yang merupakan pasangan
     * petugas utama dan wakilan dalam periode yang sama.
     */
    private function idDinasSatuPeriode(Dinas $dinasSumber): array
    {
        return Dinas::query()
            ->where('jpl_id', $dinasSumber->jpl_id)
            ->where('shift_id', $dinasSumber->shift_id)
            ->whereDate('tanggal_dinas', $dinasSumber->tanggal_dinas)
            ->where(function ($query) use ($dinasSumber) {
                $query->where('id', $dinasSumber->id)
                    ->orWhere(function ($wakilan) use ($dinasSumber) {
                        $wakilan
                            ->where('is_pengganti', true)
                            ->where(
                                'user_digantikan_id',
                                $dinasSumber->user_id
                            );
                    });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Menyalin pemeriksaan terakhir dari periode sebelumnya
     * ke sumber pemeriksaan periode berjalan.
     *
     * Data Dinas sebelumnya tidak diubah.
     * Data yang sudah tersedia di periode berjalan tidak ditimpa.
     */
    private function siapkanRealisasiInventaris(Dinas $dinasSumber): void
    {
        $inventarisAktif = Inventaris::query()
            ->where('jpl_id', $dinasSumber->jpl_id)
            ->where('is_active', true)
            ->get();

        if ($inventarisAktif->isEmpty()) {
            return;
        }

        $idDinasPeriodeIni = $this->idDinasSatuPeriode($dinasSumber);

        foreach ($inventarisAktif as $item) {
            // Jangan menimpa pemeriksaan yang sudah ada pada periode ini.
            $sudahAda = RealisasiInventaris::query()
                ->where('dinas_id', $dinasSumber->id)
                ->where('inventaris_id', $item->id)
                ->exists();

            if ($sudahAda) {
                continue;
            }

            // Cari hasil pemeriksaan terakhir dari periode sebelumnya.
            $hasilTerakhir = RealisasiInventaris::query()
                ->join(
                    'dinas',
                    'dinas.id',
                    '=',
                    'realisasi_inventaris.dinas_id'
                )
                ->where('dinas.jpl_id', $dinasSumber->jpl_id)
                ->whereDate(
                    'dinas.tanggal_dinas',
                    '<=',
                    $dinasSumber->tanggal_dinas
                )
                ->whereNotIn('dinas.id', $idDinasPeriodeIni)
                ->where(
                    'realisasi_inventaris.inventaris_id',
                    $item->id
                )
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
        }
    }

    /**
     * Menampilkan inventaris JPL dan hasil pemeriksaan.
     *
     * Hasil pemeriksaan dibaca dari sumber bersama,
     * bukan berdasarkan Dinas masing-masing pengguna.
     */
    public function index()
    {
        $dinas = $this->dinasAktif();

        if (! $dinas) {
            return view('user.inventaris.index', [
                'dinas' => null,
                'inventaris' => collect(),
                'realisasi' => collect(),
            ]);
        }

        $dinasSumber = $this->dinasSumberInventaris($dinas);

        DB::transaction(function () use ($dinasSumber) {
            $this->siapkanRealisasiInventaris($dinasSumber);
        });

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
     * Menyimpan pemeriksaan satu inventaris.
     *
     * Aturan:
     * - Bermasalah jika kondisi bukan baik.
     * - Bermasalah jika jumlah aktual berbeda dari jumlah master.
     * - Jika jumlah aktual kosong, gunakan jumlah master.
     * - Pemeriksaan utama dan wakilan disimpan pada sumber yang sama.
     * - Hasil periode sebelumnya tidak ditimpa.
     *
     * Mendukung request biasa maupun AJAX.
     */
    public function update(Request $request, Inventaris $inventaris)
    {
        $validated = $request->validate([
            'dinas_id' => [
                'required',
                'integer',
                'exists:dinas,id',
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
                'nullable',
                'integer',
                'min:0',
            ],
            'catatan' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        // Tetap validasi bahwa Dinas memang milik pengguna yang login.
        $dinas = Dinas::query()
            ->whereKey($validated['dinas_id'])
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

        DB::transaction(function () use (
            $dinasSumber,
            $inventaris,
            $validated,
            $jumlahAktual,
            $adaMasalah
        ) {
            // Pastikan hasil pemeriksaan periode sebelumnya
            // sudah menjadi kondisi awal periode berjalan.
            $this->siapkanRealisasiInventaris($dinasSumber);

            // Simpan perubahan ke sumber pemeriksaan bersama.
            RealisasiInventaris::updateOrCreate(
                [
                    'dinas_id' => $dinasSumber->id,
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

            // Perbarui informasi kondisi terakhir pada master inventaris.
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
        });

        $pesan = $adaMasalah
            ? 'Pemeriksaan tersimpan. Inventaris memerlukan perhatian dan menunggu tinjauan admin.'
            : 'Kondisi inventaris berhasil disimpan.';

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'inventaris_id' => $inventaris->id,
                'status' => $adaMasalah ? 'silang' : 'centang',
                'ada_masalah' => $adaMasalah,
                'message' => $pesan,
            ]);
        }

        return redirect()
            ->route('user.inventaris.index')
            ->with('success', $pesan);
    }
}