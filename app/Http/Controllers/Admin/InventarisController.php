<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventaris;
use App\Models\Jpl;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventarisController extends Controller
{
    private const KONDISI = [
        'baik',
        'perlu_perbaikan',
        'rusak',
        'hilang',
        'perlu_tinjauan',
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

        if ($status === 'perlu_tinjauan') {
            return $query->where('status_tinjauan', 'perlu_tinjauan');
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
            'perlu_tinjauan' => Inventaris::where('status_tinjauan', 'perlu_tinjauan')->count(),
        ];

        $query = Jpl::query()
            ->withCount([
                'inventaris',
                'inventaris as inventaris_aktif_count' => fn ($q) => $q->where('is_active', true),
                'inventaris as inventaris_baik_count' => fn ($q) => $q->where('kondisi_terakhir', 'baik'),
                'inventaris as inventaris_rusak_count' => fn ($q) => $q->where('kondisi_terakhir', 'rusak'),
                'inventaris as inventaris_perbaikan_count' => fn ($q) => $q->where('kondisi_terakhir', 'perlu_perbaikan'),
                'inventaris as inventaris_perlu_tinjauan_count' => fn ($q) => $q->where('status_tinjauan', 'perlu_tinjauan'),
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
            'perlu_tinjauan' => (clone $base)->where('status_tinjauan', 'perlu_tinjauan')->count(),
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
        $inventaris->load(['jpl', 'pelapor', 'peninjau']);

        return view('admin.inventaris.show', compact('inventaris'));
    }

    /**
     * Menyelesaikan tinjauan inventaris oleh Admin.
     */
    public function review(
        Request $request,
        Inventaris $inventaris,
        ActivityLogService $activityLogService
    ): RedirectResponse {
        $validated = $request->validate([
            'catatan_tinjauan' => ['required', 'string', 'max:2000'],
        ], [
            'catatan_tinjauan.required' => 'Catatan hasil tinjauan wajib diisi.',
            'catatan_tinjauan.max' => 'Catatan tinjauan maksimal 2000 karakter.',
        ]);

        $reviewed = DB::transaction(function () use (
            $inventaris,
            $validated,
            $request,
            $activityLogService
        ): bool {
            $lockedInventaris = Inventaris::query()
                ->lockForUpdate()
                ->findOrFail($inventaris->id);

            if ($lockedInventaris->status_tinjauan !== 'perlu_tinjauan') {
                return false;
            }

            $oldReviewStatus = $lockedInventaris->status_tinjauan;
            $lockedInventaris->update([
                'status_tinjauan' => 'normal',
                'catatan_tinjauan' => $validated['catatan_tinjauan'],
                'ditinjau_oleh' => $request->user()->id,
                'ditinjau_pada' => now(),
            ]);

            $activityLogService->log(
                'review',
                'inventaris',
                'Review Inventaris berhasil diselesaikan.',
                $lockedInventaris,
                ['status_tinjauan' => $oldReviewStatus],
                [
                    'status_tinjauan' => 'normal',
                    'ditinjau_oleh' => $request->user()->id,
                ]
            );

            return true;
        });

        return redirect()
            ->route('admin.inventaris.show', $inventaris->id)
            ->with(
                $reviewed ? 'success' : 'error',
                $reviewed
                    ? 'Tinjauan inventaris berhasil diselesaikan.'
                    : 'Inventaris ini tidak sedang menunggu tinjauan.'
            );
    }

    public function create()
    {
        $jpl = Jpl::orderBy('nama_jpl')->get();

        return view('admin.inventaris.create', compact('jpl'));
    }

    public function store(Request $request, ActivityLogService $activityLogService)
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

        DB::transaction(function () use ($validated, $activityLogService): void {
            $inventaris = Inventaris::create($validated);
            $inventaris->refresh();

            $activityLogService->log(
                'create',
                'inventaris',
                'Inventaris berhasil ditambahkan.',
                $inventaris,
                null,
                $this->activityValues($inventaris)
            );
        });

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

    public function update(
        Request $request,
        Inventaris $inventaris,
        ActivityLogService $activityLogService
    ) {
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

        DB::transaction(function () use ($inventaris, $validated, $activityLogService): void {
            $lockedInventaris = Inventaris::query()
                ->lockForUpdate()
                ->findOrFail($inventaris->id);
            $oldValues = $this->activityValues($lockedInventaris);
            $lockedInventaris->fill($validated);
            $changedFields = array_keys($lockedInventaris->getDirty());
            $lockedInventaris->save();

            if ($changedFields !== []) {
                $newValues = $this->activityValues($lockedInventaris);
                $newValues['changed_fields'] = $changedFields;

                $activityLogService->log(
                    'update',
                    'inventaris',
                    'Data Inventaris berhasil diperbarui.',
                    $lockedInventaris,
                    $oldValues,
                    $newValues
                );
            }
        });

        return redirect()
            ->route('admin.inventaris.index')
            ->with('success', 'Inventaris berhasil diperbarui.');
    }

    public function destroy(Inventaris $inventaris, ActivityLogService $activityLogService)
    {
        $deactivated = DB::transaction(function () use ($inventaris, $activityLogService): bool {
            $lockedInventaris = Inventaris::query()
                ->lockForUpdate()
                ->findOrFail($inventaris->id);

            if ($lockedInventaris->realisasiInventaris()->exists()) {
                if ($lockedInventaris->is_active) {
                    $lockedInventaris->update(['is_active' => false]);

                    $activityLogService->log(
                        'deactivate',
                        'inventaris',
                        'Inventaris dinonaktifkan karena memiliki riwayat pemeriksaan.',
                        $lockedInventaris,
                        ['is_active' => true],
                        ['is_active' => false]
                    );
                }

                return true;
            }

            $oldValues = $this->activityValues($lockedInventaris);
            $lockedInventaris->delete();

            $activityLogService->log(
                'delete',
                'inventaris',
                'Inventaris berhasil dihapus.',
                $lockedInventaris,
                $oldValues,
                null
            );

            return false;
        });

        if ($deactivated) {
            return redirect()
                ->route('admin.inventaris.index')
                ->with(
                    'success',
                    'Inventaris dinonaktifkan karena sudah memiliki riwayat pemeriksaan.'
                );
        }

        return redirect()
            ->route('admin.inventaris.index')
            ->with('success', 'Inventaris berhasil dihapus.');
    }

    /**
     * @return array<string, bool|int|string|null>
     */
    private function activityValues(Inventaris $inventaris): array
    {
        return [
            'jpl_id' => $inventaris->jpl_id === null ? null : (int) $inventaris->jpl_id,
            'kategori' => $inventaris->kategori,
            'jumlah' => (int) $inventaris->jumlah,
            'is_active' => (bool) $inventaris->is_active,
            'status_tinjauan' => $inventaris->status_tinjauan,
        ];
    }
}
