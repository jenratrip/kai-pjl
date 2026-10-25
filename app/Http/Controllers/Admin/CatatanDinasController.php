<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatatanDinas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatatanDinasController extends Controller
{
    private function queryReview(Request $request)
    {
        return CatatanDinas::with([
            'user',
            'dinas.shift',
            'ditinjauOleh',
            'attachments',
        ])
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

                    $query->where(function ($subQuery) use ($keyword) {
                        $subQuery
                            ->where('judul', 'like', "%{$keyword}%")
                            ->orWhere('isi_catatan', 'like', "%{$keyword}%")
                            ->orWhereHas('user', function ($user) use ($keyword) {
                                $user->where('name', 'like', "%{$keyword}%")
                                    ->orWhere('nip', 'like', "%{$keyword}%");
                            });
                    });
                }
            );
    }

    public function index(Request $request): View
    {
        $catatanDinas = $this->queryReview($request)
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $totalMenungguReview = CatatanDinas::where(
            'status_review',
            'menunggu_review'
        )->count();

        return view(
            'admin.catatan-dinas.index',
            compact('catatanDinas', 'totalMenungguReview')
        );
    }

    public function show(CatatanDinas $catatanDinas): View
    {
        $catatanDinas->load([
            'user',
            'dinas.shift',
            'ditinjauOleh',
            'attachments',
        ]);

        return view(
            'admin.catatan-dinas.show',
            compact('catatanDinas')
        );
    }

    public function update(
        Request $request,
        CatatanDinas $catatanDinas
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

        $catatanDinas->status_review = $validated['status_review'];
        $catatanDinas->catatan_revisi =
            $validated['status_review'] === 'perlu_revisi'
                ? trim($validated['catatan_revisi'])
                : null;
        $catatanDinas->ditinjau_oleh = auth()->id();
        $catatanDinas->ditinjau_pada = now();
        $catatanDinas->save();

        return redirect()
            ->route('admin.catatan-dinas.show', $catatanDinas->id)
            ->with(
                'success',
                $validated['status_review'] === 'disetujui'
                    ? 'Catatan Dinas berhasil disetujui.'
                    : 'Catatan Dinas dikembalikan untuk revisi.'
            );
    }
}
