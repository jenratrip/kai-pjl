<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\RealisasiKa;
use Illuminate\Http\Request;

class RealisasiKaController extends Controller
{
    public function index(Request $request)
    {
        $query = RealisasiKa::query()
            ->with([
                'dinas.user',
                'dinas.jpl',
                'keretaApi',
                'jadwalKa',
            ])
            ->whereHas('dinas');

        // Filter berdasarkan tanggal dinas.
        $query->when($request->filled('tanggal'), function ($q) use ($request) {
            $q->whereHas('dinas', function ($dinas) use ($request) {
                $dinas->whereDate('tanggal_dinas', $request->tanggal);
            });
        });

        // Filter berdasarkan JPL.
        $query->when($request->filled('jpl_id'), function ($q) use ($request) {
            $q->whereHas('dinas', function ($dinas) use ($request) {
                $dinas->where('jpl_id', $request->jpl_id);
            });
        });

        // Filter berdasarkan status realisasi.
        $query->when($request->filled('status'), function ($q) use ($request) {
            if ($request->status === 'jalan') {
                $q->where('status_lewat', 'centang');
            } elseif ($request->status === 'batal') {
                $q->where('status_lewat', 'silang');
            } elseif ($request->status === 'belum') {
                $q->whereNull('status_lewat');
            }
        });

        // Pencarian petugas, nomor KA, dan nama KA.
        $query->when($request->filled('q'), function ($q) use ($request) {
            $term = trim($request->q);

            $q->where(function ($search) use ($term) {
                $search->whereHas('keretaApi', function ($ka) use ($term) {
                    $ka->where('nomor_ka', 'like', "%{$term}%")
                        ->orWhere('nama_ka', 'like', "%{$term}%");
                })->orWhereHas('dinas.user', function ($user) use ($term) {
                    $user->where('name', 'like', "%{$term}%");
                })->orWhereHas('dinas.jpl', function ($jpl) use ($term) {
                    $jpl->where('kode_jpl', 'like', "%{$term}%")
                        ->orWhere('nama_jpl', 'like', "%{$term}%");
                });
            });
        });

        $realisasi = $query
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $jpls = \App\Models\Jpl::query()
            ->orderBy('kode_jpl')
            ->get();

        $totalRealisasi = RealisasiKa::whereHas('dinas')->count();

        $totalJalan = RealisasiKa::whereHas('dinas')
            ->where('status_lewat', 'centang')
            ->count();

        $totalBatal = RealisasiKa::whereHas('dinas')
            ->where('status_lewat', 'silang')
            ->count();

        $totalBelum = RealisasiKa::whereHas('dinas')
            ->whereNull('status_lewat')
            ->count();

        return view('admin.realisasi-ka.index', compact(
            'realisasi',
            'jpls',
            'totalRealisasi',
            'totalJalan',
            'totalBatal',
            'totalBelum'
        ));
    }
}
