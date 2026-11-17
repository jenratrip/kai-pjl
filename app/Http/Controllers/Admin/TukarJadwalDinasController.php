<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermintaanTukarJadwal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TukarJadwalDinasController extends Controller
{
    /**
     * Daftar permintaan tukar jadwal untuk monitoring admin.
     *
     * Admin dan superadmin hanya memiliki akses read-only.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:menunggu,ditolak,dibatalkan,berhasil,gagal',
            ],
            'q' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $query = PermintaanTukarJadwal::query()
            ->with([
                'dinasPemohon.user',
                'dinasPemohon.jpl.resort',
                'dinasPemohon.shift',

                'dinasTujuan.user',
                'dinasTujuan.jpl.resort',
                'dinasTujuan.shift',

                'pemohon',
                'penerima',
            ]);

        /*
         * Filter status.
         */
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        /*
         * Pencarian nama dan NIP.
         *
         * Pencarian menggunakan relasi user sekaligus snapshot
         * agar riwayat tetap dapat ditemukan setelah user dihapus.
         */
        if (! empty($validated['q'])) {
            $keyword = trim($validated['q']);

            $query->where(function ($q) use ($keyword) {
                $q->whereHas('pemohon', function ($user) use ($keyword) {
                    $user->where('name', 'like', "%{$keyword}%")
                        ->orWhere('nip', 'like', "%{$keyword}%");
                })
                    ->orWhereHas('penerima', function ($user) use ($keyword) {
                        $user->where('name', 'like', "%{$keyword}%")
                            ->orWhere('nip', 'like', "%{$keyword}%");
                    })
                    ->orWhere('pemohon_name', 'like', "%{$keyword}%")
                    ->orWhere('pemohon_nip', 'like', "%{$keyword}%")
                    ->orWhere('penerima_name', 'like', "%{$keyword}%")
                    ->orWhere('penerima_nip', 'like', "%{$keyword}%");
            });
        }

        /*
         * Prioritaskan permintaan yang masih menunggu.
         */
        $permintaan = $query
            ->orderByRaw("
                CASE
                    WHEN status = 'menunggu' THEN 0
                    ELSE 1
                END
            ")
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        /*
         * Statistik keseluruhan permintaan.
         *
         * Tidak mengikuti filter status maupun pencarian.
         */
        $statusCounts = PermintaanTukarJadwal::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return view('admin.tukar-jadwal-dinas.index', compact(
            'permintaan',
            'statusCounts'
        ));
    }

    /**
     * JSON endpoint untuk polling dashboard.
     *
     * Digunakan untuk memperbarui daftar permintaan secara berkala.
     */
    public function updates(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => [
                'nullable',
                'in:menunggu,ditolak,dibatalkan,berhasil,gagal',
            ],
            'status_filter' => [
                'nullable',
                'in:menunggu_saja,semua',
            ],
        ]);

        $query = PermintaanTukarJadwal::query()
            ->with([
                'dinasPemohon.user',
                'dinasPemohon.jpl.resort',

                'dinasTujuan.user',
                'dinasTujuan.jpl.resort',

                'pemohon',
                'penerima',
            ]);

        /*
         * Filter status.
         */
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        } elseif (($validated['status_filter'] ?? null) === 'menunggu_saja') {
            $query->where('status', 'menunggu');
        }

        /*
         * Ambil maksimal 20 data terbaru.
         */
        $items = $query
            ->orderByRaw("
                CASE
                    WHEN status = 'menunggu' THEN 0
                    ELSE 1
                END
            ")
            ->latest('id')
            ->limit(20)
            ->get();

        /*
         * Normalisasi data untuk frontend.
         *
         * Relasi menjadi sumber utama.
         * Snapshot menjadi fallback jika data relasi sudah dihapus.
         */
        $data = $items->map(function (PermintaanTukarJadwal $item) {
            $snapshotPemohon = $item->dinas_pemohon_snapshot ?? [];
            $snapshotTujuan = $item->dinas_tujuan_snapshot ?? [];

            $snapshotPemohonJpl = $snapshotPemohon['jpl'] ?? [];
            $snapshotTujuanJpl = $snapshotTujuan['jpl'] ?? [];

            $namaPemohon = $item->pemohon?->name
                ?? $item->pemohon_name
                ?? 'Tidak tersedia';

            $nipPemohon = $item->pemohon?->nip
                ?? $item->pemohon_nip
                ?? '—';

            $namaPenerima = $item->penerima?->name
                ?? $item->penerima_name
                ?? 'Tidak tersedia';

            $nipPenerima = $item->penerima?->nip
                ?? $item->penerima_nip
                ?? '—';

            $namaJplPemohon = $item->dinasPemohon?->jpl?->nama_jpl
                ?? $item->dinasPemohon?->jpl?->kode_jpl
                ?? $snapshotPemohonJpl['nama_jpl']
                ?? $snapshotPemohonJpl['kode_jpl']
                ?? '—';

            $namaJplTujuan = $item->dinasTujuan?->jpl?->nama_jpl
                ?? $item->dinasTujuan?->jpl?->kode_jpl
                ?? $snapshotTujuanJpl['nama_jpl']
                ?? $snapshotTujuanJpl['kode_jpl']
                ?? '—';

            return [
                'id' => $item->id,

                'pemohon' => $namaPemohon,
                'nip_pemohon' => $nipPemohon,

                'penerima' => $namaPenerima,
                'nip_penerima' => $nipPenerima,

                'jpl_pemohon' => $namaJplPemohon,
                'jpl_tujuan' => $namaJplTujuan,

                'status' => $item->status,

                'status_label' => match ($item->status) {
                    'menunggu' => 'Menunggu',
                    'ditolak' => 'Ditolak',
                    'dibatalkan' => 'Dibatalkan',
                    'berhasil' => 'Berhasil',
                    'gagal' => 'Gagal',
                    default => $item->status,
                },

                'diajukan_pada' => $item->diajukan_pada
                    ? $item->diajukan_pada->format('d/m/Y H:i')
                    : '—',

                'dijawab_pada' => $item->dijawab_pada
                    ? $item->dijawab_pada->format('d/m/Y H:i')
                    : '—',

                'detail_url' => route(
                    'admin.tukar-jadwal-dinas.show',
                    $item->id
                ),
            ];
        });

        /*
         * Jumlah seluruh permintaan yang masih menunggu.
         */
        $totalMenunggu = PermintaanTukarJadwal::query()
            ->where('status', 'menunggu')
            ->count();

        return response()->json([
            'success' => true,
            'items' => $data,
            'total_menunggu' => $totalMenunggu,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detail permintaan tukar jadwal.
     *
     * Admin dan superadmin hanya dapat melihat.
     */
    public function show(PermintaanTukarJadwal $permintaan): View
    {
        $permintaan->load([
            'dinasPemohon.user',
            'dinasPemohon.jpl.resort',
            'dinasPemohon.shift',

            'dinasTujuan.user',
            'dinasTujuan.jpl.resort',
            'dinasTujuan.shift',

            'pemohon',
            'penerima',
        ]);

        /*
         * Siapkan fallback snapshot untuk kebutuhan tampilan detail.
         *
         * Snapshot tidak menggantikan relasi.
         * Data relasi tetap diprioritaskan jika tersedia.
         */
        $snapshotPemohon = $permintaan->dinas_pemohon_snapshot ?? [];
        $snapshotTujuan = $permintaan->dinas_tujuan_snapshot ?? [];

        $identitasPemohon = [
            'nama' => $permintaan->pemohon?->name
                ?? $permintaan->pemohon_name
                ?? 'Tidak tersedia',

            'nip' => $permintaan->pemohon?->nip
                ?? $permintaan->pemohon_nip
                ?? '—',
        ];

        $identitasPenerima = [
            'nama' => $permintaan->penerima?->name
                ?? $permintaan->penerima_name
                ?? 'Tidak tersedia',

            'nip' => $permintaan->penerima?->nip
                ?? $permintaan->penerima_nip
                ?? '—',
        ];

        return view('admin.tukar-jadwal-dinas.show', compact(
            'permintaan',
            'snapshotPemohon',
            'snapshotTujuan',
            'identitasPemohon',
            'identitasPenerima'
        ));
    }
}
