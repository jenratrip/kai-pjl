<?php

namespace App\Services;

use App\Models\JadwalKa;
use App\Models\JadwalKaBatch;
use App\Models\Jpl;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class JadwalKaVersionService
{
    /**
     * Schedule a draft batch and update its per-JPL effective date ranges.
     */
    public function scheduleDraftBatch(int $batchId, CarbonImmutable $effectiveDate): JadwalKaBatch
    {
        $today = CarbonImmutable::today();

        if ($effectiveDate->startOfDay()->lt($today)) {
            throw new LogicException('Tanggal mulai berlaku tidak boleh sebelum hari ini.');
        }

        $jplIds = $this->jplIdsForBatch($batchId);

        if ($jplIds === []) {
            throw new LogicException('Batch tidak memiliki JPL yang tercakup.');
        }

        return DB::transaction(function () use ($batchId, $effectiveDate, $jplIds, $today): JadwalKaBatch {
            $this->lockJpls($jplIds);
            $batches = $this->lockBatchesForJpls($jplIds, $batchId);
            $allScopes = $this->lockScopesForBatches($batches->modelKeys());
            $scopes = $allScopes->whereIn('jpl_id', $jplIds)->values();
            $batch = $batches->firstWhere('id', $batchId);

            if (! $batch || $batch->status !== 'draft') {
                throw new LogicException('Hanya batch draft yang dapat dijadwalkan atau diaktifkan.');
            }

            $this->assertBatchHasSchedules($batchId, $jplIds);
            $ranges = $this->buildRanges($batches, $scopes, $batchId, $effectiveDate);

            $batch->tanggal_mulai_berlaku = $effectiveDate->toDateString();
            $batch->status = $effectiveDate->isSameDay($today) ? 'active' : 'scheduled';
            $batch->save();

            $this->saveScopeEndDates($ranges);

            if ($batch->status === 'active') {
                $effectiveJplIds = $this->effectiveJplIds($ranges, $batchId, $today);
                $this->activateBatchForJpls($batchId, $effectiveJplIds);
                $this->archiveExpiredBatches($batches, $today, $batchId);
            }

            return $batch->fresh(['jpls']);
        }, 3);
    }

    /**
     * Return scheduled batches whose effective date has arrived.
     *
     * @return Collection<int, int>
     */
    public function dueScheduledBatchIds(): Collection
    {
        return JadwalKaBatch::query()
            ->where('status', 'scheduled')
            ->whereDate('tanggal_mulai_berlaku', '<=', CarbonImmutable::today()->toDateString())
            ->orderBy('tanggal_mulai_berlaku')
            ->orderBy('id')
            ->pluck('id');
    }

    /**
     * Activate one due scheduled batch. Repeated calls are harmless.
     */
    public function activateScheduledBatch(int $batchId): bool
    {
        $jplIds = $this->jplIdsForBatch($batchId);

        if ($jplIds === []) {
            return DB::transaction(function () use ($batchId): bool {
                $batch = JadwalKaBatch::query()->whereKey($batchId)->lockForUpdate()->first();

                if (! $batch || $batch->status !== 'scheduled' || $batch->tanggal_mulai_berlaku->isFuture()) {
                    return false;
                }

                throw new LogicException('Batch terjadwal tidak memiliki JPL yang tercakup.');
            }, 3);
        }

        $today = CarbonImmutable::today();

        return DB::transaction(function () use ($batchId, $jplIds, $today): bool {
            $this->lockJpls($jplIds);
            $batches = $this->lockBatchesForJpls($jplIds, $batchId);
            $allScopes = $this->lockScopesForBatches($batches->modelKeys());
            $scopes = $allScopes->whereIn('jpl_id', $jplIds)->values();
            $batch = $batches->firstWhere('id', $batchId);

            if (! $batch || $batch->status !== 'scheduled') {
                return false;
            }

            if (CarbonImmutable::parse($batch->tanggal_mulai_berlaku)->startOfDay()->gt($today)) {
                return false;
            }

            $ranges = $this->buildRanges($batches, $scopes);
            $this->saveScopeEndDates($ranges);
            $effectiveJplIds = $this->effectiveJplIds($ranges, $batchId, $today);

            if ($effectiveJplIds !== []) {
                $this->assertBatchHasSchedules($batchId, $effectiveJplIds);
                $this->activateBatchForJpls($batchId, $effectiveJplIds);
                $batch->status = 'active';
            } else {
                $batch->status = 'archived';
            }

            $batch->save();
            $this->archiveExpiredBatches($batches, $today, $batchId);

            return true;
        }, 3);
    }

    /**
     * @return array<int, int>
     */
    private function jplIdsForBatch(int $batchId): array
    {
        return DB::table('jadwal_ka_batch_jpl')
            ->where('jadwal_ka_batch_id', $batchId)
            ->orderBy('jpl_id')
            ->pluck('jpl_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, int>  $jplIds
     */
    private function lockJpls(array $jplIds): void
    {
        $lockedIds = Jpl::query()
            ->whereIn('id', $jplIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($lockedIds !== $jplIds) {
            throw new LogicException('Satu atau lebih JPL dalam batch tidak lagi tersedia.');
        }
    }

    /**
     * @param  array<int, int>  $jplIds
     * @return Collection<int, JadwalKaBatch>
     */
    private function lockBatchesForJpls(array $jplIds, int $batchId): Collection
    {
        return JadwalKaBatch::query()
            ->where(function ($query) use ($jplIds, $batchId): void {
                $query->whereKey($batchId)
                    ->orWhereHas('jpls', fn ($jpls) => $jpls->whereIn('jpl.id', $jplIds));
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  array<int, int>  $jplIds
     * @return Collection<int, object>
     */
    private function lockScopesForBatches(array $batchIds): Collection
    {
        return DB::table('jadwal_ka_batch_jpl')
            ->whereIn('jadwal_ka_batch_id', $batchIds)
            ->orderBy('jpl_id')
            ->orderBy('jadwal_ka_batch_id')
            ->lockForUpdate()
            ->get();
    }

    /**
     * @param  Collection<int, JadwalKaBatch>  $batches
     * @param  Collection<int, object>  $scopes
     * @return array<int, array<int, array{batch_id: int, start: string, end: ?string}>>
     */
    private function buildRanges(
        Collection $batches,
        Collection $scopes,
        ?int $overrideBatchId = null,
        ?CarbonImmutable $overrideStart = null
    ): array {
        $effectiveBatches = $batches->filter(function (JadwalKaBatch $batch) use ($overrideBatchId, $overrideStart): bool {
            $isDraftBeingScheduled = (int) $batch->id === $overrideBatchId
                && $overrideStart !== null;

            return $isDraftBeingScheduled
                || in_array($batch->status, ['scheduled', 'active', 'archived'], true);
        });

        $startsByBatch = $effectiveBatches->mapWithKeys(function (JadwalKaBatch $batch) use ($overrideBatchId, $overrideStart): array {
            $start = $batch->id === $overrideBatchId && $overrideStart
                ? $overrideStart->toDateString()
                : CarbonImmutable::parse($batch->tanggal_mulai_berlaku)->toDateString();

            return [$batch->id => $start];
        });
        $ranges = [];

        foreach ($scopes as $scope) {
            $batchId = (int) $scope->jadwal_ka_batch_id;
            $jplId = (int) $scope->jpl_id;

            if (! $startsByBatch->has($batchId)) {
                continue;
            }

            $ranges[$jplId][] = [
                'batch_id' => $batchId,
                'start' => $startsByBatch->get($batchId),
                'end' => null,
            ];
        }

        foreach ($ranges as &$jplRanges) {
            usort($jplRanges, static function (array $left, array $right): int {
                return [$left['start'], $left['batch_id']] <=> [$right['start'], $right['batch_id']];
            });

            for ($index = 1; $index < count($jplRanges); $index++) {
                if ($jplRanges[$index - 1]['start'] === $jplRanges[$index]['start']) {
                    throw new LogicException('Dua batch pada JPL yang sama tidak boleh memiliki tanggal mulai berlaku yang sama.');
                }

                $jplRanges[$index - 1]['end'] = CarbonImmutable::parse($jplRanges[$index]['start'])
                    ->subDay()
                    ->toDateString();
            }
        }

        unset($jplRanges);

        return $ranges;
    }

    /**
     * @param  Collection<int, object>  $scopes
     */
    private function saveScopeEndDates(array $ranges): void
    {
        foreach ($ranges as $jplId => $jplRanges) {
            foreach ($jplRanges as $range) {
                DB::table('jadwal_ka_batch_jpl')
                    ->where('jadwal_ka_batch_id', $range['batch_id'])
                    ->where('jpl_id', $jplId)
                    ->update(['tanggal_akhir_berlaku' => $range['end']]);
            }
        }
    }

    /**
     * @param  array<int, array<int, array{batch_id: int, start: string, end: ?string}>>  $ranges
     * @return array<int, int>
     */
    private function effectiveJplIds(array $ranges, int $batchId, CarbonImmutable $date): array
    {
        $dateString = $date->toDateString();
        $effectiveIds = [];

        foreach ($ranges as $jplId => $jplRanges) {
            foreach ($jplRanges as $range) {
                if (
                    $range['batch_id'] === $batchId
                    && $range['start'] <= $dateString
                    && ($range['end'] === null || $range['end'] >= $dateString)
                ) {
                    $effectiveIds[] = (int) $jplId;
                    break;
                }
            }
        }

        sort($effectiveIds);

        return $effectiveIds;
    }

    /**
     * @param  array<int, int>  $jplIds
     */
    private function assertBatchHasSchedules(int $batchId, array $jplIds): void
    {
        $scheduledJplIds = JadwalKa::query()
            ->where('jadwal_ka_batch_id', $batchId)
            ->whereIn('jpl_id', $jplIds)
            ->distinct()
            ->orderBy('jpl_id')
            ->pluck('jpl_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($scheduledJplIds !== $jplIds) {
            throw new LogicException('Setiap JPL dalam batch harus memiliki jadwal yang tersimpan.');
        }
    }

    /**
     * @param  array<int, int>  $jplIds
     */
    private function activateBatchForJpls(int $batchId, array $jplIds): void
    {
        if ($jplIds === []) {
            return;
        }

        JadwalKa::query()
            ->whereIn('jpl_id', $jplIds)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        JadwalKa::query()
            ->where('jadwal_ka_batch_id', $batchId)
            ->whereIn('jpl_id', $jplIds)
            ->update(['is_active' => true]);
    }

    /**
     * @param  Collection<int, JadwalKaBatch>  $batches
     * @param  array<int, array<int, array{batch_id: int, start: string, end: ?string}>>  $ranges
     */
    private function archiveExpiredBatches(
        Collection $batches,
        CarbonImmutable $today,
        int $preserveBatchId
    ): void {
        $todayString = $today->toDateString();
        $scopes = $this->lockScopesForBatches($batches->modelKeys());

        foreach ($batches as $batch) {
            if ($batch->id === $preserveBatchId || ! in_array($batch->status, ['active', 'scheduled'], true)) {
                continue;
            }

            $batchScopes = $scopes->where('jadwal_ka_batch_id', $batch->id);

            if ($batchScopes->isNotEmpty() && $batchScopes->every(
                static fn (object $scope): bool => $scope->tanggal_akhir_berlaku !== null
                    && $scope->tanggal_akhir_berlaku < $todayString
            )) {
                $batch->status = 'archived';
                $batch->save();
            }
        }
    }
}
