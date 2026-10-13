<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\Inventaris;
use App\Models\RealisasiInventaris;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventarisController extends Controller
{
    public function index()
    {
        $dinas = Dinas::where('user_id', auth()->id())
            ->where('status', 'berlangsung')
            ->latest('tanggal_dinas')
            ->first();

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

        // Kolom status lama dipertahankan agar kompatibel
        // dengan struktur database yang sudah ada.
        $status = $validated['kondisi'] === 'baik'
            ? 'centang'
            : 'silang';

        RealisasiInventaris::updateOrCreate(
            [
                'dinas_id' => $dinas->id,
                'inventaris_id' => $inventaris->id,
            ],
            [
                'status' => $status,
                'kondisi' => $validated['kondisi'],
                'catatan' => $validated['catatan'] ?? null,
                'diperiksa_pada' => now(),
                'diperiksa_oleh' => auth()->id(),
            ]
        );

        return redirect()
            ->route('user.inventaris.index')
            ->with('success', 'Kondisi inventaris berhasil disimpan.');
    }
}
