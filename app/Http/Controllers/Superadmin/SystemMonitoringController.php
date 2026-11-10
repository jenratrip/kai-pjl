<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use SplFileObject;
use Throwable;

class SystemMonitoringController extends Controller
{
    private const ERROR_LEVELS = ['WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    private const MAX_LOG_FILES = 7;

    private const MAX_LOG_BYTES_PER_FILE = 2_000_000;

    private const MAX_LOG_ENTRIES = 2_000;

    public function index(Request $request): View|RedirectResponse
    {
        $checkedAt = CarbonImmutable::now();
        $application = $this->checkApplication();
        $database = $this->checkDatabase();
        $storage = $this->checkStorage();
        $scheduler = $this->checkScheduler();
        $queue = $this->checkQueue();
        $integrity = $this->checkDataIntegrity();

        $activitiesToday = null;

        if ($database['state'] === 'ok') {
            try {
                $activitiesToday = ActivityLog::query()->whereDate('created_at', today())->count();
            } catch (Throwable) {
                $activitiesToday = null;
            }
        }

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'level' => ['nullable', 'in:ALL,WARNING,ERROR,CRITICAL,ALERT,EMERGENCY'],
            'search' => ['nullable', 'string', 'max:200'],
        ]);

        $fromDate = isset($validated['from']) && $validated['from'] !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['from'])->startOfDay()
            : CarbonImmutable::now()->subMonths(3)->startOfDay();
        $toDate = isset($validated['to']) && $validated['to'] !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['to'])->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        if ($fromDate->gt($toDate)) {
            return back()
                ->withInput()
                ->withErrors(['from' => 'Tanggal awal tidak boleh melewati tanggal akhir.']);
        }

        $selectedLevel = strtoupper($validated['level'] ?? 'ALL');
        $search = trim($validated['search'] ?? '');
        [
            $allErrors,
            $errorsToday,
            $uniqueErrorGroupsToday,
            $logExists,
            $logReadable,
            $logsTruncated,
            $logCoverageComplete,
            $logCoverageSummary,
        ] = $this->readRecentErrors(
            $fromDate,
            $toDate,
            $selectedLevel,
            $search,
        );

        usort($allErrors, fn (array $left, array $right): int => $right['timestamp'] <=> $left['timestamp']);

        $perPage = 20;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $recentErrors = new LengthAwarePaginator(
            array_slice($allErrors, ($currentPage - 1) * $perPage, $perPage),
            count($allErrors),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()],
        );

        return view('superadmin.monitoring.index', [
            'checkedAt' => $checkedAt,
            'application' => $application,
            'database' => $database,
            'storage' => $storage,
            'scheduler' => $scheduler,
            'queue' => $queue,
            'integrity' => $integrity,
            'activitiesToday' => $activitiesToday,
            'errorsToday' => $errorsToday,
            'uniqueErrorGroupsToday' => $uniqueErrorGroupsToday,
            'recentErrors' => $recentErrors,
            'logExists' => $logExists,
            'logReadable' => $logReadable,
            'logsTruncated' => $logsTruncated,
            'logCoverageComplete' => $logCoverageComplete,
            'logCoverageSummary' => $logCoverageSummary,
            'fromDate' => $fromDate->toDateString(),
            'toDate' => $toDate->toDateString(),
            'selectedLevel' => $selectedLevel,
            'search' => $search,
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
            'environment' => app()->environment(),
            'debugMode' => (bool) config('app.debug'),
        ]);
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    private function checkApplication(): array
    {
        $responding = app()->isBooted() && request()->route() !== null;

        return [
            'state' => $responding ? 'ok' : 'error',
            'label' => $responding ? 'Merespons' : 'Tidak siap',
            'detail' => $responding
                ? 'Aplikasi berhasil memproses request monitoring ini.'
                : 'Aplikasi belum menyelesaikan bootstrap request.',
        ];
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->select('select 1');

            return ['state' => 'ok', 'label' => 'Terhubung', 'detail' => 'Query pemeriksaan berhasil dijalankan.'];
        } catch (Throwable) {
            return ['state' => 'error', 'label' => 'Bermasalah', 'detail' => 'Koneksi atau query pemeriksaan database gagal.'];
        }
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    private function checkStorage(): array
    {
        $path = storage_path();
        $available = is_dir($path) && is_readable($path) && is_writable($path);

        return [
            'state' => $available ? 'ok' : 'error',
            'label' => $available ? 'Dapat digunakan' : 'Tidak siap',
            'detail' => $available
                ? 'Direktori storage tersedia serta dapat dibaca dan ditulis oleh proses aplikasi.'
                : 'Direktori storage tidak tersedia atau izin baca/tulis tidak lengkap.',
        ];
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    private function checkScheduler(): array
    {
        try {
            $events = Schedule::events();
            $count = count($events);

            if ($count > 0) {
                return [
                    'state' => 'warning',
                    'label' => "{$count} tugas terdaftar; runtime belum terverifikasi",
                    'detail' => 'Konfigurasi jadwal terdeteksi. Aplikasi belum memiliki heartbeat untuk memastikan scheduler benar-benar berjalan.',
                ];
            }
        } catch (Throwable) {
            // Continue with a read-only inspection of the project's console routes.
        }

        $consoleRoutes = base_path('routes/console.php');

        if (! is_readable($consoleRoutes)) {
            return [
                'state' => 'unknown',
                'label' => 'Tidak dapat diperiksa',
                'detail' => 'Jadwal tidak dapat diperiksa dan tidak ada heartbeat scheduler.',
            ];
        }

        $contents = File::get($consoleRoutes);
        preg_match_all('/Schedule::command\s*\(/', $contents, $matches);
        $count = count($matches[0]);

        return [
            'state' => $count > 0 ? 'warning' : 'unknown',
            'label' => $count > 0 ? "{$count} deklarasi ditemukan; runtime belum terverifikasi" : 'Belum terverifikasi',
            'detail' => $count > 0
                ? 'Deklarasi schedule ditemukan pada routes/console.php. Status proses cron/scheduler tidak dapat dipastikan dari request web.'
                : 'Tidak ditemukan deklarasi schedule pada routes/console.php dan heartbeat tidak tersedia.',
        ];
    }

    /**
     * @return array{state: string, label: string, detail: string}
     */
    private function checkQueue(): array
    {
        $driver = (string) config('queue.default');

        if ($driver === 'sync') {
            return ['state' => 'ok', 'label' => 'Sinkron', 'detail' => 'Driver sync menjalankan pekerjaan pada request; worker terpisah tidak diperlukan.'];
        }

        if ($driver === 'null') {
            return ['state' => 'warning', 'label' => 'Tidak memproses antrean', 'detail' => 'Driver null membuang pekerjaan antrean.'];
        }

        if ($driver === 'database') {
            $connection = config('queue.connections.database.connection') ?: config('database.default');
            $table = (string) config('queue.connections.database.table', 'jobs');

            try {
                if (! Schema::connection($connection)->hasTable($table)) {
                    return ['state' => 'error', 'label' => 'Tabel antrean tidak tersedia', 'detail' => 'Tabel antrean '.$table.' tidak ditemukan pada koneksi yang dikonfigurasi.'];
                }

                $pending = DB::connection($connection)->table($table)->count();

                return [
                    'state' => 'warning',
                    'label' => "Backend tersedia; {$pending} pekerjaan menunggu",
                    'detail' => 'Worker database tidak dapat diverifikasi dari request web tanpa heartbeat worker.',
                ];
            } catch (Throwable) {
                return ['state' => 'error', 'label' => 'Backend antrean bermasalah', 'detail' => 'Tabel antrean tidak dapat diperiksa pada koneksi yang dikonfigurasi.'];
            }
        }

        return [
            'state' => 'warning',
            'label' => "Driver {$driver}; worker belum terverifikasi",
            'detail' => 'Driver antrean asinkron terkonfigurasi, tetapi status worker tidak tersedia tanpa heartbeat.',
        ];
    }

    /**
     * @return array<string, array{state: string, label: string, summary: string, details: array<int, string>}>
     */
    private function checkDataIntegrity(): array
    {
        return [
            'dinas' => $this->checkDinasIntegrity(),
            'jadwal' => $this->checkJadwalIntegrity(),
        ];
    }

    /**
     * @return array{state: string, label: string, summary: string, details: array<int, string>}
     */
    private function checkDinasIntegrity(): array
    {
        $requiredColumns = ['id', 'jpl_id', 'shift_id', 'tanggal_dinas', 'jadwal_ka_batch_id', 'jadwal_ka_snapshot'];

        try {
            if (! Schema::hasTable('dinas') || ! Schema::hasColumns('dinas', $requiredColumns)) {
                return $this->integrityResult('unknown', 'Tidak dapat diperiksa', ['Tabel atau kolom snapshot Dinas belum tersedia.']);
            }

            $snapshotCount = 0;
            $invalidCount = 0;
            $batchWithoutSnapshot = DB::table('dinas')
                ->whereNotNull('jadwal_ka_batch_id')
                ->whereNull('jadwal_ka_snapshot')
                ->count();

            DB::table('dinas')
                ->whereNotNull('jadwal_ka_snapshot')
                ->select($requiredColumns)
                ->orderBy('id')
                ->chunkById(200, function (Collection $rows) use (&$snapshotCount, &$invalidCount): void {
                    foreach ($rows as $row) {
                        $snapshotCount++;
                        $snapshot = json_decode((string) $row->jadwal_ka_snapshot, true);
                        $snapshotAt = is_array($snapshot) ? ($snapshot['snapshot_at'] ?? null) : null;
                        $validSnapshotAt = is_string($snapshotAt)
                            && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $snapshotAt) === 1;

                        if ($validSnapshotAt) {
                            try {
                                CarbonImmutable::parse($snapshotAt);
                            } catch (Throwable) {
                                $validSnapshotAt = false;
                            }
                        }

                        if (! is_array($snapshot)
                            || ($snapshot['format_version'] ?? null) !== 1
                            || ! $validSnapshotAt
                            || ! isset($snapshot['jadwal'])
                            || ! is_array($snapshot['jadwal'])
                            || (int) ($snapshot['dinas_id'] ?? 0) !== (int) $row->id
                            || (int) ($snapshot['jpl_id'] ?? 0) !== (int) $row->jpl_id
                            || ($snapshot['tanggal_dinas'] ?? null) !== $row->tanggal_dinas
                            || (array_key_exists('shift_id', $snapshot)
                                && ($snapshot['shift_id'] === null ? null : (int) $snapshot['shift_id'])
                                    !== ($row->shift_id === null ? null : (int) $row->shift_id))
                            || ($snapshot['jadwal_ka_batch_id'] ?? null) !== ($row->jadwal_ka_batch_id === null ? null : (int) $row->jadwal_ka_batch_id)
                            || ! in_array($snapshot['source'] ?? null, ['batch', 'legacy'], true)
                            || ($snapshot['source'] === 'batch'
                                && (! is_int($snapshot['jadwal_ka_batch_id'] ?? null) || $snapshot['jadwal_ka_batch_id'] < 1))
                            || ($snapshot['source'] === 'legacy' && ($snapshot['jadwal_ka_batch_id'] ?? null) !== null)) {
                            $invalidCount++;
                        }
                    }
                });

            $details = ["{$snapshotCount} Dinas memiliki snapshot; Dinas legacy tanpa snapshot tidak dianggap bermasalah."];

            if ($invalidCount > 0) {
                $details[] = "{$invalidCount} snapshot memiliki struktur atau konteks yang tidak konsisten.";
            }

            if ($batchWithoutSnapshot > 0) {
                $details[] = "{$batchWithoutSnapshot} Dinas memiliki batch tetapi snapshot masih NULL.";
            }

            $state = $invalidCount > 0 ? 'error' : ($batchWithoutSnapshot > 0 ? 'warning' : 'ok');

            return $this->integrityResult($state, $state === 'ok' ? 'Konsisten' : ($state === 'error' ? 'Perlu diperiksa' : 'Perlu perhatian'), $details);
        } catch (Throwable) {
            return $this->integrityResult('unknown', 'Tidak dapat diperiksa', ['Pemeriksaan struktur atau isi snapshot Dinas gagal.']);
        }
    }

    /**
     * @return array{state: string, label: string, summary: string, details: array<int, string>}
     */
    private function checkJadwalIntegrity(): array
    {
        try {
            $required = [
                'jadwal_ka' => ['id', 'jpl_id', 'jadwal_ka_batch_id'],
                'jadwal_ka_batches' => ['id', 'status', 'tanggal_mulai_berlaku'],
                'jadwal_ka_batch_jpl' => ['jadwal_ka_batch_id', 'jpl_id', 'tanggal_akhir_berlaku'],
            ];

            foreach ($required as $table => $columns) {
                if (! Schema::hasTable($table) || ! Schema::hasColumns($table, $columns)) {
                    return $this->integrityResult('unknown', 'Tidak dapat diperiksa', ["Tabel atau kolom {$table} belum tersedia."]);
                }
            }

            $orphanSchedules = DB::table('jadwal_ka as jadwal')
                ->leftJoin('jadwal_ka_batch_jpl as cakupan', function (JoinClause $join): void {
                    $join->on('cakupan.jadwal_ka_batch_id', '=', 'jadwal.jadwal_ka_batch_id')
                        ->on('cakupan.jpl_id', '=', 'jadwal.jpl_id');
                })
                ->whereNotNull('jadwal.jadwal_ka_batch_id')
                ->whereNull('cakupan.jadwal_ka_batch_id')
                ->count();

            $emptyScopes = DB::table('jadwal_ka_batch_jpl as cakupan')
                ->leftJoin('jadwal_ka as jadwal', function (JoinClause $join): void {
                    $join->on('jadwal.jadwal_ka_batch_id', '=', 'cakupan.jadwal_ka_batch_id')
                        ->on('jadwal.jpl_id', '=', 'cakupan.jpl_id');
                })
                ->whereNull('jadwal.id')
                ->count();

            $batchesMissingDate = DB::table('jadwal_ka_batches')
                ->whereIn('status', ['scheduled', 'active', 'archived'])
                ->whereNull('tanggal_mulai_berlaku')
                ->count();

            $ranges = DB::table('jadwal_ka_batch_jpl as cakupan')
                ->join('jadwal_ka_batches as batch', 'batch.id', '=', 'cakupan.jadwal_ka_batch_id')
                ->whereIn('batch.status', ['scheduled', 'active', 'archived'])
                ->orderBy('cakupan.jpl_id')
                ->orderBy('batch.tanggal_mulai_berlaku')
                ->orderBy('batch.id')
                ->select([
                    'cakupan.jadwal_ka_batch_id as batch_id',
                    'cakupan.jpl_id',
                    'batch.tanggal_mulai_berlaku',
                    'cakupan.tanggal_akhir_berlaku',
                ])
                ->cursor();

            $overlaps = 0;
            $invalidRanges = 0;
            $lastByJpl = [];

            foreach ($ranges as $range) {
                if ($range->tanggal_mulai_berlaku === null) {
                    continue;
                }

                $start = CarbonImmutable::parse($range->tanggal_mulai_berlaku)->toDateString();
                $end = $range->tanggal_akhir_berlaku === null
                    ? null
                    : CarbonImmutable::parse($range->tanggal_akhir_berlaku)->toDateString();

                if ($end !== null && $end < $start) {
                    $invalidRanges++;
                }

                $previous = $lastByJpl[$range->jpl_id] ?? null;

                if ($previous !== null && ($previous['end'] === null || $start <= $previous['end'])) {
                    $overlaps++;
                }

                if ($previous === null || ($previous['end'] !== null && ($end === null || $end > $previous['end']))) {
                    $lastByJpl[$range->jpl_id] = ['end' => $end, 'batch_id' => $range->batch_id];
                }
            }

            $details = ["{$orphanSchedules} jadwal batch tidak memiliki cakupan JPL yang cocok."];

            if ($emptyScopes > 0) {
                $details[] = "{$emptyScopes} cakupan batch tidak memiliki record jadwal KA.";
            }

            if ($batchesMissingDate > 0) {
                $details[] = "{$batchesMissingDate} batch operasional tidak memiliki tanggal mulai berlaku.";
            }

            if ($overlaps > 0) {
                $details[] = "{$overlaps} rentang efektif batch bertumpang tindih pada JPL yang sama.";
            }

            if ($invalidRanges > 0) {
                $details[] = "{$invalidRanges} rentang efektif berakhir sebelum tanggal mulainya.";
            }

            $state = ($orphanSchedules > 0 || $batchesMissingDate > 0 || $overlaps > 0 || $invalidRanges > 0)
                ? 'error'
                : ($emptyScopes > 0 ? 'warning' : 'ok');

            return $this->integrityResult($state, $state === 'ok' ? 'Konsisten' : ($state === 'error' ? 'Perlu diperiksa' : 'Perlu perhatian'), $details);
        } catch (Throwable) {
            return $this->integrityResult('unknown', 'Tidak dapat diperiksa', ['Pemeriksaan relasi atau rentang batch Jadwal KA gagal.']);
        }
    }

    /**
     * @param  array<int, string>  $details
     * @return array{state: string, label: string, summary: string, details: array<int, string>}
     */
    private function integrityResult(string $state, string $label, array $details): array
    {
        return ['state' => $state, 'label' => $label, 'summary' => implode(' ', $details), 'details' => $details];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: int, 3: bool, 4: bool, 5: bool, 6: bool, 7: string}
     */
    private function readRecentErrors(
        CarbonImmutable $fromDate,
        CarbonImmutable $toDate,
        string $selectedLevel,
        string $search,
    ): array {
        $logDirectory = (string) config('app.monitoring_log_directory', storage_path('logs'));
        $logFiles = [];

        if (File::isDirectory($logDirectory)) {
            foreach (File::files($logDirectory) as $file) {
                if (! is_link($file->getPathname())
                    && preg_match('/^laravel(?:-\d{4}-\d{2}-\d{2})?\.log$/i', $file->getFilename())) {
                    $logFiles[] = $file->getPathname();
                }
            }
        }

        usort($logFiles, fn (string $left, string $right): int => filemtime($right) <=> filemtime($left));
        $logExists = $logFiles !== [];
        $availableFileCount = count($logFiles);
        $logsTruncated = $availableFileCount > self::MAX_LOG_FILES;
        $coverageComplete = $logExists && ! $logsTruncated;
        $coverageReasons = [];

        if (! $logExists) {
            $coverageReasons[] = 'file log tidak ditemukan';
        } elseif ($logsTruncated) {
            $coverageReasons[] = 'jumlah file melebihi batas pemindaian';
        }

        $availableBytes = array_sum(array_map(static fn (string $path): int => (int) (filesize($path) ?: 0), $logFiles));
        $logFiles = array_slice($logFiles, 0, self::MAX_LOG_FILES);
        $allErrors = [];
        $errorsToday = 0;
        $uniqueErrorsToday = [];
        $scannedBytes = 0;
        $logReadable = false;

        foreach ($logFiles as $logPath) {
            if (! is_readable($logPath)) {
                $coverageComplete = false;
                $coverageReasons[] = 'satu atau lebih file tidak dapat dibaca';

                continue;
            }

            $logReadable = true;
            $size = filesize($logPath) ?: 0;
            $startOffset = max(0, $size - self::MAX_LOG_BYTES_PER_FILE);
            $scannedBytes += $size - $startOffset;
            $logsTruncated = $logsTruncated || $startOffset > 0;

            if ($startOffset > 0) {
                $coverageComplete = false;
                $coverageReasons[] = 'ukuran satu atau lebih file melebihi batas 2 MB';
            }

            $logFile = new SplFileObject($logPath, 'r');

            if ($startOffset > 0) {
                $logFile->fseek($startOffset);
                $logFile->fgets();
            }

            $currentEntry = null;

            while (! $logFile->eof()) {
                $line = $logFile->fgets();

                if (preg_match('/^\[([^\]]+)\]\s+[^.\s]+\.([A-Z]+):\s*(.*)$/i', $line, $matches)) {
                    if ($currentEntry !== null) {
                        $this->appendEntryIfInRange($currentEntry, $fromDate, $toDate, $selectedLevel, $search, $allErrors, $errorsToday, $uniqueErrorsToday);
                    }

                    $level = strtoupper($matches[2]);

                    if (! in_array($level, self::ERROR_LEVELS, true)) {
                        $currentEntry = null;

                        continue;
                    }

                    try {
                        $entryDate = CarbonImmutable::parse(trim($matches[1]));
                    } catch (Throwable) {
                        $entryDate = null;
                    }

                    $currentEntry = [
                        'date' => trim($matches[1]),
                        'timestamp' => $entryDate?->timestamp ?? 0,
                        'level' => $level,
                        'message' => $this->sanitizeLogText(trim($matches[3]), 1200),
                        'file' => null,
                        'line' => null,
                        'stack_trace' => '',
                    ];

                    continue;
                }

                if ($currentEntry === null) {
                    continue;
                }

                if (strlen($currentEntry['stack_trace']) < 12000) {
                    $currentEntry['stack_trace'] .= $this->sanitizeLogText($line, 1500);
                }

                if ($currentEntry['file'] === null && preg_match('/#\d+\s+(.+?\.php)\((\d+)\):/', $line, $location)) {
                    $currentEntry['file'] = $this->relativePath(trim($location[1]));
                    $currentEntry['line'] = (int) $location[2];
                }

                if ($currentEntry['file'] === null && preg_match('/in\s+(.+?\.php)\s+on line\s+(\d+)/', $line, $location)) {
                    $currentEntry['file'] = $this->relativePath(trim($location[1]));
                    $currentEntry['line'] = (int) $location[2];
                }
            }

            if ($currentEntry !== null) {
                $this->appendEntryIfInRange($currentEntry, $fromDate, $toDate, $selectedLevel, $search, $allErrors, $errorsToday, $uniqueErrorsToday);
            }

            unset($logFile, $currentEntry);
        }

        $logsTruncated = $logsTruncated || count($allErrors) >= self::MAX_LOG_ENTRIES;
        $logCoverageSummary = $coverageComplete
            ? sprintf('Lengkap: seluruh %d file log (%s) berhasil dipindai.', $availableFileCount, number_format($scannedBytes / 1_000_000, 2, ',', '.').' MB')
            : sprintf(
                'Parsial: %d dari %d file log diperiksa (%s dari %s). %s.',
                count($logFiles),
                $availableFileCount,
                number_format($scannedBytes / 1_000_000, 2, ',', '.').' MB',
                number_format($availableBytes / 1_000_000, 2, ',', '.').' MB tersedia',
                implode('; ', array_unique($coverageReasons)),
            );

        if (! $logReadable && $logExists) {
            $logCoverageSummary = 'Parsial: tidak ada file log yang dapat dibaca; total error harian tidak dapat dipastikan.';
        }

        return [$allErrors, $errorsToday, count($uniqueErrorsToday), $logExists, $logReadable, $logsTruncated, $coverageComplete, $logCoverageSummary];
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<int, array<string, mixed>>  $allErrors
     * @param  array<string, true>  $uniqueErrorsToday
     */
    private function appendEntryIfInRange(
        array $entry,
        CarbonImmutable $fromDate,
        CarbonImmutable $toDate,
        string $selectedLevel,
        string $search,
        array &$allErrors,
        int &$errorsToday,
        array &$uniqueErrorsToday,
    ): void {
        if (empty($entry['timestamp'])) {
            return;
        }

        $entryDate = CarbonImmutable::createFromTimestamp($entry['timestamp']);

        if ($entryDate->isToday() && in_array($entry['level'], ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true)) {
            $errorsToday++;
            $fingerprint = hash('sha256', implode("\0", [
                (string) $entry['level'],
                (string) ($entry['file'] ?? ''),
                (string) ($entry['line'] ?? ''),
                (string) ($entry['message'] ?? ''),
            ]));
            $uniqueErrorsToday[$fingerprint] = true;
        }

        if ($entryDate->lt($fromDate) || $entryDate->gt($toDate)) {
            return;
        }

        if ($selectedLevel !== 'ALL' && $entry['level'] !== $selectedLevel) {
            return;
        }

        if ($search !== '') {
            $searchable = strtolower(($entry['message'] ?? '').' '.($entry['file'] ?? '').' '.($entry['stack_trace'] ?? '').' '.($entry['level'] ?? ''));

            if (! str_contains($searchable, mb_strtolower($search))) {
                return;
            }
        }

        if (count($allErrors) < self::MAX_LOG_ENTRIES) {
            $allErrors[] = $entry;
        }
    }

    private function sanitizeLogText(string $text, int $limit): string
    {
        $text = preg_replace(
            '/(?<![A-Za-z0-9])(password|passwd|pass|token|secret|authorization|api[_-]?key|access[_-]?key|client[_-]?secret|credential|private[_-]?key)\b\s*(?:=>|[=:])\s*(["\']?)([^\s,;"\'}]+)/i',
            '$1=[disamarkan]',
            $text,
        ) ?? $text;
        $text = preg_replace('/\bBearer\s+[^\s,;]+/i', 'Bearer [disamarkan]', $text) ?? $text;
        $text = preg_replace('/(https?:\/\/)[^\s\/@:]+:[^\s\/@]+@/i', '$1[disamarkan]@', $text) ?? $text;
        $text = preg_replace('/([?&](?:password|token|secret|api[_-]?key|access[_-]?key)=)[^&\s]+/i', '$1[disamarkan]', $text) ?? $text;

        return mb_substr($text, 0, $limit);
    }

    private function relativePath(string $path): string
    {
        $basePath = str_replace('\\', '/', base_path());
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, $basePath.'/')
            ? substr($path, strlen($basePath) + 1)
            : basename($path);
    }
}
