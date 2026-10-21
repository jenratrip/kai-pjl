<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventaris;
use App\Models\Jpl;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventarisController extends Controller
{
    private const KONDISI = [
        'baik',
        'perlu_perbaikan',
        'rusak',
        'hilang',
    ];

    private const FILTER = [
        'semua',
        'aktif',
        'nonaktif',
        'baik',
        'perlu_perbaikan',
        'rusak',
        'hilang',
    ];

    private function filterStatus(Request $request): string
    {
        $status = $request->query('status', 'semua');

        return in_array($status, self::FILTER, true)
            ? $status
            : 'semua';
    }

    private function applyStatusFilter($query, string $status)
    {
        if ($status === 'aktif') {
            return $query->where('is_active', true);
        }

        if ($status === 'nonaktif') {
            return $query->where('is_active', false);
        }

        if (in_array($status, self::KONDISI, true)) {
            return $query->where('kondisi_terakhir', $status);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $status = $this->filterStatus($request);
        $search = trim((string) $request->query('search', ''));

        $statistik = [
            'total_jenis' => Inventaris::count(),
            'total_unit' => Inventaris::sum('jumlah'),
            'aktif' => Inventaris::where('is_active', true)->count(),
            'nonaktif' => Inventaris::where('is_active', false)->count(),
            'baik' => Inventaris::where('kondisi_terakhir', 'baik')->count(),
            'perlu_perbaikan' => Inventaris::where('kondisi_terakhir', 'perlu_perbaikan')->count(),
            'rusak' => Inventaris::where('kondisi_terakhir', 'rusak')->count(),
            'hilang' => Inventaris::where('kondisi_terakhir', 'hilang')->count(),
            'belum_diperiksa' => Inventaris::whereNull('kondisi_terakhir')->count(),
        ];

        $query = Jpl::query()
            ->withCount([
                'inventaris',
                'inventaris as inventaris_aktif_count' => fn ($q) =>
                    $q->where('is_active', true),
                'inventaris as inventaris_baik_count' => fn ($q) =>
                    $q->where('kondisi_terakhir', 'baik'),
                'inventaris as inventaris_rusak_count' => fn ($q) =>
                    $q->where('kondisi_terakhir', 'rusak'),
                'inventaris as inventaris_perbaikan_count' => fn ($q) =>
                    $q->where('kondisi_terakhir', 'perlu_perbaikan'),
            ])
            ->withSum('inventaris as total_unit', 'jumlah');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_jpl', 'like', "%{$search}%")
                    ->orWhere('kode_jpl', 'like', "%{$search}%");
            });
        }

        if ($status !== 'semua') {
            $query->whereHas('inventaris', function ($q) use ($status) {
                $this->applyStatusFilter($q, $status);
            });
        }

        $jpls = $query
            ->orderBy('nama_jpl')
            ->paginate(10)
            ->withQueryString();

        return view('admin.inventaris.index', compact(
            'jpls',
            'statistik',
            'status',
            'search'
        ));
    }

    public function showJpl(Request $request, Jpl $jpl)
    {
        $status = $this->filterStatus($request);
        $search = trim((string) $request->query('search', ''));

        $base = Inventaris::query()
            ->where('jpl_id', $jpl->id);

        $statistik = [
            'total' => (clone $base)->count(),
            'unit' => (clone $base)->sum('jumlah'),
            'aktif' => (clone $base)->where('is_active', true)->count(),
            'nonaktif' => (clone $base)->where('is_active', false)->count(),
            'baik' => (clone $base)->where('kondisi_terakhir', 'baik')->count(),
            'perlu_perbaikan' => (clone $base)->where('kondisi_terakhir', 'perlu_perbaikan')->count(),
            'rusak' => (clone $base)->where('kondisi_terakhir', 'rusak')->count(),
            'hilang' => (clone $base)->where('kondisi_terakhir', 'hilang')->count(),
        ];

        $query = Inventaris::query()
            ->where('jpl_id', $jpl->id);

        $this->applyStatusFilter($query, $status);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }

        $inventaris = $query
            ->orderBy('nama_barang')
            ->paginate(15)
            ->withQueryString();

        return view('admin.inventaris.show-jpl', compact(
            'jpl',
            'inventaris',
            'statistik',
            'status',
            'search'
        ));
    }

    public function show(Inventaris $inventaris)
    {
        $inventaris->load('jpl');

        return view('admin.inventaris.show', compact('inventaris'));
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
