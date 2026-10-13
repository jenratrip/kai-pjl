<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class SystemMonitoringController extends Controller
{
    public function index()
    {
        // 1. Periksa koneksi database
        $databaseConnected = false;

        try {
            DB::connection()->getPdo();
            $databaseConnected = true;
        } catch (Throwable $e) {
            report($e);
        }

        // 2. Hitung aktivitas hari ini
        $activitiesToday = ActivityLog::whereDate(
            'created_at',
            today()
        )->count();

        // 3. Siapkan informasi log
        $logPath = storage_path('logs/laravel.log');
        $logExists = File::exists($logPath);
        $errorsToday = 0;
        $recentErrors = [];
        $currentError = null;

        // 4. Baca log dan kelompokkan baris berdasarkan entri error
        if ($logExists && is_readable($logPath)) {
            $logFile = new \SplFileObject($logPath, 'r');

            while (!$logFile->eof()) {
                $line = $logFile->fgets();

                // Awal entri error Laravel
                if (preg_match(
                    '/^\[([^\]]+)\]\s+\w+\.(ERROR|CRITICAL|ALERT|EMERGENCY):\s*(.*)$/',
                    $line,
                    $matches
                )) {
                    // Simpan entri sebelumnya
                    if ($currentError !== null) {
                        $recentErrors[] = $currentError;
                    }

                    $date = trim($matches[1]);
                    $level = strtoupper($matches[2]);
                    $message = trim($matches[3]);

                    // Hilangkan bagian query/koneksi yang mungkin sensitif
                    $message = preg_replace(
                        '/\s*\(Connection:.*$/s',
                        '',
                        $message
                    );

                    // Hindari menampilkan kredensial atau token
                    $message = preg_replace(
                        '/(password|token|secret|authorization)\s*[=:]\s*[^\s,;]+/i',
                        '$1=[disamarkan]',
                        $message
                    );

                    $currentError = [
                        'date' => $date,
                        'level' => $level,
                        'message' => mb_substr(
                            $message ?: 'Pesan error tidak tersedia.',
                            0,
                            500
                        ),
                        'file' => null,
                        'line' => null,
                    ];

                    if (substr($date, 0, 10) === today()->toDateString()) {
                        $errorsToday++;
                    }

                    continue;
                }

                // Abaikan baris lanjutan jika belum ada entri error
                if ($currentError === null) {
                    continue;
                }

                // Ambil lokasi dari stack trace, misalnya:
                // #0 .../ExampleController.php(93):
                if (
                    $currentError['file'] === null &&
                    preg_match(
                        '/#\d+\s+(.+?\.php)\((\d+)\):/',
                        $line,
                        $location
                    )
                ) {
                    $currentError['file'] = $this->relativePath(
                        trim($location[1])
                    );
                    $currentError['line'] = (int) $location[2];
                }

                // Alternatif format: in /path/file.php on line 93
                if (
                    $currentError['file'] === null &&
                    preg_match(
                        '/in\s+(.+?\.php)\s+on line\s+(\d+)/',
                        $line,
                        $location
                    )
                ) {
                    $currentError['file'] = $this->relativePath(
                        trim($location[1])
                    );
                    $currentError['line'] = (int) $location[2];
                }
            }

            // Simpan entri error terakhir
            if ($currentError !== null) {
                $recentErrors[] = $currentError;
            }

            $logFile = null;
        }

        // 5. Tampilkan maksimal 10 error terbaru
        $recentErrors = array_slice(
            array_reverse($recentErrors),
            0,
            10
        );

        return view('superadmin.monitoring.index', [
            'databaseConnected' => $databaseConnected,
            'activitiesToday' => $activitiesToday,
            'errorsToday' => $errorsToday,
            'recentErrors' => $recentErrors,
            'logExists' => $logExists,
            'laravelVersion' => app()->version(),
            'phpVersion' => PHP_VERSION,
            'environment' => app()->environment(),
            'debugMode' => config('app.debug'),
        ]);
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