<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\JadwalKaWorkbookImport;
use App\Models\JadwalKa;
use App\Models\Jpl;
use App\Models\KeretaApi;
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

            $parser = new JadwalKaWorkbookImport();

            $sheets = [];
            $importErrors = [];
            $warnings = [];
            $previewRows = [];
            $headerSheetCount = 0;

            // Cache untuk menghindari query kereta berulang.
            $keretaCache = [];

            // Mendeteksi duplikat dalam workbook yang sama.
            $seenKeys = [];

            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $result = $parser->parseSheet($worksheet);
                $title = $result['sheet'];

                if (! $result['header_found']) {
                    $warnings[] = sprintf(
                        'Sheet "%s" dilewati karena header jadwal tidak ditemukan.',
                        $title
                    );

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

                        if (! array_key_exists($nomorKa, $keretaCache)) {
                            $keretaCache[$nomorKa] = KeretaApi::query()
                                ->where('nomor_ka', $nomorKa)
                                ->first();
                        }

                        $kereta = $keretaCache[$nomorKa];

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

                        $duplicateInDatabase = false;

                        if ($kereta) {
                            $duplicateInDatabase = JadwalKa::query()
                                ->where('kereta_api_id', $kereta->id)
                                ->where('jpl_id', $jpl->id)
                                ->where('jam_lewat', $jam)
                                ->whereDate(
                                    'tanggal_berlaku',
                                    $tanggalBerlaku
                                )
                                ->exists();
                        }

                        $row['duplicate'] = $duplicateInDatabase
                            || $duplicateInWorkbook;

                        if ($row['duplicate']) {
                            $row['error'] = $duplicateInDatabase
                                ? 'Jadwal untuk tanggal ini sudah ada di database.'
                                : 'Duplikat pada workbook yang sama.';
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
                    . class_basename($e)
                    . ' - '
                    . $e->getMessage();
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
    public function confirm(Request $request)
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
                &$duplicates
            ) {
                $spreadsheet = $this->loadSpreadsheet($absolutePath);
                $parser = new JadwalKaWorkbookImport();

                $keretaCache = [];
                $seenKeys = [];

                foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                    $result = $parser->parseSheet($worksheet);

                    // Abaikan sheet yang bukan sheet jadwal.
                    if (! $result['header_found']) {
                        continue;
                    }

                    // Jika parser menemukan error, batalkan transaksi.
                    if (! empty($result['errors'])) {
                        throw new \RuntimeException(
                            'Terdapat baris tidak valid pada sheet '
                            . $result['sheet']
                            . '. Periksa preview dan upload ulang setelah diperbaiki.'
                        );
                    }

                    $jpl = $this->findJpl($result['sheet']);

                    if (! $jpl) {
                        throw new \RuntimeException(
                            'JPL untuk sheet '
                            . $result['sheet']
                            . ' tidak ditemukan. Import dibatalkan.'
                        );
                    }

                    foreach ($result['rows'] as $row) {
                        if (! ($row['valid'] ?? false)) {
                            throw new \RuntimeException(
                                'Terdapat baris tidak valid pada sheet '
                                . $result['sheet']
                                . '. Import dibatalkan.'
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
                            } elseif (
                                ($row['nama_ka'] ?? null) !== null
                                && trim((string) $kereta->nama_ka) === ''
                            ) {
                                $kereta->update([
                                    'nama_ka' => $row['nama_ka'],
                                ]);
                            }

                            $keretaCache[$nomorKa] = $kereta;
                        }

                        $kereta = $keretaCache[$nomorKa];

                        /*
                         * Cek duplikasi berdasarkan KA, JPL, jam, dan tanggal.
                         * Jadwal pada tanggal lain tidak dianggap duplikat.
                         */
                        $existing = JadwalKa::query()
                            ->where('kereta_api_id', $kereta->id)
                            ->where('jpl_id', $jpl->id)
                            ->where('jam_lewat', $jam)
                            ->whereDate('tanggal_berlaku', $tanggalBerlaku)
                            ->first();

                        if ($existing) {
                            /*
                             * Backfill rute untuk data lama yang belum memiliki
                             * dari/ke. Data jadwal yang sudah ada tidak dibuat
                             * ulang dan tidak menimpa rute yang sudah terisi.
                             */
                            $routeUpdates = [];

                            if (
                                empty($existing->dari)
                                && ! empty($row['dari'])
                            ) {
                                $routeUpdates['dari'] = trim(
                                    (string) $row['dari']
                                );
                            }

                            if (
                                empty($existing->ke)
                                && ! empty($row['ke'])
                            ) {
                                $routeUpdates['ke'] = trim(
                                    (string) $row['ke']
                                );
                            }

                            if (! empty($routeUpdates)) {
                                $existing->update($routeUpdates);
                            }

                            $duplicates++;
                            $skipped++;

                            continue;
                        }

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
                            'is_active' => true,
                            'batch_import' => $batch,
                        ]);

                        $inserted++;
                    }
                }

                $spreadsheet->disconnectWorksheets();
            });

            // Transaksi berhasil: bersihkan file dan sesi sementara.
            Storage::disk('local')->delete($import['path']);
            session()->forget($key);

            $message = "Import selesai untuk tanggal "
                . Carbon::parse($tanggalBerlaku)->format('d-m-Y')
                . ". {$inserted} jadwal ditambahkan; "
                . "{$skipped} baris dilewati";

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
                    . class_basename($e)
                    . ' - '
                    . $e->getMessage();
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
            ->orWhere('kode_jpl', 'JPL ' . $title)
            ->orWhere('kode_jpl', 'JPL' . $title)
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
