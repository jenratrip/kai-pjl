<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard petugas PJL.
     */
    public function index()
    {
        $user = auth()->user();

        // Ambil dinas aktif milik petugas yang sedang login.
        $dinasAktif = Dinas::with([
            'user',
            'jpl',
            'shift',
            'catatanDinas',
        ])
            ->where('user_id', $user->id)
            ->where('status', 'berlangsung')
            ->latest('id')
            ->first();

        // Default jika petugas belum memiliki dinas aktif.
        $lokasiDinas = '-';
        $namaShift = '-';
        $jamDinas = '-';
        $tanggalTugas = '-';
        $catatanAktif = null;

        if ($dinasAktif) {
            // Informasi lokasi PJL.
            $lokasiDinas =
                $dinasAktif->jpl?->nama_jpl
                ?? $dinasAktif->jpl?->kode_jpl
                ?? $dinasAktif->jpl?->lokasi
                ?? '-';

            // Informasi shift.
            $namaShift = $dinasAktif->shift?->nama_shift ?? '-';

            $jamMulai = $this->formatJam(
                $dinasAktif->jam_mulai
                ?? $dinasAktif->shift?->jam_mulai
            );

            $jamSelesai = $this->formatJam(
                $dinasAktif->jam_selesai
                ?? $dinasAktif->shift?->jam_selesai
            );

            if ($jamMulai !== '-' && $jamSelesai !== '-') {
                $jamDinas = "{$jamMulai} - {$jamSelesai}";
            } elseif ($jamMulai !== '-') {
                $jamDinas = $jamMulai;
            } elseif ($jamSelesai !== '-') {
                $jamDinas = $jamSelesai;
            }

            // Format tanggal tugas dalam bahasa Indonesia.
            if ($dinasAktif->tanggal_dinas) {
                $tanggal = Carbon::parse(
                    $dinasAktif->tanggal_dinas
                );

                $hari = [
                    1 => 'Senin',
                    2 => 'Selasa',
                    3 => 'Rabu',
                    4 => 'Kamis',
                    5 => 'Jumat',
                    6 => 'Sabtu',
                    7 => 'Minggu',
                ];

                $bulan = [
                    1 => 'Januari',
                    2 => 'Februari',
                    3 => 'Maret',
                    4 => 'April',
                    5 => 'Mei',
                    6 => 'Juni',
                    7 => 'Juli',
                    8 => 'Agustus',
                    9 => 'September',
                    10 => 'Oktober',
                    11 => 'November',
                    12 => 'Desember',
                ];

                $tanggalTugas =
                    $hari[$tanggal->dayOfWeekIso]
                    . ', '
                    . $tanggal->day
                    . ' '
                    . $bulan[$tanggal->month]
                    . ' '
                    . $tanggal->year;
            }

            // Ambil catatan terbaru yang terkait dengan dinas aktif.
            $catatanAktif = $dinasAktif->catatanDinas()
                ->orderByDesc('waktu_catatan')
                ->orderByDesc('id')
                ->first();
        }

        return view('user.dashboard', [
            'user' => $user,
            'dinasAktif' => $dinasAktif,
            'lokasiDinas' => $lokasiDinas,
            'namaShift' => $namaShift,
            'jamDinas' => $jamDinas,
            'tanggalTugas' => $tanggalTugas,
            'catatanAktif' => $catatanAktif,
        ]);
    }

    /**
     * Format jam menjadi HH:MM.
     */
    private function formatJam($jam): string
    {
        if (!$jam) {
            return '-';
        }

        return substr((string) $jam, 0, 5);
    }
}