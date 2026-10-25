<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalKa;
use App\Models\Jpl;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JadwalKaController extends Controller
{
    /**
     * Halaman utama Jadwal KA.
     *
     * Hanya menampilkan JPL yang mempunyai jadwal:
     * - Berlaku hari ini.
     * - Aktif.
     * - Jam lewatnya belum terlewati.
     */
    public function index(Request $request): View
    {
        $sekarang = Carbon::now();
        $tanggalHariIni = $sekarang->toDateString();
        $jamSekarang = $sekarang->format('H:i:s');
        $namaHari = $this->namaHariIndonesia($sekarang);

        $counts = JadwalKa::query()
            ->where('is_active', true)
            ->where('jam_lewat', '>=', $jamSekarang)
            ->where(function ($query) use (
                $tanggalHariIni,
                $namaHari
            ) {
                // Jadwal khusus tanggal hari ini.
                $query->whereDate(
                    'tanggal_berlaku',
                    $tanggalHariIni
                )

                // Jadwal berulang sesuai hari ini.
                ->orWhere(function ($rutin) use ($namaHari) {
                    $rutin
                        ->whereNull('tanggal_berlaku')
                        ->whereNotNull('hari_berlaku')
                        ->whereRaw(
                            'LOWER(hari_berlaku) LIKE ?',
                            ['%' . mb_strtolower($namaHari) . '%']
                        );
                });
            })
            ->selectRaw('jpl_id, COUNT(*) as aggregate')
            ->groupBy('jpl_id')
            ->pluck('aggregate', 'jpl_id');

        $jpls = Jpl::query()
            ->whereIn('id', $counts->keys())
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = trim($request->input('q'));

                $query->where(function ($search) use ($keyword) {
                    $search
                        ->where('kode_jpl', 'like', "%{$keyword}%")
                        ->orWhere('nama_jpl', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('kode_jpl')
            ->paginate(25)
            ->withQueryString();

        $jpls->getCollection()->transform(function ($jpl) use ($counts) {
            $jpl->jadwal_count = (int) ($counts[$jpl->id] ?? 0);

            return $jpl;
        });

        return view('admin.jadwal-ka.index', compact(
            'jpls',
            'tanggalHariIni'
        ));
    }

    /**
     * Detail jadwal KA berdasarkan JPL.
     *
     * Jadwal yang sudah lewat tidak ditampilkan pada
     * daftar operasional hari ini.
     */
    public function show(Request $request, Jpl $jpl): View
    {
        $sekarang = Carbon::now();
        $tanggalHariIni = $sekarang->toDateString();
        $jamSekarang = $sekarang->format('H:i:s');
        $namaHari = $this->namaHariIndonesia($sekarang);

        $jadwal = JadwalKa::query()
            ->with(['keretaApi', 'jpl'])
            ->where('jpl_id', $jpl->id)
            ->where('jam_lewat', '>=', $jamSekarang)

            // Filter status yang diminta pengguna.
            ->when($request->filled('status'), function ($query) use ($request) {
                $status = $request->input('status');

                if ($status === 'aktif') {
                    $query->where('is_active', true);
                } elseif ($status === 'nonaktif') {
                    $query->where('is_active', false);
                }
            }, function ($query) {
                // Default hanya jadwal aktif.
                $query->where('is_active', true);
            })

            // Pencarian nomor atau nama KA.
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = trim($request->input('q'));

                $query->whereHas('keretaApi', function ($kereta) use ($keyword) {
                    $kereta->where(function ($search) use ($keyword) {
                        $search
                            ->where('nomor_ka', 'like', "%{$keyword}%")
                            ->orWhere('nama_ka', 'like', "%{$keyword}%");
                    });
                });
            })

            // Hanya jadwal yang berlaku hari ini.
            ->where(function ($query) use (
                $tanggalHariIni,
                $namaHari
            ) {
                $query->whereDate(
                    'tanggal_berlaku',
                    $tanggalHariIni
                )

                ->orWhere(function ($rutin) use ($namaHari) {
                    $rutin
                        ->whereNull('tanggal_berlaku')
                        ->whereNotNull('hari_berlaku')
                        ->whereRaw(
                            'LOWER(hari_berlaku) LIKE ?',
                            ['%' . mb_strtolower($namaHari) . '%']
                        );
                });
            })
            ->orderBy('jam_lewat')
            ->paginate(25)
            ->withQueryString();

        return view('admin.jadwal-ka.show', compact(
            'jpl',
            'jadwal',
            'tanggalHariIni'
        ));
    }

    /**
     * Daftar arsip jadwal berdasarkan tanggal.
     *
     * Arsip ini menampilkan jadwal yang memiliki tanggal
     * tertentu selain tanggal hari ini.
     */
    public function arsip(Request $request): View
    {
        $tanggalHariIni = Carbon::today()->toDateString();

        $arsip = DB::table('jadwal_ka')
            ->selectRaw(
                'DATE(tanggal_berlaku) as tanggal,
                 COUNT(*) as total_jadwal,
                 COUNT(DISTINCT jpl_id) as total_jpl'
            )
            ->whereNotNull('tanggal_berlaku')
            ->whereDate('tanggal_berlaku', '<', $tanggalHariIni)

            // Filter berdasarkan tanggal.
            ->when($request->filled('tanggal'), function ($query) use ($request) {
                try {
                    $tanggal = Carbon::createFromFormat(
                        '!Y-m-d',
                        $request->input('tanggal')
                    );

                    if (
                        !$tanggal ||
                        $tanggal->format('Y-m-d') !== $request->input('tanggal')
                    ) {
                        return $query->whereRaw('1 = 0');
                    }

                    return $query->whereDate(
                        'tanggal_berlaku',
                        $tanggal->toDateString()
                    );
                } catch (\Throwable $e) {
                    return $query->whereRaw('1 = 0');
                }
            })

            // Filter berdasarkan kode atau nama JPL.
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = trim($request->input('q'));

                $query->whereExists(function ($subquery) use ($keyword) {
                    $subquery->selectRaw('1')
                        ->from('jpl')
                        ->whereColumn('jpl.id', 'jadwal_ka.jpl_id')
                        ->where(function ($search) use ($keyword) {
                            $search
                                ->where('jpl.kode_jpl', 'like', "%{$keyword}%")
                                ->orWhere('jpl.nama_jpl', 'like', "%{$keyword}%");
                        });
                });
            })
            ->groupByRaw('DATE(tanggal_berlaku)')
            ->orderByDesc('tanggal')
            ->paginate(20)
            ->withQueryString();

        return view('admin.jadwal-ka.arsip', compact(
            'arsip',
            'tanggalHariIni'
        ));
    }

    /**
     * Detail arsip berdasarkan tanggal.
     *
     * Mendukung tanggal dari parameter route maupun query string.
     */
    public function showArsip(
        Request $request,
        ?string $tanggal = null
    ): View {
        $tanggalInput = $tanggal ?? $request->query('tanggal');

        abort_if(
            !is_string($tanggalInput) || $tanggalInput === '',
            404,
            'Tanggal arsip tidak ditemukan.'
        );

        try {
            $parsedTanggal = Carbon::createFromFormat(
                '!Y-m-d',
                $tanggalInput
            );
        } catch (\Throwable $e) {
            abort(404, 'Format tanggal arsip tidak valid.');
        }

        abort_if(
            !$parsedTanggal ||
            $parsedTanggal->format('Y-m-d') !== $tanggalInput,
            404,
            'Tanggal arsip tidak valid.'
        );

        $tanggal = $parsedTanggal->toDateString();
        $tanggalHariIni = Carbon::today()->toDateString();

        // Tanggal hari ini bukan arsip.
        if ($tanggal >= $tanggalHariIni) {
            return redirect()->route('admin.jadwal-ka.index');
        }

        $jadwal = JadwalKa::query()
            ->with(['keretaApi', 'jpl'])
            ->whereDate('tanggal_berlaku', $tanggal)
            ->orderBy('jpl_id')
            ->orderBy('jam_lewat')
            ->paginate(50)
            ->withQueryString();

        return view('admin.jadwal-ka.showarsip', compact(
            'jadwal',
            'tanggal',
            'tanggalHariIni'
        ));
    }

    /**
     * Mengubah tanggal menjadi nama hari Indonesia.
     */
    private function namaHariIndonesia(Carbon $tanggal): string
    {
        $hari = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        return $hari[$tanggal->dayOfWeekIso];
    }
}
