<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\JadwalKa;
use App\Models\JadwalKaBatch;
use App\Models\Jpl;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JadwalKaController extends Controller
{
    public function index(Request $request): View
    {
        $sekarang = Carbon::now();
        $tanggalHariIni = $sekarang->toDateString();
        $jamSekarang = $sekarang->format('H:i:s');
        $namaHari = $this->namaHariIndonesia($sekarang);

        $counts = JadwalKa::query()
            ->where('jam_lewat', '>=', $jamSekarang)
            ->where(function ($query) use ($tanggalHariIni, $namaHari) {
                $query->where(function ($batch) use ($tanggalHariIni) {
                    $batch->whereNotNull('jadwal_ka_batch_id')
                        ->whereHas('batch', function ($version) use ($tanggalHariIni) {
                            $version->whereIn('status', ['scheduled', 'active', 'archived'])
                                ->whereDate('tanggal_mulai_berlaku', '<=', $tanggalHariIni)
                                ->whereHas('jpls', function ($scope) use ($tanggalHariIni) {
                                    $scope->whereColumn('jpl.id', 'jadwal_ka.jpl_id')
                                        ->where(function ($end) use ($tanggalHariIni) {
                                            $end->whereNull('jadwal_ka_batch_jpl.tanggal_akhir_berlaku')
                                                ->orWhereDate(
                                                    'jadwal_ka_batch_jpl.tanggal_akhir_berlaku',
                                                    '>=',
                                                    $tanggalHariIni
                                                );
                                        });
                                });
                        });
                })->orWhere(function ($legacy) use ($tanggalHariIni, $namaHari) {
                    $legacy->whereNull('jadwal_ka_batch_id')
                        ->where('is_active', true)
                        ->where(function ($applicability) use ($tanggalHariIni, $namaHari) {
                            $applicability->whereDate('tanggal_berlaku', $tanggalHariIni)
                                ->orWhere(function ($rutin) use ($namaHari) {
                                    $rutin->whereNull('tanggal_berlaku')
                                        ->whereNotNull('hari_berlaku')
                                        ->whereRaw(
                                            'LOWER(hari_berlaku) LIKE ?',
                                            ['%'.mb_strtolower($namaHari).'%']
                                        );
                                });
                        });
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

        return view('superadmin.jadwal-ka.index', compact('jpls', 'tanggalHariIni'));
    }

    public function show(Request $request, Jpl $jpl): View
    {
        $sekarang = Carbon::now();
        $tanggalHariIni = $sekarang->toDateString();
        $namaHari = $this->namaHariIndonesia($sekarang);

        $jadwal = JadwalKa::query()
            ->with(['keretaApi', 'jpl'])
            ->where('jpl_id', $jpl->id)
            ->when(
                $request->input('status') === 'aktif',
                fn ($query) => $query->where('is_active', true)
            )
            ->when(
                $request->input('status') === 'nonaktif',
                fn ($query) => $query->where('is_active', false)
            )
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
            ->where(function ($query) use ($tanggalHariIni, $namaHari) {
                $query->where(function ($batch) use ($tanggalHariIni) {
                    $batch->whereNotNull('jadwal_ka_batch_id')
                        ->whereHas('batch', function ($version) use ($tanggalHariIni) {
                            $version->whereIn('status', ['scheduled', 'active', 'archived'])
                                ->whereDate('tanggal_mulai_berlaku', '<=', $tanggalHariIni)
                                ->whereHas('jpls', function ($scope) use ($tanggalHariIni) {
                                    $scope->whereColumn('jpl.id', 'jadwal_ka.jpl_id')
                                        ->where(function ($end) use ($tanggalHariIni) {
                                            $end->whereNull('jadwal_ka_batch_jpl.tanggal_akhir_berlaku')
                                                ->orWhereDate(
                                                    'jadwal_ka_batch_jpl.tanggal_akhir_berlaku',
                                                    '>=',
                                                    $tanggalHariIni
                                                );
                                        });
                                });
                        });
                })->orWhere(function ($legacy) use ($tanggalHariIni, $namaHari) {
                    $legacy->whereNull('jadwal_ka_batch_id')
                        ->where(function ($applicability) use ($tanggalHariIni, $namaHari) {
                            $applicability->whereDate('tanggal_berlaku', $tanggalHariIni)
                                ->orWhere(function ($rutin) use ($namaHari) {
                                    $rutin->whereNull('tanggal_berlaku')
                                        ->whereNotNull('hari_berlaku')
                                        ->whereRaw(
                                            'LOWER(hari_berlaku) LIKE ?',
                                            ['%'.mb_strtolower($namaHari).'%']
                                        );
                                });
                        });
                });
            })
            ->orderBy('jam_lewat')
            ->paginate(25)
            ->withQueryString();

        return view('superadmin.jadwal-ka.show', compact('jpl', 'jadwal', 'tanggalHariIni'));
    }

    public function arsip(Request $request): View
    {
        $tanggalHariIni = Carbon::today()->toDateString();
        $tanggalFilter = null;
        $tanggalFilterValid = true;

        if ($request->filled('tanggal')) {
            try {
                $tanggalParsed = Carbon::createFromFormat('!Y-m-d', $request->input('tanggal'));
                $tanggalFilterValid = $tanggalParsed !== false
                    && $tanggalParsed->format('Y-m-d') === $request->input('tanggal');
                $tanggalFilter = $tanggalFilterValid ? $tanggalParsed->toDateString() : null;
            } catch (\Throwable) {
                $tanggalFilterValid = false;
            }
        }

        $legacyArsip = DB::table('jadwal_ka as jadwal')
            ->join('jpl', 'jpl.id', '=', 'jadwal.jpl_id')
            ->selectRaw(
                "'legacy' as source, NULL as batch_id, DATE(jadwal.tanggal_berlaku) as tanggal, NULL as tanggal_akhir,
                 COUNT(*) as total_jadwal, COUNT(DISTINCT jadwal.jpl_id) as total_jpl"
            )
            ->whereNull('jadwal.jadwal_ka_batch_id')
            ->whereNotNull('jadwal.tanggal_berlaku')
            ->whereDate('jadwal.tanggal_berlaku', '<', $tanggalHariIni)
            ->when(! $tanggalFilterValid, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($tanggalFilter !== null, fn ($query) => $query->whereDate('jadwal.tanggal_berlaku', $tanggalFilter))
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = trim($request->input('q'));
                $query->where(function ($search) use ($keyword) {
                    $search->where('jpl.kode_jpl', 'like', "%{$keyword}%")
                        ->orWhere('jpl.nama_jpl', 'like', "%{$keyword}%");
                });
            })
            ->groupByRaw('DATE(jadwal.tanggal_berlaku)');

        $batchArsip = DB::table('jadwal_ka_batch_jpl as cakupan')
            ->join('jadwal_ka_batches as batch', 'batch.id', '=', 'cakupan.jadwal_ka_batch_id')
            ->join('jadwal_ka as jadwal', function ($join) {
                $join->on('jadwal.jadwal_ka_batch_id', '=', 'batch.id')
                    ->on('jadwal.jpl_id', '=', 'cakupan.jpl_id');
            })
            ->join('jpl', 'jpl.id', '=', 'cakupan.jpl_id')
            ->selectRaw(
                "'batch' as source, batch.id as batch_id, batch.tanggal_mulai_berlaku as tanggal,
                 cakupan.tanggal_akhir_berlaku as tanggal_akhir,
                 COUNT(jadwal.id) as total_jadwal, COUNT(DISTINCT cakupan.jpl_id) as total_jpl"
            )
            ->whereIn('batch.status', ['scheduled', 'active', 'archived'])
            ->whereNotNull('cakupan.tanggal_akhir_berlaku')
            ->whereColumn('batch.tanggal_mulai_berlaku', '<=', 'cakupan.tanggal_akhir_berlaku')
            ->whereDate('cakupan.tanggal_akhir_berlaku', '<', $tanggalHariIni)
            ->when(! $tanggalFilterValid, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($tanggalFilter !== null, fn ($query) => $query->whereDate('batch.tanggal_mulai_berlaku', $tanggalFilter))
            ->when($request->filled('q'), function ($query) use ($request) {
                $keyword = trim($request->input('q'));
                $query->where(function ($search) use ($keyword) {
                    $search->where('jpl.kode_jpl', 'like', "%{$keyword}%")
                        ->orWhere('jpl.nama_jpl', 'like', "%{$keyword}%");
                });
            })
            ->groupBy('batch.id', 'batch.tanggal_mulai_berlaku', 'cakupan.tanggal_akhir_berlaku');

        $arsip = $legacyArsip
            ->unionAll($batchArsip)
            ->orderByDesc('tanggal_akhir')
            ->orderByDesc('tanggal')
            ->orderByDesc('batch_id')
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.jadwal-ka.arsip', compact('arsip', 'tanggalHariIni'));
    }

    public function showArsip(Request $request, ?string $tanggal = null): View|RedirectResponse
    {
        $tanggalInput = $tanggal ?? $request->query('tanggal');

        abort_if(! is_string($tanggalInput) || $tanggalInput === '', 404, 'Tanggal arsip tidak ditemukan.');

        try {
            $parsedTanggal = Carbon::createFromFormat('!Y-m-d', $tanggalInput);
        } catch (\Throwable $exception) {
            abort(404, 'Format tanggal arsip tidak valid.');
        }

        abort_if(
            ! $parsedTanggal || $parsedTanggal->format('Y-m-d') !== $tanggalInput,
            404,
            'Tanggal arsip tidak valid.'
        );

        $tanggal = $parsedTanggal->toDateString();
        $tanggalHariIni = Carbon::today()->toDateString();

        if ($tanggal >= $tanggalHariIni) {
            return redirect()->route('superadmin.jadwal-ka.index');
        }

        $batchId = $request->query('batch_id');
        $tanggalAkhir = null;

        if ($batchId !== null) {
            abort_if(
                ! is_scalar($batchId)
                    || filter_var($batchId, FILTER_VALIDATE_INT) === false
                    || (int) $batchId < 1,
                404,
                'Batch arsip tidak valid.'
            );

            $batch = JadwalKaBatch::query()->findOrFail((int) $batchId);
            abort_if(
                $batch->status === 'draft'
                    || $batch->tanggal_mulai_berlaku?->toDateString() !== $tanggal,
                404,
                'Periode batch arsip tidak ditemukan.'
            );

            $tanggalAkhirInput = $request->query('tanggal_akhir');
            try {
                $tanggalAkhirParsed = is_string($tanggalAkhirInput)
                    ? Carbon::createFromFormat('!Y-m-d', $tanggalAkhirInput)
                    : false;
            } catch (\Throwable) {
                $tanggalAkhirParsed = false;
            }

            abort_if(
                ! $tanggalAkhirParsed
                    || $tanggalAkhirParsed->format('Y-m-d') !== $tanggalAkhirInput
                    || $tanggalAkhirParsed->toDateString() >= $tanggalHariIni
                    || $tanggalAkhirParsed->lt($batch->tanggal_mulai_berlaku),
                404,
                'Tanggal akhir periode arsip tidak valid.'
            );

            $tanggalAkhir = $tanggalAkhirParsed->toDateString();
            $jplIds = DB::table('jadwal_ka_batch_jpl')
                ->where('jadwal_ka_batch_id', $batch->id)
                ->whereDate('tanggal_akhir_berlaku', $tanggalAkhir)
                ->pluck('jpl_id');

            abort_if($jplIds->isEmpty(), 404, 'Cakupan JPL pada periode arsip tidak ditemukan.');

            $jadwal = JadwalKa::query()
                ->with(['keretaApi', 'jpl'])
                ->where('jadwal_ka_batch_id', $batch->id)
                ->whereIn('jpl_id', $jplIds)
                ->orderBy('jpl_id')
                ->orderBy('jam_lewat')
                ->paginate(50)
                ->withQueryString();
            $jenisArsip = 'batch';
        } else {
            $jadwal = JadwalKa::query()
                ->with(['keretaApi', 'jpl'])
                ->whereNull('jadwal_ka_batch_id')
                ->whereDate('tanggal_berlaku', $tanggal)
                ->orderBy('jpl_id')
                ->orderBy('jam_lewat')
                ->paginate(50)
                ->withQueryString();
            $jenisArsip = 'legacy';
        }

        return view('superadmin.jadwal-ka.showarsip', compact(
            'jadwal',
            'tanggal',
            'tanggalHariIni',
            'tanggalAkhir',
            'jenisArsip'
        ));
    }

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
