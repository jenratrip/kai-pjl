<?php

namespace App\Http\Controllers\Admin;

use App\Exports\JadwalKaTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\JadwalKaWorkbookImport;
use App\Models\JadwalKa;
use App\Models\KeretaApi;
use App\Models\Jpl;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

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
                $query->whereDate('tanggal_berlaku', $tanggalHariIni)
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
     * Menampilkan form edit jadwal KA.
     */
    public function edit(JadwalKa $jadwal_ka): View
    {
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

        $jadwal_ka->update($validated);

        return redirect()
            ->route('admin.jadwal-ka.show', [
                'jpl' => $jadwal_ka->jpl_id,
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
     * Mengunduh template Excel untuk import jadwal KA.
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new JadwalKaTemplateExport(),
            'template-jadwal-ka.xlsx'
        );
    }

    /**
     * Menerima upload Excel dan menyimpan jadwal KA ke database.
     *
     * Menggunakan alur yang sama dengan JadwalKaImportController
     * namun melalui endpoint resource controller (POST /admin/jadwal-ka/import).
     */
    public function importExcel(Request $request)
    {
        $validated = $request->validate([
            'tanggal_berlaku' => [
                'required',
                'date_format:Y-m-d',
            ],
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:20480',
            ],
        ], [
            'tanggal_berlaku.required' => 'Tanggal jadwal KA wajib dipilih.',
            'tanggal_berlaku.date_format' => 'Format tanggal jadwal tidak valid.',
            'file.required' => 'File Excel wajib dipilih.',
            'file.mimes' => 'File harus berformat XLS atau XLSX.',
            'file.max' => 'Ukuran file maksimal 20 MB.',
        ]);

        $tanggalBerlaku = $validated['tanggal_berlaku'];

        $path = $request->file('file')->store('imports/jadwal-ka', 'local');

        try {
            $reader = IOFactory::createReaderForFile(
                Storage::disk('local')->path($path)
            );
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load(Storage::disk('local')->path($path));

            $parser = new JadwalKaWorkbookImport();
            $batch = (string) Str::uuid();
            $inserted = 0;
            $skipped = 0;

            DB::transaction(function () use (
                $spreadsheet,
                $parser,
                $tanggalBerlaku,
                $batch,
                &$inserted,
                &$skipped
            ) {
                $keretaCache = [];
                $seenKeys    = [];

                foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                    $result = $parser->parseSheet($worksheet);

                    if (! $result['header_found']) {
                        continue;
                    }

                    if (! empty($result['errors'])) {
                        throw new \RuntimeException(
                            'Terdapat baris tidak valid pada sheet ' . $result['sheet'] . '.'
                        );
                    }

                    $jpl = Jpl::query()
                        ->where('kode_jpl', trim($result['sheet']))
                        ->where('is_active', true)
                        ->first();

                    if (! $jpl) {
                        throw new \RuntimeException(
                            'JPL untuk sheet ' . $result['sheet'] . ' tidak ditemukan atau tidak aktif.'
                        );
                    }

                    foreach ($result['rows'] as $row) {
                        if (! ($row['valid'] ?? false)) {
                            throw new \RuntimeException(
                                'Baris tidak valid pada sheet ' . $result['sheet'] . '.'
                            );
                        }

                        $nomorKa = trim((string) $row['nomor_ka']);
                        $jam     = $row['jam_lewat'];
                        $key     = mb_strtolower($nomorKa) . '|' . $jpl->id . '|' . $jam . '|' . $tanggalBerlaku;

                        if (isset($seenKeys[$key])) {
                            $skipped++;
                            continue;
                        }

                        $seenKeys[$key] = true;

                        if (! array_key_exists($nomorKa, $keretaCache)) {
                            $kereta = KeretaApi::firstOrCreate(
                                ['nomor_ka' => $nomorKa],
                                ['nama_ka' => $row['nama_ka'] ?? null]
                            );
                            $keretaCache[$nomorKa] = $kereta;
                        }

                        $kereta  = $keretaCache[$nomorKa];
                        $exists = JadwalKa::query()
                            ->where('kereta_api_id', $kereta->id)
                            ->where('jpl_id', $jpl->id)
                            ->where('jam_lewat', $jam)
                            ->whereDate('tanggal_berlaku', $tanggalBerlaku)
                            ->exists();

                        if ($exists) {
                            $skipped++;
                            continue;
                        }

                        JadwalKa::create([
                            'kereta_api_id'  => $kereta->id,
                            'jpl_id'         => $jpl->id,
                            'jam_lewat'      => $jam,
                            'hari_berlaku'   => null,
                            'tanggal_berlaku'=> $tanggalBerlaku,
                            'dari'           => isset($row['dari']) && trim((string) $row['dari']) !== '' ? trim((string) $row['dari']) : null,
                            'ke'             => isset($row['ke'])   && trim((string) $row['ke'])   !== '' ? trim((string) $row['ke'])   : null,
                            'keterangan'     => $row['keterangan'] ?? null,
                            'is_active'      => true,
                            'batch_import'   => $batch,
                        ]);

                        $inserted++;
                    }
                }

                $spreadsheet->disconnectWorksheets();
            });

            Storage::disk('local')->delete($path);

            return redirect()
                ->route('admin.jadwal-ka.index')
                ->with('success', "Import selesai: {$inserted} jadwal ditambahkan, {$skipped} dilewati. Tanggal: " . Carbon::parse($tanggalBerlaku)->format('d-m-Y'));
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);
            report($e);

            $message = 'Import gagal. Periksa format file dan coba lagi.';
            if (config('app.debug')) {
                $message .= ' Detail: ' . class_basename($e) . ' — ' . $e->getMessage();
            }

            return redirect()
                ->route('admin.jadwal-ka.import.form')
                ->withErrors(['file' => $message])
                ->withInput();
        }
    }

    /**
     * Mengaktifkan atau menonaktifkan jadwal KA.
     *
     * Jadwal tidak dapat dinonaktifkan jika ada dinas yang sedang
     * berlangsung dan jadwal tersebut sudah masuk dalam realisasi dinas itu.
     */
    public function toggleStatus(JadwalKa $jadwal_ka)
    {
        // Cegah nonaktifkan jadwal yang sedang dipakai dinas berlangsung.
        if ($jadwal_ka->is_active) {
            $adaDinasBerlangsung = \App\Models\RealisasiKa::query()
                ->where('jadwal_ka_id', $jadwal_ka->id)
                ->whereHas('dinas', fn ($q) => $q->where('status', 'berlangsung'))
                ->exists();

            if ($adaDinasBerlangsung) {
                return redirect()
                    ->back()
                    ->with('error', "Jadwal KA #{$jadwal_ka->id} tidak dapat dinonaktifkan karena sedang digunakan dalam dinas yang berlangsung.");
            }
        }

        $jadwal_ka->update([
            'is_active' => ! $jadwal_ka->is_active,
        ]);

        $status = $jadwal_ka->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()
            ->back()
            ->with('success', "Jadwal KA #{$jadwal_ka->id} berhasil {$status}.");
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
