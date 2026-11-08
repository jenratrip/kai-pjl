<?php

namespace App\Http\Controllers\Admin;

use App\Exports\JadwalKaTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\JadwalKa;
use App\Models\JadwalKaBatch;
use App\Models\Jpl;
use App\Models\KeretaApi;
use App\Models\RealisasiKa;
use App\Services\JadwalKaVersionService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use LogicException;
use Maatwebsite\Excel\Facades\Excel;

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
            ->where('jam_lewat', '>=', $jamSekarang)
            ->where(function ($query) use (
                $tanggalHariIni,
                $namaHari
            ) {
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

        $batches = JadwalKaBatch::query()
            ->with('jpls:id,kode_jpl,nama_jpl')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'batch_page')
            ->withQueryString();

        return view('admin.jadwal-ka.index', compact(
            'jpls',
            'tanggalHariIni',
            'batches'
        ));
    }

    /**
     * Detail jadwal KA berdasarkan JPL.
     *
     * Admin dapat melihat jadwal aktif maupun nonaktif
     * yang berlaku hari ini, termasuk jadwal yang jamnya sudah lewat.
     */
    public function show(Request $request, Jpl $jpl): View
    {
        $sekarang = Carbon::now();
        $tanggalHariIni = $sekarang->toDateString();
        $namaHari = $this->namaHariIndonesia($sekarang);

        $jadwal = JadwalKa::query()
            ->with(['keretaApi', 'jpl'])
            ->where('jpl_id', $jpl->id)

            // Filter status jadwal.
            ->when(
                $request->input('status') === 'aktif',
                fn ($query) => $query->where('is_active', true)
            )
            ->when(
                $request->input('status') === 'nonaktif',
                fn ($query) => $query->where('is_active', false)
            )

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

        return view('admin.jadwal-ka.show', compact(
            'jpl',
            'jadwal',
            'tanggalHariIni'
        ));
    }

    /**
     * Menampilkan form edit jadwal KA.
     */
    public function edit(JadwalKa $jadwal_ka): View|RedirectResponse
    {
        $statusBatch = $jadwal_ka->batch?->status;

        if (in_array($statusBatch, ['active', 'scheduled', 'archived'], true)) {
            $pesan = match ($statusBatch) {
                'active' => 'Jadwal dalam batch aktif tidak dapat diedit langsung. Koreksi harus dilakukan melalui impor batch baru.',
                'scheduled' => 'Jadwal sudah masuk versi terjadwal dan tidak dapat diedit.',
                'archived' => 'Jadwal dari batch yang sudah diarsipkan hanya dapat dibaca dan tidak dapat diubah.',
            };

            return redirect()
                ->route('admin.jadwal-ka.index')
                ->with('error', $pesan);
        }

        $jadwalKa = $jadwal_ka;

        $keretaApi = KeretaApi::orderBy('nomor_ka')->get();
        $jpls = Jpl::orderBy('kode_jpl')->get();

        return view('admin.jadwal-ka.edit', compact(
            'jadwalKa',
            'keretaApi',
            'jpls'
        ));
    }

    /**
     * Memperbarui jadwal KA.
     */
    public function update(Request $request, JadwalKa $jadwal_ka)
    {
        $validated = $request->validate([
            'kereta_api_id' => [
                'required',
                'integer',
                'exists:kereta_api,id',
            ],
            'jpl_id' => [
                'required',
                'integer',
                'exists:jpl,id',
            ],
            'jam_lewat' => [
                'required',
                'date_format:H:i',
            ],
            'hari_berlaku' => [
                'nullable',
                'array',
            ],
            'hari_berlaku.*' => [
                'required',
                'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            ],
            'tanggal_berlaku' => [
                'nullable',
                'date_format:Y-m-d',
            ],
            'keterangan' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ]);

        $validated['hari_berlaku'] =
            $validated['hari_berlaku'] ?? [];

        $validated['tanggal_berlaku'] =
            $validated['tanggal_berlaku'] ?? null;

        $jadwalId = (int) $jadwal_ka->getKey();
        $initialJadwal = JadwalKa::query()
            ->with('batch:id,status')
            ->select(['id', 'jpl_id', 'jadwal_ka_batch_id'])
            ->findOrFail($jadwalId);
        $initialBatchStatus = $initialJadwal->batch?->status;

        $hasil = DB::transaction(function () use ($initialJadwal, $initialBatchStatus, $jadwalId, $validated) {
            Jpl::query()
                ->whereKey($initialJadwal->jpl_id)
                ->lockForUpdate()
                ->firstOrFail();

            $batch = $initialJadwal->jadwal_ka_batch_id === null
                ? null
                : JadwalKaBatch::query()
                    ->whereKey($initialJadwal->jadwal_ka_batch_id)
                    ->lockForUpdate()
                    ->first();

            $jadwalTerkunci = JadwalKa::query()
                ->whereKey($jadwalId)
                ->lockForUpdate()
                ->firstOrFail();

            $batchIdTerkunci = $jadwalTerkunci->jadwal_ka_batch_id === null
                ? null
                : (int) $jadwalTerkunci->jadwal_ka_batch_id;
            $batchIdAwal = $initialJadwal->jadwal_ka_batch_id === null
                ? null
                : (int) $initialJadwal->jadwal_ka_batch_id;

            if (
                (int) $jadwalTerkunci->jpl_id !== (int) $initialJadwal->jpl_id
                || $batchIdTerkunci !== $batchIdAwal
            ) {
                return redirect()
                    ->back()
                    ->with('error', 'Konteks jadwal berubah selama proses. Muat ulang halaman sebelum mencoba kembali.');
            }

            $statusBatch = $batch?->status;
            $statusBatchTerlarang = in_array($statusBatch, ['active', 'scheduled', 'archived'], true)
                ? $statusBatch
                : $initialBatchStatus;

            if (
                in_array($initialBatchStatus, ['active', 'scheduled', 'archived'], true)
                || in_array($statusBatch, ['active', 'scheduled', 'archived'], true)
            ) {
                $pesan = match ($statusBatchTerlarang) {
                    'active' => 'Jadwal dalam batch aktif tidak dapat diedit langsung. Koreksi harus dilakukan melalui impor batch baru.',
                    'scheduled' => 'Jadwal sudah masuk versi terjadwal dan tidak dapat diedit.',
                    'archived' => 'Jadwal dari batch yang sudah diarsipkan hanya dapat dibaca dan tidak dapat diubah.',
                    default => 'Jadwal batch tidak dapat diedit.',
                };

                return redirect()
                    ->back()
                    ->with('error', $pesan);
            }

            if (
                $batch !== null
                && (int) $validated['jpl_id'] !== (int) $jadwalTerkunci->jpl_id
            ) {
                return redirect()
                    ->back()
                    ->withErrors([
                        'jpl_id' => 'JPL jadwal dalam batch draft tidak dapat dipindahkan karena cakupan JPL batch sudah ditetapkan saat impor.',
                    ])
                    ->withInput();
            }

            if (
                in_array($statusBatch, ['draft', 'scheduled'], true)
                && $validated['is_active']
            ) {
                return redirect()
                    ->back()
                    ->withErrors([
                        'is_active' => 'Jadwal dalam batch draft atau terjadwal tidak dapat diaktifkan sebelum tanggal efektif batch.',
                    ])
                    ->withInput();
            }

            $jadwalTerkunci->update($validated);

            return $jadwalTerkunci;
        }, 3);

        if ($hasil instanceof RedirectResponse) {
            return $hasil;
        }

        return redirect()
            ->route('admin.jadwal-ka.show', [
                'jpl' => $hasil->jpl_id,
            ])
            ->with('success', 'Jadwal KA berhasil diperbarui.');
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
            ! is_string($tanggalInput) || $tanggalInput === '',
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
            ! $parsedTanggal ||
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

        return view('admin.jadwal-ka.showarsip', compact(
            'jadwal',
            'tanggal',
            'tanggalHariIni',
            'tanggalAkhir',
            'jenisArsip'
        ));
    }

    /**
     * Mengunduh template Excel untuk import jadwal KA.
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new JadwalKaTemplateExport,
            'template-jadwal-ka.xlsx'
        );
    }

    /**
     * Menolak endpoint import lama agar semua impor melewati preview-konfirmasi.
     */
    public function importExcel(): RedirectResponse
    {
        return redirect()
            ->route('admin.jadwal-ka.import')
            ->withErrors([
                'file' => 'Endpoint impor langsung sudah dinonaktifkan. Gunakan alur preview dan konfirmasi agar jadwal tersimpan sebagai draft.',
            ]);
    }

    /**
     * Mengaktifkan atau menonaktifkan jadwal KA.
     *
     * Jadwal tidak dapat dinonaktifkan jika ada dinas yang sedang
     * berlangsung dan jadwal tersebut sudah masuk dalam realisasi dinas itu.
     */
    public function toggleStatus(JadwalKa $jadwal_ka)
    {
        $jadwalId = (int) $jadwal_ka->getKey();
        $initialJadwal = JadwalKa::query()
            ->with('batch:id,status')
            ->select(['id', 'jpl_id', 'jadwal_ka_batch_id'])
            ->findOrFail($jadwalId);
        $initialBatchStatus = $initialJadwal->batch?->status;

        return DB::transaction(function () use ($initialJadwal, $initialBatchStatus, $jadwalId) {
            Jpl::query()
                ->whereKey($initialJadwal->jpl_id)
                ->lockForUpdate()
                ->firstOrFail();

            $batch = $initialJadwal->jadwal_ka_batch_id === null
                ? null
                : JadwalKaBatch::query()
                    ->whereKey($initialJadwal->jadwal_ka_batch_id)
                    ->lockForUpdate()
                    ->first();

            $jadwalTerkunci = JadwalKa::query()
                ->whereKey($jadwalId)
                ->lockForUpdate()
                ->firstOrFail();

            $batchIdTerkunci = $jadwalTerkunci->jadwal_ka_batch_id === null
                ? null
                : (int) $jadwalTerkunci->jadwal_ka_batch_id;
            $batchIdAwal = $initialJadwal->jadwal_ka_batch_id === null
                ? null
                : (int) $initialJadwal->jadwal_ka_batch_id;

            if (
                (int) $jadwalTerkunci->jpl_id !== (int) $initialJadwal->jpl_id
                || $batchIdTerkunci !== $batchIdAwal
            ) {
                return redirect()
                    ->back()
                    ->with('error', 'Konteks jadwal berubah selama proses. Muat ulang halaman sebelum mencoba kembali.');
            }

            if (
                in_array($initialBatchStatus, ['active', 'scheduled', 'archived'], true)
                || in_array($batch?->status, ['active', 'scheduled', 'archived'], true)
            ) {
                $statusBatchTerlarang = in_array($batch?->status, ['active', 'scheduled', 'archived'], true)
                    ? $batch->status
                    : $initialBatchStatus;
                $pesan = match ($statusBatchTerlarang) {
                    'archived' => 'Jadwal dari batch yang sudah diarsipkan hanya dapat dibaca dan tidak dapat diubah.',
                    default => 'Jadwal dalam batch aktif atau terjadwal tidak dapat diubah langsung. Koreksi harus dilakukan melalui impor batch baru.',
                };

                return redirect()
                    ->back()
                    ->with('error', $pesan);
            }

            if (! $jadwalTerkunci->is_active && $batch?->status === 'draft') {
                return redirect()
                    ->back()
                    ->with('error', 'Jadwal dalam batch draft tidak dapat diaktifkan sebelum tanggal efektif batch.');
            }

            // Cegah nonaktifkan jadwal yang sedang dipakai dinas berlangsung.
            if ($jadwalTerkunci->is_active) {
                $adaDinasBerlangsung = RealisasiKa::query()
                    ->where('jadwal_ka_id', $jadwalTerkunci->id)
                    ->whereHas('dinas', fn ($q) => $q->where('status', 'berlangsung'))
                    ->exists();

                if ($adaDinasBerlangsung) {
                    return redirect()
                        ->back()
                        ->with('error', "Jadwal KA #{$jadwalTerkunci->id} tidak dapat dinonaktifkan karena sedang digunakan dalam dinas yang berlangsung.");
                }
            }

            $jadwalTerkunci->update([
                'is_active' => ! $jadwalTerkunci->is_active,
            ]);

            $status = $jadwalTerkunci->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return redirect()
                ->back()
                ->with('success', "Jadwal KA #{$jadwalTerkunci->id} berhasil {$status}.");
        }, 3);
    }

    /**
     * Schedule a draft batch or activate it when its effective date is today.
     */
    public function scheduleBatch(
        Request $request,
        JadwalKaBatch $batch,
        JadwalKaVersionService $versionService
    ): RedirectResponse {
        $validated = $request->validate([
            'tanggal_mulai_berlaku' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
        ]);

        try {
            $scheduledBatch = $versionService->scheduleDraftBatch(
                (int) $batch->getKey(),
                CarbonImmutable::createFromFormat('!Y-m-d', $validated['tanggal_mulai_berlaku'])
            );
        } catch (LogicException $exception) {
            return redirect()
                ->back()
                ->withErrors(['tanggal_mulai_berlaku' => $exception->getMessage()])
                ->withInput();
        }

        $message = $scheduledBatch->status === 'active'
            ? 'Batch jadwal KA berhasil diaktifkan.'
            : 'Batch jadwal KA berhasil dijadwalkan.';

        return redirect()
            ->route('admin.jadwal-ka.index')
            ->with('success', $message);
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
