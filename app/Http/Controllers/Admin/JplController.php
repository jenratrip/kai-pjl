<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jpl;
use App\Models\Resort;
use Illuminate\Http\Request;

class JplController extends Controller
{
    public function index(Request $request)
    {
        $query = Jpl::with('resort.wilayah');

        // Pencarian
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('kode_jpl', 'like', "%{$search}%")
                    ->orWhere('nama_jpl', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        // Filter Resort
        if ($request->filled('resort_id')) {
            $query->where('resort_id', $request->resort_id);
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status);
        }

        $jpls = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $resorts = Resort::with('wilayah')
            ->where('is_active', true)
            ->orderBy('nama_resort')
            ->get();

        $totalJpl = Jpl::count();
        $totalResort = Resort::count();

        return view('admin.jpl.index', compact(
            'jpls',
            'resorts',
            'totalJpl',
            'totalResort'
        ));
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}