<?php

namespace App\Services;

use App\Models\CatatanDinas;
use App\Models\Dinas;
use Illuminate\Support\Collection;

class CatatanSiklusService
{
    /**
     * Catatan satu siklus (shift 1 sampai 3) pada JPL dinas aktif.
     * Siklus baru dimulai saat shift pagi (shift 1) berikutnya aktif.
     */
    public function untuk(?Dinas $dinasAktif): Collection
    {
        if (! $dinasAktif || ! $dinasAktif->jpl_id) {
            return collect();
        }

        $jplId = (int) $dinasAktif->jpl_id;

        $tanggalAktif = substr(
            trim((string) $dinasAktif->getRawOriginal('tanggal_dinas')),
            0,
            10
        );

        $tanggalMulai = $this->tanggalMulaiSiklus(
            $dinasAktif,
            $jplId,
            $tanggalAktif
        );

        return CatatanDinas::with([
            'user',
            'dinas.user',
            'dinas.shift',
            'dinas.jpl.resort',
        ])
            ->whereHas('dinas', function ($query) use ($jplId, $tanggalMulai, $tanggalAktif) {
                $query->where('jpl_id', $jplId)
                    ->whereDate('tanggal_dinas', '>=', $tanggalMulai)
                    ->whereDate('tanggal_dinas', '<=', $tanggalAktif);
            })
            ->latest('waktu_catatan')
            ->get();
    }

    /**
     * Tanggal shift pagi terakhir pada JPL yang sama
     * (sampai dengan tanggal dinas aktif).
     */
    private function tanggalMulaiSiklus(
        Dinas $dinasAktif,
        int $jplId,
        string $tanggalAktif
    ): string {
        $namaShift = mb_strtolower(
            trim((string) $dinasAktif->shift?->nama_shift)
        );

        // Dinas aktif adalah shift pagi: siklus baru dimulai hari ini.
        if (str_contains($namaShift, 'pagi')) {
            return $tanggalAktif;
        }

        $tanggalPagi = Dinas::where('jpl_id', $jplId)
            ->whereDate('tanggal_dinas', '<=', $tanggalAktif)
            ->whereHas('shift', function ($query) {
                $query->where('nama_shift', 'like', '%pagi%');
            })
            ->orderByDesc('tanggal_dinas')
            ->value('tanggal_dinas');

        if (! $tanggalPagi) {
            return $tanggalAktif;
        }

        return substr(trim((string) $tanggalPagi), 0, 10);
    }
}