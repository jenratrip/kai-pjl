<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Throwable;

class SystemMonitoringController extends Controller
{
    private const ERROR_LEVELS = [
        'WARNING',
        'ERROR',
        'CRITICAL',
        'ALERT',
        'EMERGENCY',
    ];

    public function index(Request $request)
    {
        // 1. Periksa koneksi database.
        $databaseConnected = false;

        try {
            DB::connection()->getPdo();
            $databaseConnected = true;
        } catch (Throwable $e) {
            report($e);
        }

        // 2. Hitung aktivitas hari ini.
        $activitiesToday = ActivityLog::whereDate('created_at', today())->count();

        // 3. Tentukan rentang riwayat (default: tiga bulan terakhir).
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'level' => ['nullable', 'in:ALL,WARNING,ERROR,CRITICAL,ALERT,EMERGENCY'],
            'search' => ['nullable', 'string', 'max:200'],
        ]);

        $fromDate = isset($validated['from']) && $validated['from'] !== ''
            ? Carbon::createFromFormat('Y-m-d', $validated['from'])->startOfDay()
            : now()->subMonths(3)->startOfDay();

        $toDate = isset($validated['to']) && $validated['to'] !== ''
            ? Carbon::createFromFormat('Y-m-d', $validated['to'])->endOfDay()
            : now()->endOfDay();

        if ($fromDate->gt($toDate)) {
            return back()
                ->withInput()
                ->withErrors(['from' => 'Tanggal awal tidak boleh melewati tanggal akhir.']);
        }

        $selectedLevel = strtoupper($validated['level'] ?? 'ALL');
        $search = trim($validated['search'] ?? '');
        $errorsToday = 0;
        $allErrors = [];

        // 4. Baca semua file log Laravel yang tersedia secara bertahap.
        $logDirectory = storage_path('logs');
        $logFiles = [];

        if (File::isDirectory($logDirectory)) {
            foreach (File::files($logDirectory) as $file) {
                $name = $file->getFilename();

                // Hanya proses file log Laravel, termasuk laravel.log dan log harian.
                if (preg_match('/^laravel(?:-\d{4}-\d{2}-\d{2})?\.log$/i', $name)) {
                    $logFiles[] = $file->getPathname();
                }
            }
        }

        sort($logFiles);
        $logExists = count($logFiles) > 0;
        $logReadable = false;

        foreach ($logFiles as $logPath) {
            if (!is_readable($logPath)) {
                continue;
            }

            $logReadable = true;
            $currentEntry = null;
            $logFile = new \SplFileObject($logPath, 'r');

            while (!$logFile->eof()) {
                $line = $logFile->fgets();

                // Format standar Monolog/Laravel: [tanggal] channel.LEVEL: pesan
                if (preg_match(
                    '/^\[([^\]]+)\]\s+[^.\s]+\.([A-Z]+):\s*(.*)$/i',
                    $line,
                    $matches
                )) {
                    if ($currentEntry !== null) {
                        $this->appendEntryIfInRange(
                            $currentEntry,
                            $fromDate,
                            $toDate,
                            $selectedLevel,
                            $search,
                            $allErrors,
                            $errorsToday
                        );
                    }

                    $dateText = trim($matches[1]);
                    $level = strtoupper($matches[2]);
                    $message = trim($matches[3]);

                    // Jangan membatasi jenis pesan berdasarkan kategori yang dikenal di UI.
                    // Namun, daftar level tetap dibatasi pada warning/error dan level lebih tinggi.
                    if (!in_array($level, self::ERROR_LEVELS, true)) {
                        $currentEntry = null;
                        continue;
                    }

                    try {
                        $entryDate = Carbon::parse($dateText);
                    } catch (Throwable $e) {
                        $entryDate = null;
                    }

                    $currentEntry = [
                        'date' => $dateText,
                        'timestamp' => $entryDate?->timestamp ?? 0,
                        'level' => $level,
                        'message' => $this->sanitizeLogText($message, 1200),
                        'file' => null,
                        'line' => null,
                        'stack_trace' => '',
                    ];

                    continue;
                }

                if ($currentEntry === null) {
                    continue;
                }

                // Simpan stack trace secukupnya agar halaman tidak terlalu berat.
                if (strlen($currentEntry['stack_trace']) < 12000) {
                    $currentEntry['stack_trace'] .= $this->sanitizeLogText($line, 1500);
                }

                // Lokasi pada format stack trace PHP: #0 /path/file.php(93):
                if (
                    $currentEntry['file'] === null
                    && preg_match('/#\d+\s+(.+?\.php)\((\d+)\):/', $line, $location)
                ) {
                    $currentEntry['file'] = $this->relativePath(trim($location[1]));
                    $currentEntry['line'] = (int) $location[2];
                }

                // Format alternatif: in /path/file.php on line 93
                if (
                    $currentEntry['file'] === null
                    && preg_match('/in\s+(.+?\.php)\s+on line\s+(\d+)/', $line, $location)
                ) {
                    $currentEntry['file'] = $this->relativePath(trim($location[1]));
                    $currentEntry['line'] = (int) $location[2];
                }
            }

            if ($currentEntry !== null) {
                $this->appendEntryIfInRange(
                    $currentEntry,
                    $fromDate,
                    $toDate,
                    $selectedLevel,
                    $search,
                    $allErrors,
                    $errorsToday
                );
            }

            unset($logFile, $currentEntry);
        }

        // Urutkan dari terbaru ke terlama sebelum paginasi.
        usort($allErrors, fn (array $a, array $b) => $b['timestamp'] <=> $a['timestamp']);

        $perPage = 20;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $total = count($allErrors);
        $items = array_slice($allErrors, ($currentPage - 1) * $perPage, $perPage);

        $recentErrors = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return view('superadmin.monitoring.index', [
            'databaseConnected' => $databaseConnected,
            'activitiesToday' => $activitiesToday,
            'errorsToday' => $errorsToday,
            'recentErrors' => $recentErrors,
            'logExists' => $logExists,
            'logReadable' => $logReadable,
            'fromDate' => $fromDate->toDateString(),
            'toDate' => $toDate->toDateString(),
            'selectedLevel' => $selectedLevel,
            'search' => $search,
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
            'environment' => app()->environment(),
            'debugMode' => config('app.debug'),
        ]);
    }

    private function appendEntryIfInRange(
        array $entry,
        Carbon $fromDate,
        Carbon $toDate,
        string $selectedLevel,
        string $search,
        array &$allErrors,
        int &$errorsToday
    ): void {
        if (empty($entry['timestamp'])) {
            return;
        }

        $entryDate = Carbon::createFromTimestamp($entry['timestamp']);

        if ($entryDate->isToday() && in_array($entry['level'], ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true)) {
            $errorsToday++;
        }

        if ($entryDate->lt($fromDate) || $entryDate->gt($toDate)) {
            return;
        }

        if ($selectedLevel !== 'ALL' && $entry['level'] !== $selectedLevel) {
            return;
        }

        if ($search !== '') {
            $searchable = strtolower(
                ($entry['message'] ?? '') . ' ' .
                ($entry['file'] ?? '') . ' ' .
                ($entry['stack_trace'] ?? '') . ' ' .
                ($entry['level'] ?? '')
            );

            if (!str_contains($searchable, mb_strtolower($search))) {
                return;
            }
        }

        $allErrors[] = $entry;
    }

    private function sanitizeLogText(string $text, int $limit): string
    {
        // Redaksi kredensial/token sederhana sebelum log ditampilkan ke browser.
        $text = preg_replace(
            '/\b(password|passwd|token|secret|authorization|api[_-]?key)\b\s*[=:]\s*([^\s,;]+)/i',
            '$1=[disamarkan]',
            $text
        ) ?? $text;

        return mb_substr($text, 0, $limit);
    }

    private function relativePath(string $path): string
    {
        $basePath = str_replace('\\', '/', base_path());
        $path = str_replace('\\', '/', $path);

        if (str_starts_with($path, $basePath . '/')) {
            return substr($path, strlen($basePath) + 1);
        }

        return basename($path);
    }
}