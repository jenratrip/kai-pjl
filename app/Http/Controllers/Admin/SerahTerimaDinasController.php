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
     * Status yang tampil di halaman admin.
     * Draft tidak ditampilkan karena belum dikirim oleh petugas.
     */
    private const STATUS_TAMPIL = ['menunggu_penerima', 'selesai'];

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
     * Admin atau superadmin saja yang boleh melihat pengajuan.
     */
    private function pastikanAdmin(): void
    {
        abort_unless(
            in_array((int) auth()->user()?->role_id, [2, 3], true),
            403,
            'Hanya admin atau superadmin yang dapat melihat serah terima dinas.'
        );
    }

    /**
     * Query dasar: semua serah terima yang sudah dikirim petugas.
     *
     * Penerima kini cukup diketik namanya oleh petugas, sehingga
     * penerima_user_id bisa kosong. Karena itu daftar tidak lagi
     * difilter berdasarkan akun penerima.
     */
    private function queryPengajuan(Request $request)
    {
        $query = SerahTerima::with($this->relasi())
            ->whereIn('status', self::STATUS_TAMPIL);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Yang menunggu konfirmasi ditampilkan lebih dulu.
        return $query
            ->orderByRaw(
                "CASE WHEN status = 'menunggu_penerima' THEN 0 ELSE 1 END"
            )
            ->latest('waktu_serah_terima')
            ->latest('id');
    }

    /**
     * Halaman daftar pengajuan.
     */
    public function index(Request $request): View
    {
        $this->pastikanAdmin();

        $request->validate([
            'status' => ['nullable', 'in:menunggu_penerima,selesai'],
        ]);

        $serahTerima = $this->queryPengajuan($request)
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
        $this->pastikanAdmin();

        $request->validate([
            'status' => [
                'nullable',
                'in:menunggu_penerima,selesai',
            ],
        ]);

        $items = $this->queryPengajuan($request)
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

                // Nama penerima yang diketik petugas (atau akun penerima).
                'penerima' => data_get($item->ringkasan, 'nama_penerima')
                    ?: ($item->penerima?->name ?? '—'),

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
                ->where('status', 'menunggu_penerima')
                ->count(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detail pengajuan.
     *
     * Admin boleh membuka semua pengajuan yang sudah dikirim.
     * Jika penerima_user_id diisi, konfirmasi tetap dibatasi
     * oleh controller konfirmasi pada akun yang dituju.
     */
    public function show(SerahTerima $serahTerima): View
    {
        $this->pastikanAdmin();

        abort_unless(
            in_array($serahTerima->status, self::STATUS_TAMPIL, true),
            404
        );

        $serahTerima->load($this->relasi());

        return view(
            'admin.serah-terima-dinas.show',
            compact('serahTerima')
        );
    }
}