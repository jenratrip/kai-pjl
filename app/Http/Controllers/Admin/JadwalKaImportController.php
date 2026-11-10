<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\JadwalKaWorkbookImport;
use App\Models\JadwalKa;
use App\Models\JadwalKaBatch;
use App\Models\Jpl;
use App\Models\KeretaApi;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Throwable;

class JadwalKaImportController extends Controller
{
    /**
     * Menampilkan halaman import jadwal KA.
     */
    public function create()
    {
        return view('admin.jadwal-ka.import');
    }

    /**
     * Membaca workbook dan menampilkan preview sebelum import.
     */
    public function preview(Request $request)
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

        $path = $request->file('file')->store(
            'imports/jadwal-ka',
            'local'
        );

        try {
            $spreadsheet = $this->loadSpreadsheet(
                Storage::disk('local')->path($path)
            );

            $parser = new JadwalKaWorkbookImport;

            $sheets = [];
            $importErrors = [];
            $warnings = [];
            $previewRows = [];
            $headerSheetCount = 0;

            // Mendeteksi duplikat dalam workbook yang sama.
            $seenKeys = [];

            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $result = $parser->parseSheet($worksheet);
                $title = $result['sheet'];

                if (! $result['header_found']) {
                    if ($this->findJpl($title)) {
                        $importErrors[] = sprintf(
                            'Sheet JPL "%s" tidak memiliki header jadwal yang valid.',
                            $title
                        );
                    } else {
                        $warnings[] = sprintf(
                            'Sheet "%s" dilewati karena header jadwal tidak ditemukan.',
                            $title
                        );
                    }

                    continue;
                }

                $headerSheetCount++;

                // Mencari JPL berdasarkan nama sheet.
                $jpl = $this->findJpl($title);

                if (! $jpl) {
                    $importErrors[] = sprintf(
                        'Sheet "%s": kode JPL tidak ditemukan di database.',
                        $title
                    );
                }

                foreach ($result['errors'] as $error) {
                    $importErrors[] = $error;
                }

                if (empty($result['rows'])) {
                    $importErrors[] = sprintf(
                        'Sheet JPL "%s" tidak memiliki baris jadwal yang dapat diimpor.',
                        $title
                    );
                }

                foreach ($result['rows'] as $row) {
                    $row['jpl_id'] = $jpl?->id;
                    $row['jpl_name'] = $jpl?->nama_jpl;
                    $row['duplicate'] = false;

                    if (! $jpl) {
                        $row['valid'] = false;
                        $row['error'] = 'Kode JPL pada sheet tidak ditemukan.';
                    }

                    if (($row['valid'] ?? false) && $jpl) {
                        $nomorKa = trim((string) $row['nomor_ka']);
                        $jam = $row['jam_lewat'];

                        /*
                         * Identitas jadwal:
                         * nomor KA + JPL + jam lewat + tanggal berlaku.
                         */
                        $key = implode('|', [
                            mb_strtolower($nomorKa),
                            (string) $jpl->id,
                            (string) $jam,
                            $tanggalBerlaku,
                        ]);

                        $duplicateInWorkbook = isset($seenKeys[$key]);

                        $seenKeys[$key] = true;

                        $row['duplicate'] = $duplicateInWorkbook;

                        if ($row['duplicate']) {
                            $row['error'] = 'Duplikat pada workbook yang sama.';
                        }
                    }

                    $previewRows[] = $row;
                }

                $sheets[] = [
                    'name' => $title,
                    'jpl_found' => $jpl !== null,
                    'jpl_name' => $jpl?->nama_jpl,
                    'row_count' => count($result['rows']),
                    'error_count' => count($result['errors']),
                ];
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            // Tidak ada jadwal yang bisa ditampilkan.
            if ($headerSheetCount === 0 || empty($previewRows)) {
                Storage::disk('local')->delete($path);

                return redirect()
                    ->route('admin.jadwal-ka.import')
                    ->withErrors([
                        'file' => 'Tidak ditemukan baris jadwal KA yang dapat dibaca.',
                    ])
                    ->withInput([
                        'tanggal_berlaku' => $tanggalBerlaku,
                    ]);
            }

            $hasInvalidRows = collect($previewRows)->contains(
                fn ($row) => ! ($row['valid'] ?? false)
            );

            $validRowCount = collect($previewRows)
                ->filter(
                    fn ($row) => ($row['valid'] ?? false)
                        && ! ($row['duplicate'] ?? false)
                )
                ->count();

            if ($validRowCount === 0) {
                $importErrors[] = 'Tidak ada baris jadwal baru yang dapat dimasukkan. Periksa duplikat dan validitas data.';
            }

            // Konfirmasi hanya diperbolehkan jika tidak ada error,
            // baris tidak valid, atau sheet yang tidak cocok dengan JPL.
            $canConfirm = empty($importErrors)
                && ! $hasInvalidRows
                && collect($sheets)->every(
                    fn ($sheet) => $sheet['jpl_found']
                );

            $token = (string) Str::uuid();

            // Simpan lokasi file dan tanggal berlaku untuk konfirmasi.
            session()->put("jadwal_ka_import.{$token}", [
                'path' => $path,
                'tanggal_berlaku' => $tanggalBerlaku,
                'created_at' => now()->timestamp,
            ]);

            return view('admin.jadwal-ka.preview', [
                'token' => $token,
                'tanggalBerlaku' => $tanggalBerlaku,
                'sheets' => $sheets,
                'rows' => $previewRows,
                'importErrors' => $importErrors,
                'warnings' => $warnings,

                'validCount' => collect($previewRows)
                    ->filter(
                        fn ($row) => ($row['valid'] ?? false)
                            && ! ($row['duplicate'] ?? false)
                    )
                    ->count(),

                'invalidCount' => collect($previewRows)
                    ->filter(
                        fn ($row) => ! ($row['valid'] ?? false)
                    )
                    ->count(),

                'duplicateCount' => collect($previewRows)
                    ->filter(
                        fn ($row) => ($row['valid'] ?? false)
                            && ($row['duplicate'] ?? false)
                    )
                    ->count(),

                'canConfirm' => $canConfirm,
            ]);
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);

            report($e);

            $message = 'File Excel gagal dibaca. Pastikan format workbook sesuai template jadwal KA.';

            if (config('app.debug')) {
                $message .= ' Detail: '
                    .class_basename($e)
                    .' - '
                    .$e->getMessage();
            }

            return redirect()
                ->route('admin.jadwal-ka.import')
                ->withErrors(['file' => $message])
                ->withInput([
                    'tanggal_berlaku' => $tanggalBerlaku,
                ]);
        }
    }

    /**
     * Menyimpan jadwal setelah pengguna mengonfirmasi preview.
     */
    public function confirm(Request $request, ActivityLogService $activityLogService)
    {
        $request->validate([
            'token' => ['required', 'uuid'],
        ]);

        $key = "jadwal_ka_import.{$request->token}";
        $import = session()->get($key);

        // Sesi preview tidak ditemukan.
        if (! $import || empty($import['path'])) {
            return redirect()
                ->route('admin.jadwal-ka.import')
                ->withErrors([
                    'file' => 'Sesi preview tidak ditemukan. Silakan upload ulang.',
                ]);
        }

        // Preview berlaku selama dua jam.
        if (
            now()->timestamp - ($import['created_at'] ?? 0) > 7200
        ) {
            Storage::disk('local')->delete($import['path']);
            session()->forget($key);

            return redirect()
                ->route('admin.jadwal-ka.import')
                ->withErrors([
                    'file' => 'Preview kedaluwarsa. Silakan upload ulang.',
                ]);
        }

        $tanggalBerlaku = $import['tanggal_berlaku'] ?? null;

        /*
         * Validasi ulang tanggal dari session.
         * Tanggal tidak diambil dari input konfirmasi pengguna.
         */
        if (! is_string($tanggalBerlaku)) {
            return redirect()
                ->route('admin.jadwal-ka.import')
                ->withErrors([
                    'file' => 'Tanggal berlaku tidak ditemukan pada sesi preview. Silakan upload ulang.',
                ]);
        }

        $dateParts = explode('-', $tanggalBerlaku);

        if (
            count($dateParts) !== 3
            || ! checkdate(
                (int) ($dateParts[1] ?? 0),
                (int) ($dateParts[2] ?? 0),
                (int) ($dateParts[0] ?? 0)
            )
            || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalBerlaku)
        ) {
            return redirect()
                ->route('admin.jadwal-ka.import')
                ->withErrors([
                    'file' => 'Tanggal berlaku pada sesi preview tidak valid. Silakan upload ulang.',
                ]);
        }

        $absolutePath = Storage::disk('local')->path($import['path']);

        // File sementara sudah tidak tersedia.
        if (! is_file($absolutePath)) {
            session()->forget($key);

            return redirect()
                ->route('admin.jadwal-ka.import')
                ->withErrors([
                    'file' => 'File sementara tidak ditemukan. Silakan upload ulang.',
                ]);
        }

        $batch = (string) Str::uuid();

        $inserted = 0;
        $skipped = 0;
        $duplicates = 0;

        try {
            DB::transaction(function () use (
                $absolutePath,
                $batch,
                $tanggalBerlaku,
                &$inserted,
                &$skipped,
                &$duplicates,
                $activityLogService
            ) {
                $batchRecord = JadwalKaBatch::create([
                    'uuid' => $batch,
                    'status' => 'draft',
                    'tanggal_mulai_berlaku' => $tanggalBerlaku,
                ]);

                $spreadsheet = $this->loadSpreadsheet($absolutePath);
                $parser = new JadwalKaWorkbookImport;

                $keretaCache = [];
                $seenKeys = [];
                $jplIds = [];
                $headerSheetCount = 0;

                foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                    $result = $parser->parseSheet($worksheet);

                    // Abaikan sheet yang bukan sheet jadwal.
                    if (! $result['header_found']) {
                        if ($this->findJpl($result['sheet'])) {
                            throw new \RuntimeException(
                                'Sheet JPL '
                                .$result['sheet']
                                .' tidak memiliki header jadwal yang valid. Import dibatalkan.'
                            );
                        }

                        continue;
                    }

                    $headerSheetCount++;

                    // Jika parser menemukan error, batalkan transaksi.
                    if (! empty($result['errors'])) {
                        throw new \RuntimeException(
                            'Terdapat baris tidak valid pada sheet '
                            .$result['sheet']
                            .'. Periksa preview dan upload ulang setelah diperbaiki.'
                        );
                    }

                    $jpl = $this->findJpl($result['sheet']);

                    if (! $jpl) {
                        throw new \RuntimeException(
                            'JPL untuk sheet '
                            .$result['sheet']
                            .' tidak ditemukan. Import dibatalkan.'
                        );
                    }

                    if (empty($result['rows'])) {
                        throw new \RuntimeException(
                            'Sheet JPL '
                            .$result['sheet']
                            .' tidak memiliki baris jadwal. Import dibatalkan.'
                        );
                    }

                    $jplIds[$jpl->id] = $jpl->id;

                    foreach ($result['rows'] as $row) {
                        if (! ($row['valid'] ?? false)) {
                            throw new \RuntimeException(
                                'Terdapat baris tidak valid pada sheet '
                                .$result['sheet']
                                .'. Import dibatalkan.'
                            );
                        }

                        $nomorKa = trim((string) $row['nomor_ka']);
                        $jam = $row['jam_lewat'];

                        $key = implode('|', [
                            mb_strtolower($nomorKa),
                            (string) $jpl->id,
                            (string) $jam,
                            $tanggalBerlaku,
                        ]);

                        // Lewati duplikat dalam workbook.
                        if (isset($seenKeys[$key])) {
                            $duplicates++;
                            $skipped++;

                            continue;
                        }

                        $seenKeys[$key] = true;

                        // Cari kereta atau buat jika belum tersedia.
                        if (! array_key_exists($nomorKa, $keretaCache)) {
                            $kereta = KeretaApi::query()
                                ->where('nomor_ka', $nomorKa)
                                ->first();

                            if (! $kereta) {
                                $kereta = KeretaApi::create([
                                    'nomor_ka' => $nomorKa,
                                    'nama_ka' => $row['nama_ka'],
                                    'jenis' => null,
                                ]);
                            }

                            $keretaCache[$nomorKa] = $kereta;
                        }

                        $kereta = $keretaCache[$nomorKa];

                        // Simpan jadwal termasuk stasiun asal dan tujuan.
                        JadwalKa::create([
                            'kereta_api_id' => $kereta->id,
                            'dari' => isset($row['dari'])
                                && trim((string) $row['dari']) !== ''
                                    ? trim((string) $row['dari'])
                                    : null,
                            'ke' => isset($row['ke'])
                                && trim((string) $row['ke']) !== ''
                                    ? trim((string) $row['ke'])
                                    : null,
                            'jpl_id' => $jpl->id,
                            'jam_lewat' => $jam,
                            'hari_berlaku' => null,
                            'tanggal_berlaku' => $tanggalBerlaku,
                            'keterangan' => $row['keterangan'] ?? null,
                            'is_active' => false,
                            'batch_import' => $batch,
                            'jadwal_ka_batch_id' => $batchRecord->id,
                        ]);

                        $inserted++;
                    }
                }

                if ($headerSheetCount === 0) {
                    throw new \RuntimeException(
                        'Tidak ditemukan sheet jadwal JPL yang valid. Import dibatalkan.'
                    );
                }

                if ($inserted === 0) {
                    throw new \RuntimeException(
                        'Tidak ada jadwal baru untuk dibuat sebagai draft. Semua baris merupakan duplikat.'
                    );
                }

                $batchRecord->jpls()->sync(array_values($jplIds));

                $activityLogService->log(
                    'import',
                    'jadwal_ka',
                    'Import batch jadwal KA berhasil dikonfirmasi.',
                    $batchRecord,
                    null,
                    [
                        'status' => 'draft',
                        'tanggal_mulai_berlaku' => $tanggalBerlaku,
                        'imported_count' => $inserted,
                        'skipped_count' => $skipped,
                        'covered_jpl_count' => count($jplIds),
                    ]
                );

                $spreadsheet->disconnectWorksheets();
            });

            // Transaksi berhasil: bersihkan file dan sesi sementara.
            Storage::disk('local')->delete($import['path']);
            session()->forget($key);

            $message = 'Import selesai untuk tanggal '
                .Carbon::parse($tanggalBerlaku)->format('d-m-Y')
                .". {$inserted} jadwal ditambahkan; "
                ."{$skipped} baris dilewati";

            if ($duplicates > 0) {
                $message .= " ({$duplicates} duplikat)";
            }

            $message .= ". Batch: {$batch}";

            return redirect()
                ->route('admin.jadwal-ka.index')
                ->with('success', $message);
        } catch (Throwable $e) {
            report($e);

            $message = 'Import gagal dan transaksi database dibatalkan. Periksa kembali file dan log Laravel.';

            if (config('app.debug')) {
                $message .= ' Detail: '
                    .class_basename($e)
                    .' - '
                    .$e->getMessage();
            }

            return redirect()
                ->route('admin.jadwal-ka.import')
                ->withErrors(['file' => $message]);
        }
    }

    /**
     * Membaca file Excel.
     */
    private function loadSpreadsheet(string $absolutePath): Spreadsheet
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadDataOnly(true);

        return $reader->load($absolutePath);
    }

    /**
     * Mencari JPL berdasarkan judul sheet Excel.
     */
    private function findJpl(string $title): ?Jpl
    {
        $title = trim($title);

        $jpl = Jpl::query()
            ->where('kode_jpl', $title)
            ->orWhere('kode_jpl', 'JPL '.$title)
            ->orWhere('kode_jpl', 'JPL'.$title)
            ->first();

        if ($jpl) {
            return $jpl;
        }

        if (preg_match(
            '/\bJPL\s*[-:]?\s*(\d+)\b/i',
            $title,
            $matches
        )) {
            return Jpl::query()
                ->where('kode_jpl', $matches[1])
                ->first();
        }

        return null;
    }
}
