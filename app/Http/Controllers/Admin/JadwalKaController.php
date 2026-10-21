<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalKa;
use App\Models\Jpl;
use Illuminate\Http\Request;

class JadwalKaController extends Controller
{
    /**
     * Index:
     * Tampilkan hanya JPL yang memiliki jadwal KA.
     */
    public function index(Request $request)
    {
        // Hitung jumlah jadwal KA untuk setiap JPL.
        $counts = JadwalKa::query()
            ->selectRaw('jpl_id, COUNT(*) as aggregate')
            ->groupBy('jpl_id')
            ->pluck('aggregate', 'jpl_id');

        // Ambil hanya JPL yang memiliki jadwal KA.
        $jpls = Jpl::query()
            ->whereIn('id', $counts->keys())
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->q);

                $query->where(function ($q) use ($term) {
                    $q->where('kode_jpl', 'like', "%{$term}%")
                        ->orWhere('nama_jpl', 'like', "%{$term}%");
                });
            })
            ->orderBy('kode_jpl')
            ->paginate(25)
            ->withQueryString();

        // Tambahkan jumlah jadwal ke setiap data JPL.
        $jpls->getCollection()->transform(function ($jpl) use ($counts) {
            $jpl->jadwal_count = (int) ($counts[$jpl->id] ?? 0);

            return $jpl;
        });

        return view('admin.jadwal-ka.index', compact('jpls'));
    }

    /**
     * Detail:
     * Tampilkan jadwal KA berdasarkan JPL yang dipilih.
     */
    public function show(Request $request, Jpl $jpl)
    {
        $jadwal = JadwalKa::query()
            ->with(['keretaApi', 'jpl'])
            ->where('jpl_id', $jpl->id)
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where(
                    'is_active',
                    $request->status === 'aktif'
                )
            )
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim($request->q);

                $query->whereHas('keretaApi', function ($kereta) use ($term) {
                    $kereta->where('nomor_ka', 'like', "%{$term}%")
                        ->orWhere('nama_ka', 'like', "%{$term}%");
                });
            })
            ->orderBy('jam_lewat')
            ->paginate(25)
            ->withQueryString();

        return view('admin.jadwal-ka.show', compact('jpl', 'jadwal'));
    }
}
