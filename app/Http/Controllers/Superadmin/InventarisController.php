<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Inventaris;
use App\Models\Jpl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    private function applyStatusFilter(Builder $query, string $status): Builder
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

    public function index(Request $request): View
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
                'inventaris as inventaris_aktif_count' => fn ($query) => $query->where('is_active', true),
                'inventaris as inventaris_baik_count' => fn ($query) => $query->where('kondisi_terakhir', 'baik'),
                'inventaris as inventaris_rusak_count' => fn ($query) => $query->where('kondisi_terakhir', 'rusak'),
                'inventaris as inventaris_perbaikan_count' => fn ($query) => $query->where('kondisi_terakhir', 'perlu_perbaikan'),
            ])
            ->withSum('inventaris as total_unit', 'jumlah');

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('nama_jpl', 'like', "%{$search}%")
                    ->orWhere('kode_jpl', 'like', "%{$search}%");
            });
        }

        if ($status !== 'semua') {
            $query->whereHas('inventaris', function (Builder $query) use ($status): void {
                $this->applyStatusFilter($query, $status);
            });
        }

        $jpls = $query
            ->orderBy('nama_jpl')
            ->paginate(10)
            ->withQueryString();

        return view('superadmin.inventaris.index', compact(
            'jpls',
            'statistik',
            'status',
            'search'
        ));
    }

    public function showJpl(Request $request, Jpl $jpl): View
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
            $query->where(function (Builder $query) use ($search): void {
                $query->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }

        $inventaris = $query
            ->orderBy('nama_barang')
            ->paginate(15)
            ->withQueryString();

        return view('superadmin.inventaris.show-jpl', compact(
            'jpl',
            'inventaris',
            'statistik',
            'status',
            'search'
        ));
    }

    public function show(Inventaris $inventaris): View
    {
        $inventaris->load('jpl');

        return view('superadmin.inventaris.show', compact('inventaris'));
    }
}
