<?php

namespace App\Services;

use App\Models\Dinas;
use App\Models\JadwalKa;
use App\Models\JadwalKaBatch;
use App\Models\Jpl;
use App\Models\Shift;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class DinasJadwalSnapshotService
{
    /**
     * Return a Dinas snapshot, creating it once when it has not been captured.
     *
     * @return array<string, mixed>
     */
    public function resolveAndStore(int $dinasId): array
    {
        // This unlocked read determines the required JPL-first lock order.
        $initialDinas = Dinas::query()->findOrFail($dinasId);
        $initialContext = [
            'jpl_id' => (int) $initialDinas->jpl_id,
            'shift_id' => $initialDinas->shift_id === null ? null : (int) $initialDinas->shift_id,
            'tanggal_dinas' => CarbonImmutable::parse($initialDinas->tanggal_dinas)->toDateString(),
        ];

        return DB::transaction(function () use ($dinasId, $initialContext): array {
            $jpl = Jpl::query()
                ->whereKey($initialContext['jpl_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $dinas = Dinas::query()
                ->whereKey($dinasId)
                ->lockForUpdate()
                ->firstOrFail();

            $currentContext = [
                'jpl_id' => (int) $dinas->jpl_id,
                'shift_id' => $dinas->shift_id === null ? null : (int) $dinas->shift_id,
                'tanggal_dinas' => CarbonImmutable::parse($dinas->tanggal_dinas)->toDateString(),
            ];

            if ($currentContext !== $initialContext) {
                throw new LogicException(
                    'Konteks Dinas berubah saat snapshot Jadwal KA sedang dibuat. Silakan ulangi proses.'
                );
            }

            if ($dinas->jadwal_ka_snapshot !== null) {
                return $this->assertSnapshotContext($dinas->jadwal_ka_snapshot, [
                    'dinas_id' => (int) $dinas->id,
                    'jpl_id' => (int) $dinas->jpl_id,
                    'shift_id' => $dinas->shift_id === null ? null : (int) $dinas->shift_id,
                    'jadwal_ka_batch_id' => $dinas->jadwal_ka_batch_id === null
                        ? null
                        : (int) $dinas->jadwal_ka_batch_id,
                    'tanggal_dinas' => CarbonImmutable::parse($dinas->tanggal_dinas)->toDateString(),
                ]);
            }

            $shift = $dinas->shift_id === null
                ? null
                : Shift::query()->whereKey($dinas->shift_id)->lockForUpdate()->first();

            $dinasDate = CarbonImmutable::parse($dinas->tanggal_dinas)->startOfDay();
            [$batch, $scopes] = $this->lockBatchVersionsForJpl($jpl->id);
            $sourceBatch = $this->resolveApplicableBatch($batch, $scopes, $jpl->id, $dinasDate);

            if ($sourceBatch !== null) {
                $source = 'batch';
                $batchId = (int) $sourceBatch->id;
                $schedules = $this->schedulesForBatch($batchId, $jpl->id);
            } else {
                $source = 'legacy';
                $batchId = null;
                $schedules = $this->legacySchedules($jpl->id);
            }

            $matchingSchedules = $this->filterSchedulesForDinas(
                $schedules,
                $dinasDate,
                $shift,
                $sourceBatch !== null
            )->values();

            $snapshot = [
                'format_version' => 1,
                'snapshot_at' => now()->toIso8601String(),
                'dinas_id' => (int) $dinas->id,
                'jpl_id' => (int) $jpl->id,
                'shift_id' => $dinas->shift_id === null ? null : (int) $dinas->shift_id,
                'tanggal_dinas' => $dinasDate->toDateString(),
                'jadwal_ka_batch_id' => $batchId,
                'source' => $source,
                'jadwal' => $matchingSchedules
                    ->map(fn (JadwalKa $schedule): array => $this->serializeSchedule($schedule, $jpl))
                    ->all(),
            ];

            $dinas->jadwal_ka_batch_id = $batchId;
            $dinas->jadwal_ka_snapshot = $snapshot;
            $dinas->save();

            return $snapshot;
        }, 3);
    }

    /**
     * @param  array{dinas_id: int, jpl_id: int, shift_id: ?int, jadwal_ka_batch_id: ?int, tanggal_dinas: string}  $context
     * @return array<string, mixed>
     */
    private function assertSnapshotContext(mixed $snapshot, array $context): array
    {
        if (! is_array($snapshot)) {
            throw new LogicException('Snapshot Jadwal KA tersimpan memiliki format yang tidak valid.');
        }

        foreach ($context as $key => $value) {
            if (! array_key_exists($key, $snapshot) || $snapshot[$key] !== $value) {
                throw new LogicException(
                    'Konteks Dinas tidak sesuai dengan snapshot Jadwal KA yang sudah tersimpan. Snapshot tidak diubah.'
                );
            }
        }

        if (($snapshot['format_version'] ?? null) !== 1) {
            throw new LogicException('Versi format snapshot Jadwal KA tidak valid.');
        }

        if (! array_key_exists('jadwal', $snapshot) || ! is_array($snapshot['jadwal'])) {
            throw new LogicException('Daftar jadwal pada snapshot Jadwal KA tidak valid.');
        }

        if (! in_array($snapshot['source'] ?? null, ['batch', 'legacy'], true)) {
            throw new LogicException('Sumber snapshot Jadwal KA tidak valid.');
        }

        if (! $this->isValidIsoTimestamp($snapshot['snapshot_at'] ?? null)) {
            throw new LogicException('Waktu pembuatan snapshot Jadwal KA tidak valid.');
        }

        if (! array_key_exists('jadwal_ka_batch_id', $snapshot)) {
            throw new LogicException('ID batch pada snapshot Jadwal KA tidak tersedia.');
        }

        $batchId = $snapshot['jadwal_ka_batch_id'];

        if ($snapshot['source'] === 'batch' && (! is_int($batchId) || $batchId < 1)) {
            throw new LogicException('Snapshot dengan sumber batch harus memiliki ID batch positif.');
        }

        if ($snapshot['source'] === 'legacy' && $batchId !== null) {
            throw new LogicException('Snapshot legacy tidak boleh memiliki ID batch.');
        }

        return $snapshot;
    }

    private function isValidIsoTimestamp(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $dateTime = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $value);
        $errors = \DateTimeImmutable::getLastErrors();

        return $dateTime !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $dateTime->format(\DateTimeInterface::ATOM) === $value;
    }

    /**
     * Lock in the same JPL -> batch -> pivot order used by JadwalKaVersionService.
     *
     * @return array{0: EloquentCollection<int, JadwalKaBatch>, 1: Collection<int, object>}
     */
    private function lockBatchVersionsForJpl(int $jplId): array
    {
        $batches = JadwalKaBatch::query()
            ->whereHas('jpls', fn ($query) => $query->where('jpl.id', $jplId))
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $scopes = $batches->isEmpty()
            ? collect()
            : DB::table('jadwal_ka_batch_jpl')
                ->whereIn('jadwal_ka_batch_id', $batches->modelKeys())
                ->orderBy('jpl_id')
                ->orderBy('jadwal_ka_batch_id')
                ->lockForUpdate()
                ->get();

        return [$batches, $scopes];
    }

    /**
     * Resolve exactly one non-draft batch whose pivot range covers the Dinas date.
     *
     * @param  EloquentCollection<int, JadwalKaBatch>  $batches
     * @param  Collection<int, object>  $scopes
     */
    private function resolveApplicableBatch(
        EloquentCollection $batches,
        Collection $scopes,
        int $jplId,
        CarbonImmutable $dinasDate
    ): ?JadwalKaBatch {
        $batchesById = $batches->keyBy('id');
        $date = $dinasDate->toDateString();
        $today = CarbonImmutable::today()->toDateString();
        $applicable = [];
        $pendingForFutureDinas = false;

        foreach ($scopes->where('jpl_id', $jplId) as $scope) {
            $batch = $batchesById->get((int) $scope->jadwal_ka_batch_id);

            if (! $batch || $batch->status === 'draft') {
                continue;
            }

            if (! in_array($batch->status, ['scheduled', 'active', 'archived'], true)) {
                throw new LogicException('Status batch Jadwal KA tidak dikenali untuk JPL ini.');
            }

            if ($batch->tanggal_mulai_berlaku === null) {
                throw new LogicException(
                    "Batch Jadwal KA {$batch->id} berstatus {$batch->status} tidak memiliki tanggal mulai berlaku."
                );
            }

            $start = CarbonImmutable::parse($batch->tanggal_mulai_berlaku)->toDateString();
            $end = $scope->tanggal_akhir_berlaku === null
                ? null
                : CarbonImmutable::parse($scope->tanggal_akhir_berlaku)->toDateString();

            if ($end !== null && $end < $start) {
                throw new LogicException('Rentang versi Jadwal KA memiliki tanggal akhir sebelum tanggal mulai.');
            }

            if ($start > $date || ($end !== null && $end < $date)) {
                continue;
            }

            if ($batch->status === 'scheduled' && $start > $today) {
                $pendingForFutureDinas = true;

                continue;
            }

            $applicable[] = $batch;
        }

        if (count($applicable) > 1) {
            throw new LogicException(
                "Rentang versi Jadwal KA tumpang tindih untuk JPL {$jplId} pada tanggal Dinas {$date}."
            );
        }

        if ($applicable !== []) {
            return $applicable[0];
        }

        if ($pendingForFutureDinas) {
            throw new LogicException(
                'Versi Jadwal KA untuk tanggal Dinas ini masih terjadwal dan belum mencapai tanggal efektif.'
            );
        }

        return null;
    }

    /**
     * @return EloquentCollection<int, JadwalKa>
     */
    private function schedulesForBatch(int $batchId, int $jplId): EloquentCollection
    {
        return JadwalKa::query()
            ->with('keretaApi')
            ->where('jadwal_ka_batch_id', $batchId)
            ->where('jpl_id', $jplId)
            ->orderByRaw('CASE WHEN tanggal_berlaku IS NULL THEN 1 ELSE 0 END')
            ->orderBy('jam_lewat')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * Legacy rows are trusted only while marked active because their former
     * effective ranges cannot be reconstructed from the available history.
     *
     * @return EloquentCollection<int, JadwalKa>
     */
    private function legacySchedules(int $jplId): EloquentCollection
    {
        return JadwalKa::query()
            ->with('keretaApi')
            ->whereNull('jadwal_ka_batch_id')
            ->where('jpl_id', $jplId)
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN tanggal_berlaku IS NULL THEN 1 ELSE 0 END')
            ->orderBy('jam_lewat')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * Apply date/day conditions to legacy schedules and shift conditions to all schedules.
     *
     * @param  EloquentCollection<int, JadwalKa>  $schedules
     * @return Collection<int, JadwalKa>
     */
    private function filterSchedulesForDinas(
        EloquentCollection $schedules,
        CarbonImmutable $dinasDate,
        ?Shift $shift,
        bool $isBatchSchedule
    ): Collection {
        $dayNames = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        $dayName = $dayNames[$dinasDate->dayOfWeekIso];

        return $schedules->filter(function (JadwalKa $schedule) use ($dinasDate, $dayName, $shift, $isBatchSchedule): bool {
            $specificDateMatches = $schedule->tanggal_berlaku !== null
                && CarbonImmutable::parse($schedule->tanggal_berlaku)->toDateString() === $dinasDate->toDateString();
            $recurringDayMatches = $schedule->tanggal_berlaku === null
                && is_array($schedule->hari_berlaku)
                && (in_array($dayName, $schedule->hari_berlaku, true)
                    || in_array('Setiap Hari', $schedule->hari_berlaku, true));

            return ($isBatchSchedule || $specificDateMatches || $recurringDayMatches)
                && $this->isWithinShift($schedule->jam_lewat, $shift);
        });
    }

    private function isWithinShift(?string $time, ?Shift $shift): bool
    {
        if ($time === null || $shift?->jam_mulai === null || $shift->jam_selesai === null) {
            return false;
        }

        $trainMinutes = $this->timeToMinutes($time);
        $startMinutes = $this->timeToMinutes((string) $shift->jam_mulai);
        $endMinutes = $this->timeToMinutes((string) $shift->jam_selesai);

        if ($trainMinutes === null || $startMinutes === null || $endMinutes === null) {
            return false;
        }

        if ($startMinutes <= $endMinutes) {
            return $trainMinutes >= $startMinutes && $trainMinutes <= $endMinutes;
        }

        return $trainMinutes >= $startMinutes || $trainMinutes <= $endMinutes;
    }

    private function timeToMinutes(string $time): ?int
    {
        $parts = explode(':', $time);

        if (count($parts) < 2 || ! is_numeric($parts[0]) || ! is_numeric($parts[1])) {
            return null;
        }

        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];

        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return null;
        }

        return ($hours * 60) + $minutes;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSchedule(JadwalKa $schedule, Jpl $jpl): array
    {
        return [
            'jadwal_ka_id' => (int) $schedule->id,
            'kereta_api_id' => $schedule->kereta_api_id === null ? null : (int) $schedule->kereta_api_id,
            'nomor_ka' => $schedule->keretaApi?->nomor_ka,
            'nama_ka' => $schedule->keretaApi?->nama_ka,
            'jam_lewat' => $schedule->jam_lewat,
            'dari' => $schedule->dari,
            'ke' => $schedule->ke,
            'jpl_id' => (int) $jpl->id,
            'jpl_kode' => $jpl->kode_jpl,
            'jpl_nama' => $jpl->nama_jpl,
            'tanggal_berlaku' => $schedule->tanggal_berlaku?->toDateString(),
            'hari_berlaku' => $schedule->hari_berlaku,
        ];
    }
}
