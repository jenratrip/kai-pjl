<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SerahTerima;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SerahTerimaDinasController extends Controller
{
    /**
     * Relasi yang digunakan pada daftar dan detail.
     */
    private function relasi(): array
    {
        return [
            'dinasPenyerah.user',
            'dinasPenyerah.jpl.resort.wilayah',
            'dinasPenyerah.shift',
            'penerima',
        ];
    }

    /**
     * Query dasar: hanya pengajuan milik akun penerima yang login.
     */
    private function queryPengajuan(Request $request)
    {
        $query = SerahTerima::with($this->relasi())
            ->where('penerima_user_id', auth()->id());

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return $query;
    }

    /**
     * Halaman daftar pengajuan.
     */
    public function index(Request $request): View
    {
        $serahTerima = $this->queryPengajuan($request)
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view(
            'admin.serah-terima-dinas.index',
            compact('serahTerima')
        );
    }

    /**
     * Data JSON untuk pembaruan tabel dan notifikasi polling.
     *
     * Endpoint ini dipanggil JavaScript secara berkala.
     */
    public function updates(Request $request): JsonResponse
    {
        $request->validate([
            'status' => [
                'nullable',
                'in:menunggu_penerima,selesai',
            ],
        ]);

        $items = $this->queryPengajuan($request)
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

                'nip' => $dinas?->user?->nip
                    ?? '—',

                'jpl' => $jpl?->nama_jpl
                    ?? $jpl?->kode_jpl
                    ?? '—',

                'waktu' => $item->waktu_serah_terima
                    ? $item->waktu_serah_terima->format('d/m/Y H:i')
                    : '—',

                'status' => $item->status,

                'status_label' => match ($item->status) {
                    'menunggu_penerima' => 'Menunggu konfirmasi',
                    'selesai' => 'Selesai',
                    default => ucfirst(
                        str_replace('_', ' ', $item->status ?? 'Tidak diketahui')
                    ),
                },

                'detail_url' => route(
                    'admin.serah-terima-dinas.show',
                    $item->id
                ),
            ];
        });

        return response()->json([
            'success' => true,
            'items' => $data,
            'total_menunggu' => SerahTerima::query()
                ->where('penerima_user_id', auth()->id())
                ->where('status', 'menunggu_penerima')
                ->count(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detail pengajuan.
     */
    public function show(SerahTerima $serahTerima): View
    {
        abort_unless(
            (int) $serahTerima->penerima_user_id ===
                (int) auth()->id(),
            403,
            'Pengajuan ini bukan ditujukan kepada akun Anda.'
        );

        $serahTerima->load($this->relasi());

        return view(
            'admin.serah-terima-dinas.show',
            compact('serahTerima')
        );
    }
}
