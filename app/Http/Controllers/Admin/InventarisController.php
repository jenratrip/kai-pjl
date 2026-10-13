<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventaris;
use App\Models\Jpl;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventarisController extends Controller
{
    public function index()
    {
        $inventaris = Inventaris::with('jpl')
            ->latest()
            ->paginate(10);

        return view('admin.inventaris.index', compact('inventaris'));
    }

    public function create()
    {
        $jpl = Jpl::orderBy('nama_jpl')->get();

        return view('admin.inventaris.create', compact('jpl'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_barang' => [
                'nullable',
                'string',
                'max:255',
                'unique:inventaris,kode_barang',
            ],
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori' => [
                'required',
                Rule::in([
                    'perangkat_pengamanan',
                    'alat_pendukung',
                    'inventaris_pjl',
                ]),
            ],
            'lokasi' => ['required', 'string', 'max:255'],
            'jpl_id' => ['nullable', 'exists:jpl,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Inventaris::create($validated);

        return redirect()
            ->route('admin.inventaris.index')
            ->with('success', 'Inventaris berhasil ditambahkan.');
    }

    public function edit(Inventaris $inventaris)
    {
        $jpl = Jpl::orderBy('nama_jpl')->get();

        return view(
            'admin.inventaris.edit',
            compact('inventaris', 'jpl')
        );
    }

    public function update(Request $request, Inventaris $inventaris)
    {
        $validated = $request->validate([
            'kode_barang' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('inventaris', 'kode_barang')
                    ->ignore($inventaris->id),
            ],
            'nama_barang' => ['required', 'string', 'max:255'],
            'kategori' => [
                'required',
                Rule::in([
                    'perangkat_pengamanan',
                    'alat_pendukung',
                    'inventaris_pjl',
                ]),
            ],
            'lokasi' => ['required', 'string', 'max:255'],
            'jpl_id' => ['nullable', 'exists:jpl,id'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $inventaris->update($validated);

        return redirect()
            ->route('admin.inventaris.index')
            ->with('success', 'Inventaris berhasil diperbarui.');
    }

    public function destroy(Inventaris $inventaris)
    {
        // Inventaris yang sudah memiliki riwayat tidak dihapus
        // agar catatan pemeriksaan dinas tetap terjaga.
        if ($inventaris->realisasiInventaris()->exists()) {
            $inventaris->update(['is_active' => false]);

            return redirect()
                ->route('admin.inventaris.index')
                ->with(
                    'success',
                    'Inventaris dinonaktifkan karena sudah memiliki riwayat pemeriksaan.'
                );
        }

        $inventaris->delete();

        return redirect()
            ->route('admin.inventaris.index')
            ->with('success', 'Inventaris berhasil dihapus.');
    }
}
