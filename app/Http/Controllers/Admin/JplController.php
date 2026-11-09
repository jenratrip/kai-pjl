<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jpl;
use App\Models\Resort;
use App\Services\ActivityLogService;
use App\Services\DefaultInventarisService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JplController extends Controller
{
    /**
     * Daftar kata umum yang tidak perlu dipakai dalam pencarian.
     */
    private array $stopWords = [
        'di',
        'ke',
        'dari',
        'dan',
        'atau',
        'yang',
        'untuk',
        'pada',
        'dengan',
        'adalah',
        'ini',
        'itu',
        'the',
        'jpl',
        'nomor',
        'no',
    ];

    /**
     * Menampilkan daftar JPL, statistik, dan filter.
     */
    public function index(Request $request): View
    {
        $query = Jpl::with('resort');

        /*
         * Pencarian otomatis berdasarkan kata yang relevan.
         */
        if ($request->filled('search')) {
            $this->applyJplSearch(
                $query,
                $request->input('search')
            );
        }

        /*
         * Filter Resort.
         */
        if ($request->filled('resort_id')) {
            $query->where(
                'resort_id',
                $request->input('resort_id')
            );
        }

        /*
         * Filter status aktif/nonaktif.
         */
        if ($request->filled('status')) {
            $query->where(
                'is_active',
                $request->input('status')
            );
        }

        /*
         * Urutkan kode numerik secara numerik.
         */
        $jpls = $query
            ->orderByRaw(
                "CASE
                    WHEN kode_jpl REGEXP '^[0-9]+$'
                    THEN 0
                    ELSE 1
                END"
            )
            ->orderByRaw(
                "CASE
                    WHEN kode_jpl REGEXP '^[0-9]+$'
                    THEN CAST(kode_jpl AS UNSIGNED)
                    ELSE NULL
                END ASC"
            )
            ->orderBy('kode_jpl')
            ->paginate(10)
            ->withQueryString();

        /*
         * Resort aktif untuk filter dan form tambah.
         */
        $resorts = Resort::query()
            ->where('is_active', true)
            ->orderBy('nama_resort')
            ->get();

        /*
         * Statistik keseluruhan, tidak terpengaruh filter.
         */
        $totalJpl = Jpl::count();

        $totalJplAktif = Jpl::where(
            'is_active',
            true
        )->count();

        $totalJplNonaktif = Jpl::where(
            'is_active',
            false
        )->count();

        return view('admin.jpl.index', compact(
            'jpls',
            'resorts',
            'totalJpl',
            'totalJplAktif',
            'totalJplNonaktif'
        ));
    }

    /**
     * Membersihkan teks pencarian dan menghapus stop words.
     */
    private function normalizeSearch(string $search): array
    {
        $search = mb_strtolower(trim($search));

        /*
         * Pertahankan huruf, angka, spasi, titik, plus,
         * dan tanda minus.
         */
        $search = preg_replace(
            '/[^\p{L}\p{N}\s+.-]/u',
            ' ',
            $search
        );

        $search = preg_replace('/\s+/u', ' ', $search);

        /*
         * Tambahkan variasi KM/HM:
         * "491 140" menjadi kandidat "491+140".
         */
        $candidates = [$search];

        if (preg_match(
            '/^(\d+)\s+(\d+(?:\.\d+)?)$/',
            $search,
            $matches
        )) {
            $candidates[] = $matches[1].'+'.$matches[2];
        }

        /*
         * Ubah format "491+140" menjadi dua kata
         * pencarian: "491" dan "140".
         */
        $expanded = [];

        foreach ($candidates as $candidate) {
            $candidate = str_replace('+', ' ', $candidate);

            foreach (explode(' ', $candidate) as $word) {
                $word = trim($word);

                if ($word !== '') {
                    $expanded[] = $word;
                }
            }
        }

        $words = array_values(array_unique($expanded));

        /*
         * Abaikan kata umum.
         */
        $words = array_values(array_filter(
            $words,
            fn (string $word): bool => ! in_array($word, $this->stopWords, true)
        ));

        return $words;
    }

    /**
     * Menerapkan pencarian pada kolom-kolom JPL.
     *
     * Setiap kata harus cocok dengan minimal satu kolom.
     */
    private function applyJplSearch(
        $query,
        string $search
    ): void {
        $words = $this->normalizeSearch($search);

        /*
         * Jika input hanya berisi stop words, jangan
         * mengembalikan daftar kosong secara tidak sengaja.
         */
        if (empty($words)) {
            return;
        }

        $columns = [
            'kode_jpl',
            'nama_jpl',
            'km_hm',
            'antara',
            'status_penjagaan',
            'jenis_jalur_ka',
            'konst_jalan',
            'titik_koordinat',
            'lokasi',
            'kota_kabupaten',
            'provinsi',
        ];

        foreach ($words as $word) {
            $query->where(function ($q) use (
                $word,
                $columns
            ) {
                foreach ($columns as $index => $column) {
                    if ($index === 0) {
                        $q->where(
                            $column,
                            'like',
                            "%{$word}%"
                        );
                    } else {
                        $q->orWhere(
                            $column,
                            'like',
                            "%{$word}%"
                        );
                    }
                }
            });
        }
    }

    /**
     * Modal tambah berada di halaman index.
     */
    public function create(): RedirectResponse
    {
        return redirect()
            ->route('admin.jpl.index')
            ->with('open_create_modal', true);
    }

    /**
     * Validasi data JPL untuk tambah dan edit.
     */
    private function validateJpl(
        Request $request,
        ?Jpl $jpl = null
    ): array {
        $resortRule = Rule::exists('resorts', 'id')
            ->where(function (Builder $query) use ($jpl) {
                $query->where('is_active', true);

                if ($jpl) {
                    $query->orWhere('id', $jpl->resort_id);
                }
            });

        return $request->validate([
            'kode_jpl' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('jpl', 'kode_jpl')
                    ->ignore($jpl?->id),
            ],

            'nama_jpl' => [
                'required',
                'string',
                'max:255',
            ],

            'resort_id' => [
                'required',
                'integer',
                $resortRule,
            ],

            'antara' => [
                'nullable',
                'string',
                'max:255',
            ],

            'km_hm' => [
                'nullable',
                'string',
                'max:255',
            ],

            'lokasi' => [
                'nullable',
                'string',
                'max:255',
            ],

            'kota_kabupaten' => [
                'nullable',
                'string',
                'max:255',
            ],

            'provinsi' => [
                'nullable',
                'string',
                'max:255',
            ],

            'jenis_jalur_ka' => [
                'nullable',
                'string',
                'max:255',
            ],

            'konst_jalan' => [
                'nullable',
                'string',
                'max:255',
            ],

            'titik_koordinat' => [
                'nullable',
                'string',
                'max:255',
            ],

            'lebar_jln' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'status_penjagaan' => [
                'nullable',
                'string',
                'max:255',
            ],

            'keterangan' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ], [
            'resort_id.exists' => 'Resort yang dipilih tidak tersedia atau nonaktif.',
        ]);
    }

    /**
     * Menambahkan JPL beserta inventaris default.
     */
    public function store(
        Request $request,
        DefaultInventarisService $inventarisService,
        ActivityLogService $activityLogService
    ): RedirectResponse {
        $validated = $this->validateJpl($request);

        $validated['is_active'] =
            $validated['is_active'] ?? true;

        DB::transaction(function () use (
            $validated,
            $inventarisService,
            $activityLogService
        ) {
            $jpl = Jpl::create($validated);

            $inventarisService->initializeForJpl($jpl);

            $activityLogService->log(
                'create',
                'jpl',
                'JPL berhasil dibuat beserta inventaris default.',
                $jpl,
                null,
                $this->activityValues($jpl),
            );
        });

        return redirect()
            ->route('admin.jpl.index')
            ->with(
                'success',
                'JPL berhasil ditambahkan beserta inventaris default.'
            );
    }

    /**
     * Menampilkan detail JPL.
     */
    public function show(string $id): View
    {
        $jpl = Jpl::with([
            'resort',
            'dinas',
            'jadwalKa',
            'inventaris',
        ])->findOrFail($id);

        return view('admin.jpl.show', compact('jpl'));
    }

    /**
     * Menampilkan halaman edit JPL.
     */
    public function edit(string $id): View
    {
        $jpl = Jpl::with('resort')
            ->findOrFail($id);

        $resorts = Resort::query()
            ->where(function ($query) use ($jpl) {
                $query->where('is_active', true)
                    ->orWhere('id', $jpl->resort_id);
            })
            ->orderBy('nama_resort')
            ->get();

        return view('admin.jpl.edit', compact(
            'jpl',
            'resorts'
        ));
    }

    /**
     * Memperbarui data JPL.
     */
    public function update(
        Request $request,
        string $id,
        ActivityLogService $activityLogService
    ): RedirectResponse {
        $jpl = Jpl::findOrFail($id);

        $validated = $this->validateJpl(
            $request,
            $jpl
        );

        /*
         * Pertahankan status lama jika checkbox
         * is_active tidak dikirim oleh form.
         */
        if (! array_key_exists('is_active', $validated)) {
            $validated['is_active'] = $jpl->is_active;
        }

        DB::transaction(function () use ($jpl, $validated, $activityLogService): void {
            $oldValues = $this->activityValues($jpl);
            $jpl->update($validated);
            $newValues = $this->activityValues($jpl);

            if ($oldValues !== $newValues) {
                $activityLogService->log(
                    'update',
                    'jpl',
                    'Data JPL berhasil diperbarui.',
                    $jpl,
                    $oldValues,
                    $newValues,
                );
            }
        });

        return redirect()
            ->route('admin.jpl.index')
            ->with(
                'success',
                'Data JPL berhasil diperbarui.'
            );
    }

    /**
     * Menonaktifkan JPL tanpa menghapus riwayatnya.
     */
    public function destroy(string $id, ActivityLogService $activityLogService): RedirectResponse
    {
        $jpl = Jpl::findOrFail($id);

        if (! $jpl->is_active) {
            return redirect()
                ->route('admin.jpl.index')
                ->with(
                    'error',
                    'JPL tersebut sudah nonaktif.'
                );
        }

        DB::transaction(function () use ($jpl, $activityLogService): void {
            $oldValues = $this->activityValues($jpl);
            $jpl->update(['is_active' => false]);

            $activityLogService->log(
                'deactivate',
                'jpl',
                'JPL berhasil dinonaktifkan.',
                $jpl,
                $oldValues,
                $this->activityValues($jpl),
            );
        });

        return redirect()
            ->route('admin.jpl.index')
            ->with(
                'success',
                'JPL berhasil dinonaktifkan.'
            );
    }

    /**
     * Mengaktifkan kembali JPL.
     */
    public function activate(string $id, ActivityLogService $activityLogService): RedirectResponse
    {
        $jpl = Jpl::findOrFail($id);

        if ($jpl->is_active) {
            return redirect()
                ->route('admin.jpl.index')
                ->with(
                    'error',
                    'JPL tersebut sudah aktif.'
                );
        }

        DB::transaction(function () use ($jpl, $activityLogService): void {
            $oldValues = $this->activityValues($jpl);
            $jpl->update(['is_active' => true]);

            $activityLogService->log(
                'activate',
                'jpl',
                'JPL berhasil diaktifkan kembali.',
                $jpl,
                $oldValues,
                $this->activityValues($jpl),
            );
        });

        return redirect()
            ->route('admin.jpl.index')
            ->with(
                'success',
                'JPL berhasil diaktifkan kembali.'
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function activityValues(Jpl $jpl): array
    {
        return [
            'kode_jpl' => $jpl->kode_jpl,
            'nama_jpl' => $jpl->nama_jpl,
            'resort_id' => $jpl->resort_id,
            'antara' => $jpl->antara,
            'km_hm' => $jpl->km_hm,
            'lokasi' => $jpl->lokasi,
            'kota_kabupaten' => $jpl->kota_kabupaten,
            'provinsi' => $jpl->provinsi,
            'jenis_jalur_ka' => $jpl->jenis_jalur_ka,
            'konst_jalan' => $jpl->konst_jalan,
            'titik_koordinat' => $jpl->titik_koordinat,
            'lebar_jln' => $jpl->lebar_jln,
            'status_penjagaan' => $jpl->status_penjagaan,
            'is_active' => $jpl->is_active,
        ];
    }
}
