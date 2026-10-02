<?php

namespace App\Http\Controllers\Admin;

use App\Exports\WilayahTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Resort;
use App\Models\Wilayah;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class WilayahController extends Controller
{
    /**
     * Menampilkan daftar wilayah.
     */
    public function index(Request $request)
    {
        $query = Wilayah::withCount('resorts');

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where(
                    'nama_wilayah',
                    'like',
                    "%{$search}%"
                )->orWhere(
                    'keterangan',
                    'like',
                    "%{$search}%"
                );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | Filter Status
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'is_active',
                $request->status === 'aktif'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Data Wilayah
        |--------------------------------------------------------------------------
        */

        $wilayahs = $query
            ->orderBy('nama_wilayah')
            ->paginate(10)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Statistik
        |--------------------------------------------------------------------------
        */

        $totalWilayah = Wilayah::count();

        $totalAktif = Wilayah::where(
            'is_active',
            true
        )->count();

        $totalResort = Resort::count();


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        return view(
            'admin.wilayah.index',
            compact(
                'wilayahs',
                'totalWilayah',
                'totalAktif',
                'totalResort'
            )
        );
    }


    /**
     * Form tambah wilayah.
     */
    public function create()
    {
        return view('admin.wilayah.create');
    }


    /**
     * Menyimpan wilayah baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_wilayah' => [
                'required',
                'string',
                'max:255',
                'unique:wilayah,nama_wilayah',
            ],

            'keterangan' => [
                'nullable',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Default Status
        |--------------------------------------------------------------------------
        */

        $validated['is_active'] = true;


        /*
        |--------------------------------------------------------------------------
        | Simpan
        |--------------------------------------------------------------------------
        */

        Wilayah::create($validated);


        return redirect()
            ->route('admin.wilayah.index')
            ->with(
                'success',
                'Wilayah berhasil ditambahkan.'
            );
    }


    /**
     * Form edit wilayah.
     */
    public function edit(Wilayah $wilayah)
    {
        return view(
            'admin.wilayah.edit',
            compact('wilayah')
        );
    }


    /**
     * Update wilayah.
     */
    public function update(
        Request $request,
        Wilayah $wilayah
    ) {

        $validated = $request->validate([
            'nama_wilayah' => [
                'required',
                'string',
                'max:255',
                'unique:wilayah,nama_wilayah,' . $wilayah->id,
            ],

            'keterangan' => [
                'nullable',
                'string',
            ],
        ]);


        $wilayah->update($validated);


        return redirect()
            ->route('admin.wilayah.index')
            ->with(
                'success',
                'Wilayah berhasil diperbarui.'
            );
    }


    /**
     * Toggle status wilayah.
     */
    public function toggleStatus(Wilayah $wilayah)
    {
        $wilayah->update([
            'is_active' => ! $wilayah->is_active,
        ]);


        return back()->with(
            'success',
            'Status wilayah berhasil diperbarui.'
        );
    }


    /**
     * Download template import wilayah.
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new WilayahTemplateExport(),
            'template-import-wilayah.xlsx'
        );
    }
}