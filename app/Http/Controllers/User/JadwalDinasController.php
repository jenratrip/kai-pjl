<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JadwalDinasController extends Controller
{
    /**
     * Display the user's schedule for a given month.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Ambil data dinas aktif untuk header.
        $dinasAktif = $this->getDinasAktif();

        $month = (int) ($request->query('month') ?? now()->month);
        $year = (int) ($request->query('year') ?? now()->year);

        // Validasi bulan dan tahun.
        $month = max(1, min(12, $month));
        $year = max(2000, min(2100, $year));

        // Hitung rentang tanggal bulan yang dipilih.
        $firstDay = CarbonImmutable::create($year, $month, 1);

        $startDate = $firstDay->toDateString();
        $endDate = $firstDay->endOfMonth()->toDateString();

        // Ambil jadwal dinas milik pengguna.
        $schedules = Dinas::with([
            'jpl:id,kode_jpl,nama_jpl',
            'shift:id,nama_shift,jam_mulai,jam_selesai',
            'userDigantikan',
        ])
            ->where('user_id', $user->id)
            ->whereBetween('tanggal_dinas', [$startDate, $endDate])
            ->orderBy('tanggal_dinas')
            ->orderBy('jam_mulai')
            ->get();

        // Ambil data hari libur pengguna.
        $holidays = DB::table('jadwal_dinas_libur')
            ->where('user_id', $user->id)
            ->whereBetween('tanggal_dinas', [$startDate, $endDate])
            ->orderBy('tanggal_dinas')
            ->get();

        // Bangun ringkasan statistik.
        $summary = $this->buildSummary($schedules, $holidays);

        // Siapkan data jadwal dan libur untuk ditampilkan.
        $displaySchedules = $this->formatSchedulesForDisplay($schedules);
        $displayHolidays = $this->formatHolidaysForDisplay($holidays);

        // Gabungkan dan urutkan jadwal serta hari libur.
        $allItems = $this->mergeSchedulesAndHolidays(
            $displaySchedules,
            $displayHolidays
        );

        // Bangun data kalender.
        $calendar = $this->buildCalendar(
            $year,
            $month,
            $schedules,
            $holidays
        );

        return view('user.jadwal-dinas.index', [
            'month' => $month,
            'year' => $year,
            'summary' => $summary,
            'schedules' => $allItems,
            'rawSchedules' => $schedules,
            'calendar' => $calendar,
            'hasData' => $schedules->isNotEmpty() || $holidays->isNotEmpty(),
            'monthNames' => $this->getMonthNames(),

            // Data dinas aktif untuk komponen header.
            'dinasAktif' => $dinasAktif,
        ]);
    }

    /**
     * Mengambil data dinas aktif berdasarkan session.
     *
     * Dinas harus:
     * - Sesuai dengan ID dinas pada session.
     * - Dimiliki oleh user yang sedang login.
     * - Berstatus berlangsung.
     */
    private function getDinasAktif(): ?Dinas
    {
        if (! Auth::check()) {
            return null;
        }

        $dinasId = request()->session()->get('dinas_id');

        if (! is_numeric($dinasId) || (int) $dinasId <= 0) {
            return null;
        }

        return Dinas::with([
            'jpl.resort',
            'shift',
            'user',
            'userDigantikan',
        ])
            ->whereKey((int) $dinasId)
            ->where('user_id', Auth::id())
            ->where('status', 'berlangsung')
            ->first();
    }

    /**
     * Build summary statistics for the selected period.
     *
     * Menggunakan Support Collection agar dapat menerima:
     * - Illuminate\Database\Eloquent\Collection
     * - Illuminate\Support\Collection
     *
     * @return array<string, int>
     */
    private function buildSummary(
        Collection $schedules,
        Collection $holidays
    ): array {
        $dayCount = [];
        $holidayDates = [];

        $shiftCount = [
            'pagi' => 0,
            'siang' => 0,
            'malam' => 0,
        ];

        // Hitung hari bertugas dan jumlah setiap shift.
        foreach ($schedules as $schedule) {
            $date = Carbon::parse($schedule->tanggal_dinas)
                ->toDateString();

            $dayCount[$date] = true;

            if ($schedule->shift === null) {
                continue;
            }

            $shiftName = mb_strtolower(
                trim((string) $schedule->shift->nama_shift)
            );

            if (str_contains($shiftName, 'pagi')) {
                $shiftCount['pagi']++;
            } elseif (str_contains($shiftName, 'siang')) {
                $shiftCount['siang']++;
            } elseif (str_contains($shiftName, 'malam')) {
                $shiftCount['malam']++;
            }
        }

        // Hitung tanggal libur yang unik.
        foreach ($holidays as $holiday) {
            $date = Carbon::parse($holiday->tanggal_dinas)
                ->toDateString();

            $holidayDates[$date] = true;
        }

        return [
            'total_hari_bertugas' => count($dayCount),
            'total_hari_libur' => count($holidayDates),
            'shift_pagi' => $shiftCount['pagi'],
            'shift_siang' => $shiftCount['siang'],
            'shift_malam' => $shiftCount['malam'],
        ];
    }

    /**
     * Format schedules for display.
     *
     * @return array<array<string, mixed>>
     */
    private function formatSchedulesForDisplay(
        Collection $schedules
    ): array {
        return $schedules->map(function (Dinas $dinas) {
            $date = Carbon::parse($dinas->tanggal_dinas);

            $userDigantikan = $dinas->userDigantikan?->name;

            return [
                'type' => 'schedule',

                // ID dinas untuk kebutuhan fitur tukar jadwal.
                'dinas_id' => $dinas->id,

                'date' => $date->format('Y-m-d'),

                'date_display' => $date->translatedFormat('d F Y'),

                'day_name' => $date->translatedFormat('l'),

                'jpl_code' => $dinas->jpl?->kode_jpl ?? 'N/A',

                'jpl_name' => $dinas->jpl?->nama_jpl ?? 'N/A',

                'shift_name' => $dinas->shift?->nama_shift ?? 'N/A',

                'shift_type' => $this->getShiftType($dinas->shift),

                'jam_mulai' => $dinas->jam_mulai?->format('H:i') ?? 'N/A',

                'jam_selesai' => $dinas->jam_selesai?->format('H:i') ?? 'N/A',

                'status' => $dinas->status,

                'status_display' => $this->getStatusDisplay(
                    (string) $dinas->status
                ),

                'is_pengganti' => (bool) $dinas->is_pengganti,

                'user_digantikan' => $userDigantikan,

                'is_replacement_of' => $userDigantikan,
            ];
        })->toArray();
    }

    /**
     * Format holidays for display.
     *
     * @return array<array<string, mixed>>
     */
    private function formatHolidaysForDisplay(
        Collection $holidays
    ): array {
        return $holidays->map(function ($holiday) {
            $date = Carbon::parse($holiday->tanggal_dinas);

            return [
                'type' => 'holiday',

                'date' => $date->format('Y-m-d'),

                'date_display' => $date->translatedFormat('d F Y'),

                'day_name' => $date->translatedFormat('l'),

                'jpl_code' => 'LIBUR',

                'jpl_name' => 'Hari Libur',

                'shift_name' => 'L',

                'shift_type' => 'libur',

                'jam_mulai' => '-',

                'jam_selesai' => '-',

                'status' => 'libur',

                'status_display' => 'Libur',

                'is_pengganti' => false,

                'user_digantikan' => null,

                'is_replacement_of' => null,
            ];
        })->toArray();
    }

    /**
     * Merge and sort schedules and holidays chronologically.
     *
     * @param array<array<string, mixed>> $schedules
     * @param array<array<string, mixed>> $holidays
     * @return array<array<string, mixed>>
     */
    private function mergeSchedulesAndHolidays(
        array $schedules,
        array $holidays
    ): array {
        $merged = array_merge($schedules, $holidays);

        usort($merged, function (array $a, array $b): int {
            // Urutkan berdasarkan tanggal.
            $dateComparison = strcmp($a['date'], $b['date']);

            if ($dateComparison !== 0) {
                return $dateComparison;
            }

            // Jika tanggal sama, jadwal ditampilkan sebelum libur.
            $typeComparison = strcmp(
                $a['type'] === 'schedule' ? '0' : '1',
                $b['type'] === 'schedule' ? '0' : '1'
            );

            if ($typeComparison !== 0) {
                return $typeComparison;
            }

            // Jika sama-sama jadwal, urutkan berdasarkan jam mulai.
            return strcmp(
                $a['jam_mulai'] ?? '',
                $b['jam_mulai'] ?? ''
            );
        });

        return $merged;
    }

    /**
     * Determine the shift type for display.
     */
    private function getShiftType($shift): string
    {
        if ($shift === null) {
            return 'unknown';
        }

        $name = mb_strtolower(
            trim((string) $shift->nama_shift)
        );

        if (str_contains($name, 'pagi')) {
            return 'pagi';
        }

        if (str_contains($name, 'siang')) {
            return 'siang';
        }

        if (str_contains($name, 'malam')) {
            return 'malam';
        }

        return 'unknown';
    }

    /**
     * Get display text for status.
     */
    private function getStatusDisplay(string $status): string
    {
        return match ($status) {
            'dijadwalkan' => 'Dijadwalkan',
            'berlangsung' => 'Berlangsung',
            'selesai' => 'Selesai',
            'libur' => 'Libur',
            default => 'Tidak Diketahui',
        };
    }

    /**
     * Build calendar data for the month.
     *
     * @return array<array<string, mixed>>
     */
    private function buildCalendar(
        int $year,
        int $month,
        Collection $schedules,
        Collection $holidays
    ): array {
        $firstDay = CarbonImmutable::create($year, $month, 1);
        $lastDay = $firstDay->endOfMonth();

        // Kelompokkan jadwal berdasarkan tanggal.
        $schedulesByDate = [];

        foreach ($schedules as $schedule) {
            $date = Carbon::parse($schedule->tanggal_dinas)
                ->format('Y-m-d');

            if (! isset($schedulesByDate[$date])) {
                $schedulesByDate[$date] = [];
            }

            $schedulesByDate[$date][] = [
                'shift_type' => $this->getShiftType($schedule->shift),

                'shift_name' => $schedule->shift?->nama_shift ?? 'N/A',
            ];
        }

        // Kelompokkan tanggal libur.
        $holidaysByDate = [];

        foreach ($holidays as $holiday) {
            $date = Carbon::parse($holiday->tanggal_dinas)
                ->format('Y-m-d');

            $holidaysByDate[$date] = true;
        }

        // Bangun kalender dengan awal minggu hari Senin.
        $calendar = [];

        $currentDate = $firstDay->startOfWeek(Carbon::MONDAY);

        while ($currentDate <= $lastDay) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $dateStr = $currentDate->toDateString();

                $isCurrentMonth = $currentDate->month === $month;

                $shifts = $schedulesByDate[$dateStr] ?? [];

                $isHoliday = isset($holidaysByDate[$dateStr]);

                $week[] = [
                    'date' => $dateStr,

                    'day' => $currentDate->day,

                    'is_current_month' => $isCurrentMonth,

                    'is_today' => $dateStr === today()->toDateString(),

                    'is_holiday' => $isHoliday,

                    'shifts' => $shifts,

                    'has_schedule' => ! empty($shifts),
                ];

                $currentDate = $currentDate->addDay();
            }

            $calendar[] = $week;
        }

        return $calendar;
    }

    /**
     * Get month names.
     *
     * @return array<int, string>
     */
    private function getMonthNames(): array
    {
        return [
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
    }
}
