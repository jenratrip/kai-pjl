<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dinas;
use App\Models\JadwalKaBatch;
use App\Models\Jpl;
use App\Models\Shift;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\DinasRosterWorkbookParser;
use App\Services\DinasJadwalSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class DinasRosterImportController extends Controller
{
    private const SHIFT_CODES = [
        'P' => ['name' => 'Pagi', 'start' => '06:00:00', 'end' => '14:00:00'],
        'S' => ['name' => 'Siang', 'start' => '14:00:00', 'end' => '22:00:00'],
        'M' => ['name' => 'Malam', 'start' => '22:00:00', 'end' => '06:00:00'],
    ];

    public function preview(Request $request, DinasRosterWorkbookParser $parser): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'extensions:xlsx', 'max:10240'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ], [
            'file.mimes' => 'File harus berupa workbook Excel .xlsx.',
            'file.extensions' => 'Ekstensi file harus .xlsx.',
            'file.max' => 'Ukuran workbook maksimal 10 MB.',
        ]);

        try {
            $preview = $parser->parse(
                (string) $request->file('file')->getRealPath(),
                (int) $validated['month'],
                (int) $validated['year'],
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => 'Workbook belum dapat dipratinjau.',
                'errors' => [$exception->getMessage()],
            ], 422);
        }

        $shiftConfiguration = $this->shiftConfiguration();

        $jpls = Jpl::query()
            ->orderBy('kode_jpl')
            ->get(['id', 'kode_jpl', 'nama_jpl', 'is_active']);

        $accountsByNip = User::query()
            ->with('role:id,name')
            ->get(['id', 'nip', 'name', 'role_id', 'is_active'])
            ->groupBy(fn (User $user): string => $this->normalizeNip((string) $user->nip));

        $workbookNipCounts = collect($preview['staff'])
            ->map(fn (array $staff): string => $this->normalizeNip($staff['nipkwt']))
            ->filter()
            ->countBy();

        $allValidationErrors = [];

        foreach ($preview['staff'] as &$staff) {
            $normalizedNip = $this->normalizeNip($staff['nipkwt']);

            $matches = $normalizedNip === ''
                ? collect()
                : $accountsByNip->get($normalizedNip, collect());

            $staffValidationErrors = $staff['errors'];

            if ($matches->count() > 1) {
                $staff['account'] = [
                    'status' => 'ambigu',
                    'label' => 'Ambigu',
                    'detail' => 'Lebih dari satu akun memiliki NIP yang sama setelah normalisasi.',
                    'name' => null,
                    'nip' => null,
                    'id' => null,
                ];
            } elseif ($matches->isEmpty()) {
                $staff['account'] = [
                    'status' => 'tidak_cocok',
                    'label' => 'Tidak cocok',
                    'detail' => 'Akun dengan NIP yang cocok tidak ditemukan.',
                    'name' => null,
                    'nip' => null,
                    'id' => null,
                ];
            } else {
                /** @var User $account */
                $account = $matches->first();

                $isEligible = $account->is_active
                    && $account->role?->name === 'pjl';

                $staff['account'] = [
                    'status' => $isEligible ? 'cocok' : 'tidak_cocok',
                    'label' => $isEligible ? 'Cocok' : 'Tidak cocok',
                    'detail' => $isEligible
                        ? 'NIP cocok dengan akun PJL aktif.'
                        : 'NIP ditemukan, tetapi akun tidak aktif atau bukan role PJL.',
                    'name' => $account->name,
                    'nip' => $account->nip,
                    'id' => (int) $account->id,
                ];
            }

            if ($staff['account']['status'] !== 'cocok') {
                $staffValidationErrors[] = "Baris {$staff['workbook_row']}: {$staff['account']['detail']}";
            }

            if (($workbookNipCounts->get($normalizedNip, 0)) > 1) {
                $staffValidationErrors[] = "Baris {$staff['workbook_row']}: NIPKWT duplikat pada workbook; setiap petugas hanya boleh muncul satu kali.";
            }

            $staff['assignment'] = $this->resolveAssignment(
                $staff['job_title'] ?? null,
                $jpls
            );

            if ($staff['assignment']['status'] !== 'cocok') {
                $staffValidationErrors[] = "Baris {$staff['workbook_row']}: {$staff['assignment']['detail']}";
            }

            foreach ($staff['days'] as &$day) {
                $day['shift_status'] = $day['valid'] && $day['code'] !== 'L'
                    ? ($shiftConfiguration[$day['code']]['status'] ?? 'kode_tidak_valid')
                    : ($day['code'] === 'L' && $day['valid'] ? 'libur' : 'kode_tidak_valid');

                if ($day['valid'] && $day['code'] !== 'L' && $day['shift_status'] !== 'cocok') {
                    $staffValidationErrors[] = "Baris {$staff['workbook_row']}, tanggal {$day['day']}: master shift {$day['code']} belum sesuai dengan jam roster.";
                }
            }

            unset($day);

            $staff['validation_errors'] = array_values(array_unique($staffValidationErrors));

            array_push($allValidationErrors, ...$staff['validation_errors']);
        }

        unset($staff);

        $allValidationErrors = array_values(array_unique($allValidationErrors));

        $invalidStaffCount = collect($preview['staff'])
            ->filter(fn (array $staff): bool => $staff['validation_errors'] !== [])
            ->count();

        $storageBlockers = $allValidationErrors === []
            ? $this->storageBlockers($preview)
            : [];

        return response()->json([
            'preview' => $preview,
            'shiftConfiguration' => $shiftConfiguration,
            'validation' => [
                'is_valid' => $allValidationErrors === [],
                'errors' => $allValidationErrors,
                'staff_count' => count($preview['staff']),
                'valid_staff_count' => count($preview['staff']) - $invalidStaffCount,
                'invalid_staff_count' => $invalidStaffCount,
                'can_store' => $allValidationErrors === [] && $storageBlockers === [],
                'storage_blockers' => $storageBlockers,
            ],
        ]);
    }

    /**
     * @param Collection<int, Jpl> $jpls
     * @return array{
     *     status: string,
     *     label: string,
     *     detail: string,
     *     jpl: ?array{id: int, code: string, name: string}
     * }
     */
    private function resolveAssignment(?string $jobTitle, EloquentCollection $jpls): array
    {
        $number = $this->jplNumber($jobTitle);

        if ($number === null) {
            return [
                'status' => 'tidak_cocok',
                'label' => 'Jabatan tidak dikenali',
                'detail' => $jobTitle === null || trim($jobTitle) === ''
                    ? 'Kolom Jabatan kosong.'
                    : 'Jabatan harus mencantumkan kode JPL yang dikenali, misalnya JPL 351.',
                'jpl' => null,
            ];
        }

        $matches = $jpls->filter(
            fn (Jpl $jpl): bool => $this->jplNumber($jpl->kode_jpl) === $number
        );

        if ($matches->count() > 1) {
            return [
                'status' => 'ambigu',
                'label' => 'Kode JPL ambigu',
                'detail' => "Lebih dari satu JPL cocok dengan kode {$number}.",
                'jpl' => null,
            ];
        }

        if ($matches->isEmpty()) {
            return [
                'status' => 'tidak_cocok',
                'label' => 'JPL tidak ditemukan',
                'detail' => "Jabatan menunjukkan JPL {$number}, tetapi kode tersebut tidak ditemukan pada master JPL.",
                'jpl' => null,
            ];
        }

        /** @var Jpl $jpl */
        $jpl = $matches->first();

        if (! $jpl->is_active) {
            return [
                'status' => 'tidak_aktif',
                'label' => 'JPL tidak aktif',
                'detail' => "JPL {$number} ditemukan, tetapi statusnya tidak aktif.",
                'jpl' => null,
            ];
        }

        return [
            'status' => 'cocok',
            'label' => 'JPL cocok',
            'detail' => 'JPL dipetakan dari kolom Jabatan workbook.',
            'jpl' => [
                'id' => (int) $jpl->id,
                'code' => $jpl->kode_jpl,
                'name' => $jpl->nama_jpl,
            ],
        ];
    }

    private function jplNumber(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (preg_match('/^\s*(?:JPL[\s-]*)?(\d+)\s*$/i', $value, $matches) !== 1) {
            return null;
        }

        return ltrim($matches[1], '0') ?: '0';
    }

    /**
     * @return array<string, array{
     *     name: string,
     *     start: string,
     *     end: string,
     *     status: string,
     *     actual_start: ?string,
     *     actual_end: ?string
     * }>
     */
    private function shiftConfiguration(): array
    {
        $shifts = Shift::query()->get([
            'id',
            'nama_shift',
            'jam_mulai',
            'jam_selesai',
        ]);

        $configuration = [];

        foreach (self::SHIFT_CODES as $code => $expected) {
            $matches = $shifts->filter(
                fn (Shift $shift): bool =>
                    mb_strtolower(trim($shift->nama_shift)) === mb_strtolower($expected['name'])
            );

            $match = $matches->count() === 1
                ? $matches->first()
                : null;

            $timesMatch = $match !== null
                && substr((string) $match->jam_mulai, 0, 8) === $expected['start']
                && substr((string) $match->jam_selesai, 0, 8) === $expected['end'];

            $configuration[$code] = [
                ...$expected,
                'shift_id' => $match === null ? null : (int) $match->id,
                'status' => $matches->count() > 1
                    ? 'ambigu'
                    : ($match === null ? 'tidak_ditemukan' : ($timesMatch ? 'cocok' : 'jam_tidak_cocok')),
                'actual_start' => $match?->jam_mulai === null
                    ? null
                    : substr((string) $match->jam_mulai, 0, 8),
                'actual_end' => $match?->jam_selesai === null
                    ? null
                    : substr((string) $match->jam_selesai, 0, 8),
            ];
        }

        return $configuration;
    }

    public function confirm(
        Request $request,
        DinasRosterWorkbookParser $parser,
        DinasJadwalSnapshotService $snapshotService,
        ActivityLogService $activityLogService
    ): JsonResponse {
        $previewResponse = $this->preview($request, $parser);
        $previewData = $previewResponse->getData(true);

        if ($previewResponse->getStatusCode() !== 200) {
            return response()->json($previewData, $previewResponse->getStatusCode());
        }

        if (! ($previewData['validation']['is_valid'] ?? false)) {
            return response()->json([
                'message' => 'Seluruh roster harus valid. Tidak ada data yang disimpan.',
                'errors' => $previewData['validation']['errors'],
            ], 422);
        }

        if (! ($previewData['validation']['can_store'] ?? false)) {
            return response()->json([
                'message' => 'Penyimpanan roster diblokir. Selesaikan seluruh persyaratan penyimpanan terlebih dahulu.',
                'errors' => $previewData['validation']['storage_blockers'],
            ], 409);
        }

        $preview = $previewData['preview'];

        $holidayRows = [];
        $scheduleRows = [];

        foreach ($preview['staff'] as $staff) {
            foreach ($staff['days'] as $day) {
                if ($day['code'] === 'L') {
                    $holidayRows[] = [
                        'user_id' => $staff['account']['id'],
                        'jpl_id' => $staff['assignment']['jpl']['id'],
                        'tanggal_dinas' => $day['date'],
                    ];

                    continue;
                }

                $shift = $previewData['shiftConfiguration'][$day['code']];

                $scheduleRows[] = [
                    'user_id' => $staff['account']['id'],
                    'nipkwt' => $staff['nipkwt'],
                    'job_title' => $staff['job_title'],
                    'jpl_id' => $staff['assignment']['jpl']['id'],
                    'jpl_code' => $staff['assignment']['jpl']['code'],
                    'shift_id' => $shift['shift_id'],
                    'shift_code' => $day['code'],
                    'tanggal_dinas' => $day['date'],
                    'workbook_row' => $staff['workbook_row'],
                ];
            }
        }

        $startDate = CarbonImmutable::createSafe(
            $preview['year'],
            $preview['month'],
            1
        )->toDateString();

        $endDate = CarbonImmutable::createSafe(
            $preview['year'],
            $preview['month'],
            $preview['days_in_month']
        )->toDateString();

        $userIds = collect($preview['staff'])
            ->pluck('account.id')
            ->unique()
            ->sort()
            ->values();

        $jplIds = collect($preview['staff'])
            ->pluck('assignment.jpl.id')
            ->unique()
            ->sort()
            ->values();

        try {
            $importResult = DB::transaction(function () use (
                $startDate,
                $endDate,
                $userIds,
                $jplIds,
                $scheduleRows,
                $holidayRows,
                $preview,
                $snapshotService,
                $activityLogService
            ): array {
                /*
                 * FILTER SUMBER JADWAL KA
                 *
                 * Jadwal sebelum hari ini hanya disimpan jika
                 * sumber Jadwal KA historisnya tersedia.
                 *
                 * Jika tidak tersedia, penugasan pada tanggal
                 * tersebut dilewati, bukan membatalkan impor.
                 */
                $availableScheduleRows = [];
                $skippedScheduleCount = 0;
                $sourceCache = [];

                foreach ($scheduleRows as $row) {
                    $date = $row['tanggal_dinas'];

                    $isHistorical = CarbonImmutable::parse($date)
                        ->isBefore(today());

                    if (! $isHistorical) {
                        $availableScheduleRows[] = $row;
                        continue;
                    }

                    $sourceKey = $row['jpl_id'].'|'.$date;

                    if (! array_key_exists($sourceKey, $sourceCache)) {
                        $sourceCache[$sourceKey] = $this->hasHistoricalScheduleSource(
                            (int) $row['jpl_id'],
                            $date
                        );
                    }

                    if (! $sourceCache[$sourceKey]) {
                        $skippedScheduleCount++;
                        continue;
                    }

                    $availableScheduleRows[] = $row;
                }

                /*
                 * Hanya jadwal yang memiliki sumber valid
                 * yang diteruskan ke proses penyimpanan.
                 */
                $scheduleRows = $availableScheduleRows;
                $importedScheduleCount = count($scheduleRows);

                $lockedUsers = User::query()
                    ->whereIn('id', $userIds)
                    ->where('is_active', true)
                    ->whereHas('role', fn ($query) => $query->where('name', 'pjl'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $lockedJpls = Jpl::query()
                    ->whereIn('id', $jplIds)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $requiredShiftIds = collect($scheduleRows)
                    ->pluck('shift_id')
                    ->filter()
                    ->unique();

                $lockedShifts = Shift::query()
                    ->whereIn('id', $requiredShiftIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if (
                    $lockedUsers->count() !== $userIds->count()
                    || $lockedJpls->count() !== $jplIds->count()
                ) {
                    throw new InvalidArgumentException(
                        'Identitas petugas atau JPL berubah setelah preview. Unggah ulang workbook.'
                    );
                }

                $allLockedJpls = Jpl::query()
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id', 'kode_jpl', 'nama_jpl', 'is_active']);

                foreach ($preview['staff'] as $staff) {
                    $number = $this->jplNumber($staff['job_title'] ?? null);

                    $candidates = $number === null
                        ? collect()
                        : $allLockedJpls->filter(
                            fn (Jpl $jpl): bool =>
                                $this->jplNumber($jpl->kode_jpl) === $number
                        );

                    $assignment = $staff['assignment']['jpl'];

                    if (
                        $candidates->count() !== 1
                        || ! $candidates->first()?->is_active
                        || (int) $candidates->first()->id !== (int) $assignment['id']
                    ) {
                        throw new InvalidArgumentException(
                            "Baris {$staff['workbook_row']}: pemetaan JPL berubah atau menjadi ambigu setelah preview. Seluruh impor dibatalkan."
                        );
                    }

                    $account = $lockedUsers->firstWhere(
                        'id',
                        $staff['account']['id']
                    );

                    if (
                        ! $account
                        || $this->normalizeNip((string) $account->nip)
                            !== $this->normalizeNip($staff['nipkwt'])
                    ) {
                        throw new InvalidArgumentException(
                            "Baris {$staff['workbook_row']}: akun tidak lagi cocok dengan NIPKWT workbook. Seluruh impor dibatalkan."
                        );
                    }
                }

                if (
                    $lockedShifts->count() !== $requiredShiftIds->count()
                ) {
                    throw new InvalidArgumentException(
                        'Master shift berubah setelah preview. Unggah ulang workbook.'
                    );
                }

                foreach ($scheduleRows as $row) {
                    $expectedShift = self::SHIFT_CODES[$row['shift_code']];

                    $shift = $lockedShifts->firstWhere('id', $row['shift_id']);

                    if (
                        ! $shift
                        || mb_strtolower(trim($shift->nama_shift))
                            !== mb_strtolower($expectedShift['name'])
                        || substr((string) $shift->jam_mulai, 0, 8)
                            !== $expectedShift['start']
                        || substr((string) $shift->jam_selesai, 0, 8)
                            !== $expectedShift['end']
                    ) {
                        throw new InvalidArgumentException(
                            "Baris {$row['workbook_row']}: jam master shift berubah setelah preview. Seluruh impor dibatalkan."
                        );
                    }
                }

                $hasExistingDinas = Dinas::query()
                    ->whereIn('user_id', $userIds)
                    ->whereBetween('tanggal_dinas', [$startDate, $endDate])
                    ->lockForUpdate()
                    ->exists();

                $hasExistingHoliday = DB::table('jadwal_dinas_libur')
                    ->whereIn('user_id', $userIds)
                    ->whereBetween('tanggal_dinas', [$startDate, $endDate])
                    ->lockForUpdate()
                    ->exists();

                if ($hasExistingDinas || $hasExistingHoliday) {
                    throw new InvalidArgumentException(
                        'Periode tujuan sudah memiliki jadwal atau hari libur. Impor dibatalkan tanpa menimpa data.'
                    );
                }

                $intervals = [];

                foreach ($scheduleRows as $row) {
                    $shift = $lockedShifts->firstWhere('id', $row['shift_id']);

                    $start = CarbonImmutable::parse(
                        $row['tanggal_dinas'].' '.$shift->jam_mulai
                    );

                    $end = CarbonImmutable::parse(
                        $row['tanggal_dinas'].' '.$shift->jam_selesai
                    );

                    if ($end->lessThanOrEqualTo($start)) {
                        $end = $end->addDay();
                    }

                    $conflict = Dinas::query()
                        ->where('user_id', $row['user_id'])
                        ->where('jam_mulai', '<', $end)
                        ->where('jam_selesai', '>', $start)
                        ->exists();

                    foreach ($intervals as $interval) {
                        if (
                            $interval['user_id'] === $row['user_id']
                            && $interval['start']->lessThan($end)
                            && $interval['end']->greaterThan($start)
                        ) {
                            $conflict = true;
                            break;
                        }
                    }

                    if ($conflict) {
                        throw new InvalidArgumentException(
                            "Jadwal petugas pada {$row['tanggal_dinas']} berbenturan dengan Dinas yang sudah ada."
                        );
                    }

                    $intervals[] = [
                        'user_id' => $row['user_id'],
                        'start' => $start,
                        'end' => $end,
                    ];

                    $dinas = Dinas::query()->create([
                        'user_id' => $row['user_id'],
                        'jpl_id' => $row['jpl_id'],
                        'shift_id' => $row['shift_id'],
                        'tanggal_dinas' => $row['tanggal_dinas'],
                        'jam_mulai' => $start,
                        'jam_selesai' => $end,
                        'status' => $this->initialStatus($start, $end),
                        'is_pengganti' => false,
                    ]);

                    $snapshotService->resolveAndStore((int) $dinas->id);
                }

                if ($holidayRows !== []) {
                    $now = now();

                    DB::table('jadwal_dinas_libur')->insert(
                        array_map(
                            fn (array $row): array => [
                                ...$row,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ],
                            $holidayRows
                        )
                    );
                }

                $activityLogService->log(
                    'import',
                    'dinas',
                    'Impor roster bulanan berhasil diproses.',
                    null,
                    null,
                    [
                        'period_start' => $startDate,
                        'period_end' => $endDate,
                        'schedule_count' => $importedScheduleCount,
                        'skipped_schedule_count' => $skippedScheduleCount,
                        'holiday_count' => count($holidayRows),
                    ],
                );

                return [
                    'imported_count' => $importedScheduleCount,
                    'skipped_count' => $skippedScheduleCount,
                    'holiday_count' => count($holidayRows),
                ];
            }, 3);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [$exception->getMessage()],
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Impor roster gagal. Tidak ada data yang disimpan.',
            ], 500);
        }

        $message = "{$importResult['imported_count']} jadwal dan {$importResult['holiday_count']} hari libur berhasil disimpan.";

        if ($importResult['skipped_count'] > 0) {
            $message .= " {$importResult['skipped_count']} jadwal dilewati karena sumber Jadwal KA historis tidak tersedia.";
        }

        return response()->json([
            'message' => $message,
            'imported_count' => $importResult['imported_count'],
            'skipped_count' => $importResult['skipped_count'],
            'holiday_count' => $importResult['holiday_count'],
        ]);
    }

    /**
     * Memeriksa apakah JPL memiliki sumber Jadwal KA
     * yang berlaku pada tanggal tertentu.
     */
    private function hasHistoricalScheduleSource(int $jplId, string $date): bool
    {
        return JadwalKaBatch::query()
            ->whereIn('status', ['scheduled', 'active', 'archived'])
            ->whereDate('tanggal_mulai_berlaku', '<=', $date)
            ->whereHas('jpls', function ($query) use ($jplId, $date): void {
                $query->where('jpl.id', $jplId)
                    ->where(function ($pivot) use ($date): void {
                        $pivot->whereNull('jadwal_ka_batch_jpl.tanggal_akhir_berlaku')
                            ->orWhereDate(
                                'jadwal_ka_batch_jpl.tanggal_akhir_berlaku',
                                '>=',
                                $date
                            );
                    });
            })
            ->whereHas(
                'jadwalKa',
                fn ($query) => $query->where('jpl_id', $jplId)
            )
            ->lockForUpdate()
            ->exists();
    }

    /**
     * Memeriksa persyaratan penyimpanan selain ketersediaan
     * sumber Jadwal KA historis per tanggal.
     *
     * Kekurangan sumber Jadwal KA ditangani dengan melewati
     * penugasan tanggal tersebut di dalam confirm().
     *
     * @param array{
     *     month: int,
     *     year: int,
     *     days_in_month: int,
     *     staff: list<array<string, mixed>>
     * } $preview
     * @return list<string>
     */
    private function storageBlockers(array $preview): array
    {
        $blockers = [];

        if (! Schema::hasTable('jadwal_dinas_libur')) {
            $blockers[] = 'Migration jadwal_dinas_libur belum dijalankan; data libur tidak dapat disimpan.';
        }

        $userIds = [];

        foreach ($preview['staff'] as $staff) {
            $userIds[] = $staff['account']['id'] ?? null;
        }

        $userIds = array_values(array_unique(array_filter($userIds)));

        if ($userIds !== []) {
            $startDate = CarbonImmutable::createSafe(
                $preview['year'],
                $preview['month'],
                1
            )->toDateString();

            $endDate = CarbonImmutable::createSafe(
                $preview['year'],
                $preview['month'],
                $preview['days_in_month']
            )->toDateString();

            if (
                Dinas::query()
                    ->whereIn('user_id', $userIds)
                    ->whereBetween('tanggal_dinas', [$startDate, $endDate])
                    ->exists()
            ) {
                $blockers[] = 'Periode tujuan sudah memiliki jadwal Dinas untuk satu atau lebih petugas; sistem tidak akan menimpa data.';
            }

            if (
                Schema::hasTable('jadwal_dinas_libur')
                && DB::table('jadwal_dinas_libur')
                    ->whereIn('user_id', $userIds)
                    ->whereBetween('tanggal_dinas', [$startDate, $endDate])
                    ->exists()
            ) {
                $blockers[] = 'Periode tujuan sudah memiliki data libur untuk satu atau lebih petugas; sistem tidak akan menimpa data.';
            }
        }

        return array_values(array_unique($blockers));
    }

    private function initialStatus(
        CarbonImmutable $start,
        CarbonImmutable $end
    ): string {
        if ($end->lessThanOrEqualTo(now())) {
            return 'selesai';
        }

        return $start->lessThanOrEqualTo(now())
            ? 'berlangsung'
            : 'dijadwalkan';
    }

    private function normalizeNip(string $nip): string
    {
        return preg_replace(
            '/[^A-Z0-9]/',
            '',
            mb_strtoupper(trim($nip))
        ) ?? '';
    }
}
