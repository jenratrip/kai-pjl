<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SerahTerima;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SerahTerimaDinasController extends Controller
{
    /**
     * Status serah terima yang dapat dilihat Admin.
     * Draft tidak ditampilkan karena belum dikirim petugas.
     */
    private const STATUS_TAMPIL = [
        'menunggu_penerima',
        'selesai',
    ];

    /**
     * Relasi yang digunakan pada halaman daftar dan detail.
     */
    private function relasi(): array
    {
        return [
            'dinasPenyerah.user',
            'dinasPenyerah.jpl.resort.wilayah',
            'dinasPenyerah.shift',
            'penerima',
            'ditinjauOleh',
        ];
    }

    /**
     * Pastikan hanya Admin dan Superadmin yang dapat mengakses.
     */
    private function pastikanAdmin(): void
    {
        abort_unless(
            in_array(
                (int) auth()->user()?->role_id,
                [2, 3],
                true
            ),
            403,
            'Hanya admin atau superadmin yang dapat melihat serah terima dinas.'
        );
    }

    /**
     * Query daftar serah terima sekaligus mendukung:
     * - Filter status serah terima.
     * - Filter status review Admin.
     * - Pencarian petugas, NIP, dan JPL.
     */
    private function queryReview(Request $request)
    {
        return SerahTerima::with($this->relasi())
            ->whereIn('status', self::STATUS_TAMPIL)
            ->when(
                $request->filled('status'),
                fn ($query) => $query->where(
                    'status',
                    $request->input('status')
                )
            )
            ->when(
                $request->filled('status_review'),
                fn ($query) => $query->where(
                    'status_review',
                    $request->input('status_review')
                )
            )
            ->when(
                $request->filled('q'),
                function ($query) use ($request) {
                    $keyword = trim(
                        (string) $request->input('q')
                    );

                    $query->whereHas(
                        'dinasPenyerah',
                        function ($dinas) use ($keyword) {
                            $dinas->whereHas(
                                'user',
                                function ($user) use ($keyword) {
                                    $user->where(
                                        'name',
                                        'like',
                                        "%{$keyword}%"
                                    )->orWhere(
                                        'nip',
                                        'like',
                                        "%{$keyword}%"
                                    );
                                }
                            )->orWhereHas(
                                'jpl',
                                function ($jpl) use ($keyword) {
                                    $jpl->where(
                                        'nama_jpl',
                                        'like',
                                        "%{$keyword}%"
                                    )->orWhere(
                                        'kode_jpl',
                                        'like',
                                        "%{$keyword}%"
                                    );
                                }
                            );
                        }
                    );
                }
            );
    }

    /**
     * Daftar serah terima untuk Admin.
     */
    public function index(Request $request): View
    {
        $this->pastikanAdmin();

        $request->validate([
            'status' => [
                'nullable',
                'in:menunggu_penerima,selesai',
            ],
            'status_review' => [
                'nullable',
                'in:menunggu_review,disetujui,perlu_revisi',
            ],
        ]);

        $serahTerima = $this->queryReview($request)
            ->orderByRaw(
                "CASE WHEN status = 'menunggu_penerima' THEN 0 ELSE 1 END"
            )
            ->latest('waktu_serah_terima')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $totalMenungguReview = SerahTerima::query()
            ->whereIn('status', self::STATUS_TAMPIL)
            ->where('status_review', 'menunggu_review')
            ->count();

        return view(
            'admin.serah-terima-dinas.index',
            compact(
                'serahTerima',
                'totalMenungguReview'
            )
        );
    }

    /**
     * Data untuk pembaruan daftar otomatis.
     */
    public function updates(Request $request): JsonResponse
    {
        $this->pastikanAdmin();

        $request->validate([
            'status' => [
                'nullable',
                'in:menunggu_penerima,selesai',
            ],
            'status_review' => [
                'nullable',
                'in:menunggu_review,disetujui,perlu_revisi',
            ],
        ]);

        $items = $this->queryReview($request)
            ->orderByRaw(
                "CASE WHEN status = 'menunggu_penerima' THEN 0 ELSE 1 END"
            )
            ->latest('waktu_serah_terima')
            ->latest('id')
            ->limit(10)
            ->get();

        $data = $items->map(function (SerahTerima $item) {
            $dinas = $item->dinasPenyerah;
            $jpl = $dinas?->jpl;

            return [
                'id' => $item->id,
                'petugas' => $dinas?->user?->name
                    ?? 'Tidak tersedia',
                'nip' => $dinas?->user?->nip ?? '—',
                'jpl' => $jpl?->nama_jpl
                    ?? $jpl?->kode_jpl
                    ?? '—',
                'penerima' => data_get(
                    $item->ringkasan,
                    'nama_penerima'
                ) ?: ($item->penerima?->name ?? '—'),
                'waktu' => $item->waktu_serah_terima
                    ? $item->waktu_serah_terima->format('d/m/Y H:i')
                    : '—',
                'status' => $item->status,
                'status_review' => $item->status_review
                    ?? 'menunggu_review',
                'status_review_label' => match (
                    $item->status_review
                ) {
                    'disetujui' => 'Disetujui',
                    'perlu_revisi' => 'Perlu revisi',
                    default => 'Menunggu review',
                },
                'catatan_revisi' => $item->catatan_revisi,
                'detail_url' => route(
                    'admin.serah-terima-dinas.show',
                    $item->id
                ),
            ];
        });

        $totalMenunggu = SerahTerima::query()
            ->whereIn('status', self::STATUS_TAMPIL)
            ->where('status_review', 'menunggu_review')
            ->count();

        return response()->json([
            'success' => true,
            'items' => $data,
            'total_menunggu' => $totalMenunggu,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detail pengajuan serah terima.
     */
    public function show(SerahTerima $serahTerima): View
    {
        $this->pastikanAdmin();

        abort_unless(
            in_array(
                $serahTerima->status,
                self::STATUS_TAMPIL,
                true
            ),
            404
        );

        $serahTerima->load($this->relasi());

        return view(
            'admin.serah-terima-dinas.show',
            compact('serahTerima')
        );
    }

    /**
     * Review Admin tanpa mengubah status serah terima
     * atau data tanda tangan.
     */
    public function update(
        Request $request,
        SerahTerima $serahTerima
    ): RedirectResponse {
        $this->pastikanAdmin();

        abort_unless(
            in_array(
                $serahTerima->status,
                self::STATUS_TAMPIL,
                true
            ),
            404
        );

        $validated = $request->validate([
            'status_review' => [
                'required',
                'in:disetujui,perlu_revisi',
            ],
            'catatan_revisi' => [
                'nullable',
                'required_if:status_review,perlu_revisi',
                'string',
                'max:5000',
            ],
        ], [
            'status_review.required' =>
                'Pilih hasil review terlebih dahulu.',
            'status_review.in' =>
                'Pilihan review tidak valid.',
            'catatan_revisi.required_if' =>
                'Catatan wajib diisi jika meminta revisi.',
            'catatan_revisi.max' =>
                'Catatan revisi maksimal 5000 karakter.',
        ]);

        DB::transaction(function () use (
            $serahTerima,
            $validated
        ) {
            $serahTerima->status_review =
                $validated['status_review'];

            $serahTerima->catatan_revisi =
                $validated['status_review'] === 'perlu_revisi'
                    ? trim($validated['catatan_revisi'])
                    : null;

            $serahTerima->ditinjau_oleh = auth()->id();
            $serahTerima->ditinjau_pada = now();

            // Status serah terima dan tanda tangan tidak diubah.
            $serahTerima->save();
        });

        return redirect()
            ->route(
                'admin.serah-terima-dinas.show',
                $serahTerima->id
            )
            ->with(
                'success',
                $validated['status_review'] === 'disetujui'
                    ? 'Serah Terima Dinas berhasil disetujui.'
                    : 'Serah Terima Dinas dikembalikan untuk revisi.'
            );
    }
}