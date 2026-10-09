<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Statistik Dashboard
        |--------------------------------------------------------------------------
        */

        $totalJadwalKa = DB::table('jadwal_ka')
            ->count();

        $totalRealisasiKa = DB::table('realisasi_ka')
            ->count();

        $totalCatatanDinas = DB::table('catatan_dinas')
            ->count();

        $totalInventaris = DB::table('inventaris')
            ->count();

        $totalRealisasiInventaris = DB::table('realisasi_inventaris')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Realisasi Perlintasan Terakhir
        |--------------------------------------------------------------------------
        |
        | Mengambil data realisasi KA terbaru.
        |
        */

        $realisasiTerakhir = DB::table('realisasi_ka')
            ->leftJoin(
                'jadwal_ka',
                'realisasi_ka.jadwal_ka_id',
                '=',
                'jadwal_ka.id'
            )
            ->leftJoin(
                'kereta_api',
                'realisasi_ka.kereta_api_id',
                '=',
                'kereta_api.id'
            )
            ->leftJoin(
                'jpl',
                'jadwal_ka.jpl_id',
                '=',
                'jpl.id'
            )
            ->select([
                'realisasi_ka.id',
                'realisasi_ka.jam_aktual',
                'realisasi_ka.status_lewat',

                'kereta_api.nomor_ka',
                'kereta_api.nama_ka',

                'jpl.kode_jpl',
                'jpl.nama_jpl',
            ])
            ->orderByDesc('realisasi_ka.created_at')
            ->limit(5)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Kirim Data ke View
        |--------------------------------------------------------------------------
        */

        return view('admin.dashboard.index', compact(
            'totalJadwalKa',
            'totalRealisasiKa',
            'totalCatatanDinas',
            'totalInventaris',
            'totalRealisasiInventaris',
            'realisasiTerakhir'
        ));
    }
}