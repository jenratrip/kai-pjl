<?php

use App\Services\DinasJadwalSnapshotService;
use App\Services\JadwalKaVersionService;
use Carbon\CarbonImmutable;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('snapshot resolves effective batch records regardless of Excel effective date and never recalculates', function () {
    CarbonImmutable::setTestNow('2026-11-05 09:00:00');
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'P-1');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift('08:00:00', '16:00:00');
    $train = $this->kaiTrain('BATCH-1');
    $batch = $this->kaiBatch('archived', '2026-11-01');
    $batch->jpls()->sync([$jpl->id => ['tanggal_akhir_berlaku' => null]]);
    $schedule = $this->kaiSchedule($jpl, $train, [
        'jadwal_ka_batch_id' => $batch->id,
        'is_active' => false,
        'tanggal_berlaku' => '2020-01-01',
        'hari_berlaku' => null,
        'jam_lewat' => '09:30:00',
    ]);
    $dinas = $this->kaiDinas($user, $jpl, $shift, ['tanggal_dinas' => '2026-11-05']);

    $service = app(DinasJadwalSnapshotService::class);
    $snapshot = $service->resolveAndStore((int) $dinas->id);

    expect($snapshot['source'])->toBe('batch')
        ->and($snapshot['jadwal_ka_batch_id'])->toBe($batch->id)
        ->and($snapshot['jadwal'])->toHaveCount(1)
        ->and($snapshot['jadwal'][0]['jadwal_ka_id'])->toBe($schedule->id);

    $schedule->update(['jam_lewat' => '15:30:00']);
    $again = $service->resolveAndStore((int) $dinas->id);

    expect($again)->toBe($snapshot)
        ->and($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot);

    CarbonImmutable::setTestNow();
});

test('legacy snapshot uses active legacy schedules and stores an empty list when none match', function () {
    CarbonImmutable::setTestNow('2026-11-05 09:00:00');
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'P-2');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift('10:00:00', '12:00:00');
    $train = $this->kaiTrain('LEGACY-1');
    $schedule = $this->kaiSchedule($jpl, $train, [
        'hari_berlaku' => ['Kamis'],
        'jam_lewat' => '10:30:00',
        'is_active' => true,
    ]);
    $dinas = $this->kaiDinas($user, $jpl, $shift, ['tanggal_dinas' => '2026-11-05']);

    $snapshot = app(DinasJadwalSnapshotService::class)->resolveAndStore((int) $dinas->id);

    expect($snapshot['source'])->toBe('legacy')
        ->and($snapshot['jadwal_ka_batch_id'])->toBeNull()
        ->and($snapshot['jadwal'])->toHaveCount(1)
        ->and($snapshot['jadwal'][0]['jadwal_ka_id'])->toBe($schedule->id);

    CarbonImmutable::setTestNow();
});

test('a future batch does not replace legacy schedules for an earlier Dinas date', function () {
    CarbonImmutable::setTestNow('2026-11-05 09:00:00');
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-FUTURE-BATCH');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift('08:00:00', '12:00:00');
    $train = $this->kaiTrain('FUTURE-1');
    $futureBatch = $this->kaiBatch('scheduled', '2026-11-06');
    $futureBatch->jpls()->sync([$jpl->id => ['tanggal_akhir_berlaku' => null]]);
    $futureSchedule = $this->kaiSchedule($jpl, $train, [
        'jadwal_ka_batch_id' => $futureBatch->id,
        'is_active' => false,
        'jam_lewat' => '09:00:00',
    ]);
    $legacySchedule = $this->kaiSchedule($jpl, $train, [
        'hari_berlaku' => ['Kamis'],
        'jam_lewat' => '10:00:00',
        'is_active' => true,
    ]);
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'tanggal_dinas' => '2026-11-05',
    ]);

    $snapshot = app(DinasJadwalSnapshotService::class)->resolveAndStore((int) $dinas->id);

    expect($snapshot['source'])->toBe('legacy')
        ->and($snapshot['jadwal_ka_batch_id'])->toBeNull()
        ->and($snapshot['jadwal'])->toHaveCount(1)
        ->and($snapshot['jadwal'][0]['jadwal_ka_id'])->toBe($legacySchedule->id)
        ->and($snapshot['jadwal'][0]['jadwal_ka_id'])->not->toBe($futureSchedule->id);

    CarbonImmutable::setTestNow();
});

test('snapshot uses the Dinas date and includes schedules inside a cross-midnight shift', function () {
    CarbonImmutable::setTestNow('2026-11-06 02:00:00');
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-OVERNIGHT');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift('22:00:00', '06:00:00');
    $batch = $this->kaiBatch('archived', '2026-11-01');
    $batch->jpls()->sync([
        $jpl->id => ['tanggal_akhir_berlaku' => '2026-11-05'],
    ]);
    $schedule = $this->kaiSchedule($jpl, $this->kaiTrain('OVERNIGHT-1'), [
        'jadwal_ka_batch_id' => $batch->id,
        'tanggal_berlaku' => '2020-01-01',
        'jam_lewat' => '01:30:00',
        'is_active' => false,
    ]);
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'tanggal_dinas' => '2026-11-05',
    ]);

    $snapshot = app(DinasJadwalSnapshotService::class)->resolveAndStore((int) $dinas->id);

    expect($snapshot['tanggal_dinas'])->toBe('2026-11-05')
        ->and($snapshot['source'])->toBe('batch')
        ->and($snapshot['jadwal_ka_batch_id'])->toBe($batch->id)
        ->and($snapshot['jadwal'])->toHaveCount(1)
        ->and($snapshot['jadwal'][0]['jadwal_ka_id'])->toBe($schedule->id);

    CarbonImmutable::setTestNow();
});

test('activating a new batch leaves an existing Dinas snapshot unchanged', function () {
    CarbonImmutable::setTestNow('2026-11-05 09:00:00');
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-SNAPSHOT-ACTIVATE');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift('08:00:00', '16:00:00');
    $train = $this->kaiTrain('VERSION-SNAPSHOT');
    $oldBatch = $this->kaiBatch('active', '2026-11-01');
    $oldBatch->jpls()->sync([$jpl->id => ['tanggal_akhir_berlaku' => null]]);
    $oldSchedule = $this->kaiSchedule($jpl, $train, [
        'jadwal_ka_batch_id' => $oldBatch->id,
        'is_active' => true,
    ]);
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'tanggal_dinas' => '2026-11-05',
    ]);
    $snapshot = app(DinasJadwalSnapshotService::class)->resolveAndStore((int) $dinas->id);

    $newBatch = $this->kaiBatch('draft', null);
    $newBatch->jpls()->sync([$jpl->id]);
    $this->kaiSchedule($jpl, $train, [
        'jadwal_ka_batch_id' => $newBatch->id,
        'is_active' => false,
    ]);

    app(JadwalKaVersionService::class)->scheduleDraftBatch(
        (int) $newBatch->id,
        CarbonImmutable::today()
    );

    expect($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot)
        ->and($dinas->fresh()->jadwal_ka_snapshot['jadwal'][0]['jadwal_ka_id'])->toBe($oldSchedule->id)
        ->and($oldSchedule->fresh()->is_active)->toBeFalse();

    CarbonImmutable::setTestNow();
});
