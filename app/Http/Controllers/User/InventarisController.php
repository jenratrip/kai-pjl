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
     * Mendapatkan dinas aktif milik pengguna yang login.
     */
    private function dinasAktif(): ?Dinas
    {
        return Dinas::where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->latest('tanggal_dinas')
            ->latest('id')
            ->first();
    }

    public function index()
    {
        $dinas = $this->dinasAktif();

        if (!$dinas) {
            return view('user.inventaris.index', [
                'dinas' => null,
                'inventaris' => collect(),
                'realisasi' => collect(),
            ]);
        }

        $inventaris = Inventaris::where('is_active', true)
            ->where('jpl_id', $dinas->jpl_id)
            ->orderBy('nama_barang')
            ->get();

        $realisasi = RealisasiInventaris::where('dinas_id', $dinas->id)
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
     * Aturan sama dengan SerahTerimaDinasController::storeInventaris():
     * - Bermasalah jika kondisi bukan "baik" atau jumlah aktual
     *   berbeda dari jumlah master.
     * - jumlah_aktual opsional; jika kosong dianggap sama dengan
     *   jumlah master (inventaris lengkap).
     * - Inventaris bermasalah ditandai untuk ditinjau admin.
     *
     * Mendukung request biasa (redirect) maupun fetch/AJAX (JSON).
     */
    public function update(Request $request, Inventaris $inventaris)
    {
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
            'catatan' => ['nullable', 'string', 'max:2000'],
        ]);

        $dinas = Dinas::whereKey($validated['dinas_id'])
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

        DB::transaction(function () use (
            $dinas,
            $inventaris,
            $validated,
            $jumlahAktual,
            $adaMasalah
        ) {
            // Kolom status lama dipertahankan agar kompatibel
            // dengan struktur database yang sudah ada.
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