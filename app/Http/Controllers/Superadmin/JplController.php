<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Jpl;
use App\Models\Resort;
use Illuminate\Http\Request;

class JplController extends Controller
{
    public function index(Request $request)
    {
        $query = Jpl::with('resort.wilayah');

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('kode_jpl', 'like', "%{$search}%")
                    ->orWhere('nama_jpl', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('resort_id')) {
            $query->where('resort_id', $request->resort_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $jpls = $query->latest()
            ->paginate(10)
            ->withQueryString();

        $resorts = Resort::with('wilayah')
            ->where('is_active', true)
            ->orderBy('nama_resort')
            ->get();

        $totalJpl = Jpl::count();
        $totalResort = Resort::count();

        return view('superadmin.jpl.index', compact(
            'jpls',
            'resorts',
            'totalJpl',
            'totalResort'
        ));
    }
}
