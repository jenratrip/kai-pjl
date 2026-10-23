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
     * Daftar untuk review Admin.
     * Filter status menggunakan status_review, bukan status tanda tangan.
     */
    private function queryReview(Request $request)
    {
        return SerahTerima::with($this->relasi())
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
                    $keyword = trim((string) $request->input('q'));

                    $query->whereHas('dinasPenyerah', function ($dinas) use ($keyword) {
                        $dinas->whereHas('user', function ($user) use ($keyword) {
                            $user->where('name', 'like', "%{$keyword}%")
                                ->orWhere('nip', 'like', "%{$keyword}%");
                        })->orWhereHas('jpl', function ($jpl) use ($keyword) {
                            $jpl->where('nama_jpl', 'like', "%{$keyword}%")
                                ->orWhere('kode_jpl', 'like', "%{$keyword}%");
                        });
                    });
                }
            );
    }

    public function index(Request $request): View
    {
        $serahTerima = $this->queryReview($request)
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $totalMenungguReview = SerahTerima::where(
            'status_review',
            'menunggu_review'
        )->count();

        return view(
            'admin.serah-terima-dinas.index',
            compact('serahTerima', 'totalMenungguReview')
        );
    }

    /**
     * Data untuk pembaruan daftar otomatis pada halaman Admin.
     */
    public function updates(Request $request): JsonResponse
    {
        $request->validate([
            'status_review' => [
                'nullable',
                'in:menunggu_review,disetujui,perlu_revisi',
            ],
        ]);

        $items = $this->queryReview($request)
            ->latest('id')
            ->limit(10)
            ->get();

        $data = $items->map(function (SerahTerima $item) {
            $dinas = $item->dinasPenyerah;
            $jpl = $dinas?->jpl;

            return [
                'id' => $item->id,
                'petugas' => $dinas?->user?->name ?? 'Tidak tersedia',
                'nip' => $dinas?->user?->nip ?? '—',
                'jpl' => $jpl?->nama_jpl ?? $jpl?->kode_jpl ?? '—',
                'waktu' => $item->waktu_serah_terima
                    ? $item->waktu_serah_terima->format('d/m/Y H:i')
                    : '—',

                // Status alur serah terima/tanda tangan.
                'status' => $item->status,

                // Status penilaian Admin.
                'status_review' => $item->status_review ?? 'menunggu_review',
                'status_review_label' => match ($item->status_review) {
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

        return response()->json([
            'success' => true,
            'items' => $data,
            'total_menunggu' => SerahTerima::where(
                'status_review',
                'menunggu_review'
            )->count(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function show(SerahTerima $serahTerima): View
    {
        $serahTerima->load($this->relasi());

        return view(
            'admin.serah-terima-dinas.show',
            compact('serahTerima')
        );
    }

    /**
     * Review Admin. Tidak mengubah status tanda tangan serah terima.
     */
    public function update(
        Request $request,
        SerahTerima $serahTerima
    ): RedirectResponse {
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
            'status_review.required' => 'Pilih hasil review terlebih dahulu.',
            'status_review.in' => 'Pilihan review tidak valid.',
            'catatan_revisi.required_if' => 'Catatan wajib diisi jika meminta revisi.',
            'catatan_revisi.max' => 'Catatan revisi maksimal 5000 karakter.',
        ]);

        DB::transaction(function () use ($serahTerima, $validated) {
            $serahTerima->status_review = $validated['status_review'];

            $serahTerima->catatan_revisi =
                $validated['status_review'] === 'perlu_revisi'
                    ? trim($validated['catatan_revisi'])
                    : null;

            $serahTerima->ditinjau_oleh = auth()->id();
            $serahTerima->ditinjau_pada = now();

            // Jangan ubah $serahTerima->status atau field tanda tangan.
            $serahTerima->save();
        });

        return redirect()
            ->route('admin.serah-terima-dinas.show', $serahTerima->id)
            ->with(
                'success',
                $validated['status_review'] === 'disetujui'
                    ? 'Serah Terima Dinas berhasil disetujui.'
                    : 'Serah Terima Dinas dikembalikan untuk revisi.'
            );
    }
}
