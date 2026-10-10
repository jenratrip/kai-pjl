<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jpl;
use App\Models\Resort;
use App\Models\Wilayah;
use Illuminate\Http\Request;

class ResortController extends Controller
{
    public function index(Request $request)
    {
        $query = Resort::with('wilayah')
            ->withCount('jpl');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('nama_resort', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%")
                    ->orWhereHas('wilayah', function ($wilayah) use ($search) {
                        $wilayah->where(
                            'nama_wilayah',
                            'like',
                            "%{$search}%"
                        );
                    });
            });
        }

        // Filter wilayah
        if ($request->filled('wilayah_id')) {
            $query->where('wilayah_id', $request->wilayah_id);
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->status === 'aktif'
            );
        }

        $resorts = $query
            ->orderBy('nama_resort')
            ->paginate(10)
            ->withQueryString();

        // Hanya wilayah aktif yang dapat dipilih
        $wilayahs = Wilayah::where('is_active', true)
            ->orderBy('nama_wilayah')
            ->get();

        // Statistik
        $totalResort = Resort::count();

        $totalAktif = Resort::where('is_active', true)
            ->count();

        $totalJpl = Jpl::count();

        return view('admin.resort.index', compact(
            'resorts',
            'wilayahs',
            'totalResort',
            'totalAktif',
            'totalJpl'
        ));
    }

    public function create()
    {
        $wilayahs = Wilayah::where('is_active', true)
            ->orderBy('nama_wilayah')
            ->get();

        return view('admin.resort.create', compact('wilayahs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'wilayah_id' => [
                'required',
                'exists:wilayah,id',
            ],

            'nama_resort' => [
                'required',
                'string',
                'max:255',
                'unique:resorts,nama_resort,NULL,id,wilayah_id,' . $request->wilayah_id,
            ],

            'keterangan' => [
                'nullable',
                'string',
            ],
        ], [
            'wilayah_id.required' =>
                'Wilayah wajib dipilih.',

            'wilayah_id.exists' =>
                'Wilayah yang dipilih tidak valid.',

            'nama_resort.required' =>
                'Nama resort wajib diisi.',

            'nama_resort.unique' =>
                'Nama resort tersebut sudah digunakan pada wilayah yang dipilih.',
        ]);

        $validated['is_active'] = true;

        Resort::create($validated);

        return redirect()
            ->route('admin.resort.index')
            ->with(
                'success',
                'Resort berhasil ditambahkan.'
            );
    }

    public function show(Resort $resort)
    {
        $resort->load([
            'wilayah',
            'jpl',
        ]);

        return view(
            'admin.resort.show',
            compact('resort')
        );
    }

    public function edit(Resort $resort)
    {
        $wilayahs = Wilayah::where('is_active', true)
            ->orWhere('id', $resort->wilayah_id)
            ->orderBy('nama_wilayah')
            ->get();

        return view(
            'admin.resort.edit',
            compact(
                'resort',
                'wilayahs'
            )
        );
    }

    public function update(
        Request $request,
        Resort $resort
    ) {
        $validated = $request->validate([
            'wilayah_id' => [
                'required',
                'exists:wilayah,id',
            ],

            'nama_resort' => [
                'required',
                'string',
                'max:255',
                'unique:resorts,nama_resort,' .
                    $resort->id .
                    ',id,wilayah_id,' .
                    $request->wilayah_id,
            ],

            'keterangan' => [
                'nullable',
                'string',
            ],
        ], [
            'wilayah_id.required' =>
                'Wilayah wajib dipilih.',

            'wilayah_id.exists' =>
                'Wilayah yang dipilih tidak valid.',

            'nama_resort.required' =>
                'Nama resort wajib diisi.',

            'nama_resort.unique' =>
                'Nama resort tersebut sudah digunakan pada wilayah yang dipilih.',
        ]);

        $resort->update($validated);

        return redirect()
            ->route('admin.resort.index')
            ->with(
                'success',
                'Resort berhasil diperbarui.'
            );
    }

    public function toggleStatus(Resort $resort)
    {
        $resort->update([
            'is_active' => ! $resort->is_active,
        ]);

        return back()->with(
            'success',
            'Status resort berhasil diperbarui.'
        );
    }
}