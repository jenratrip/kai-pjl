<?php

use App\Services\JadwalKaVersionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('activating a version replaces only covered JPL and archives old batch only after every scope ends', function () {
    CarbonImmutable::setTestNow('2026-11-05 09:00:00');
    $jplA = $this->kaiJpl('JPL-A');
    $jplB = $this->kaiJpl('JPL-B');
    $train = $this->kaiTrain();

    $oldBatch = $this->kaiBatch('active', '2026-10-01');
    $oldBatch->jpls()->sync([$jplA->id => ['tanggal_akhir_berlaku' => null], $jplB->id => ['tanggal_akhir_berlaku' => null]]);
    $oldA = $this->kaiSchedule($jplA, $train, ['jadwal_ka_batch_id' => $oldBatch->id, 'is_active' => true]);
    $oldB = $this->kaiSchedule($jplB, $train, ['jadwal_ka_batch_id' => $oldBatch->id, 'is_active' => true]);

    $newBatch = $this->kaiBatch('draft', null);
    $newBatch->jpls()->sync([$jplA->id]);
    $newSchedule = $this->kaiSchedule($jplA, $train, ['jadwal_ka_batch_id' => $newBatch->id, 'is_active' => false]);

    $activated = app(JadwalKaVersionService::class)->scheduleDraftBatch(
        (int) $newBatch->id,
        CarbonImmutable::today()
    );

    expect($activated->status)->toBe('active')
        ->and($oldA->fresh()->is_active)->toBeFalse()
        ->and($oldB->fresh()->is_active)->toBeTrue()
        ->and($newSchedule->fresh()->is_active)->toBeTrue()
        ->and($oldBatch->fresh()->status)->toBe('active')
        ->and(DB::table('jadwal_ka_batch_jpl')->where('jadwal_ka_batch_id', $oldBatch->id)->where('jpl_id', $jplA->id)->value('tanggal_akhir_berlaku'))->toBe('2026-11-04')
        ->and(DB::table('jadwal_ka_batch_jpl')->where('jadwal_ka_batch_id', $oldBatch->id)->where('jpl_id', $jplB->id)->value('tanggal_akhir_berlaku'))->toBeNull();

});

test('future batch remains scheduled and does not change active schedule until effective date', function () {
    CarbonImmutable::setTestNow('2026-11-05 09:00:00');
    $jpl = $this->kaiJpl();
    $train = $this->kaiTrain();
    $activeBatch = $this->kaiBatch('active', '2026-10-01');
    $activeBatch->jpls()->sync([$jpl->id]);
    $oldSchedule = $this->kaiSchedule($jpl, $train, ['jadwal_ka_batch_id' => $activeBatch->id, 'is_active' => true]);

    $futureBatch = $this->kaiBatch('draft', null);
    $futureBatch->jpls()->sync([$jpl->id]);
    $futureSchedule = $this->kaiSchedule($jpl, $train, ['jadwal_ka_batch_id' => $futureBatch->id, 'is_active' => false]);

    $scheduled = app(JadwalKaVersionService::class)->scheduleDraftBatch(
        (int) $futureBatch->id,
        CarbonImmutable::tomorrow()
    );

    expect($scheduled->status)->toBe('scheduled')
        ->and($oldSchedule->fresh()->is_active)->toBeTrue()
        ->and($futureSchedule->fresh()->is_active)->toBeFalse()
        ->and(app(JadwalKaVersionService::class)->activateScheduledBatch((int) $futureBatch->id))->toBeFalse();

});

test('scheduled batch is activated once by the due-batch command', function () {
    CarbonImmutable::setTestNow('2026-11-05 09:00:00');
    $jpl = $this->kaiJpl();
    $train = $this->kaiTrain();
    $oldBatch = $this->kaiBatch('active', '2026-10-01');
    $oldBatch->jpls()->sync([$jpl->id]);
    $oldSchedule = $this->kaiSchedule($jpl, $train, ['jadwal_ka_batch_id' => $oldBatch->id, 'is_active' => true]);

    $scheduledBatch = $this->kaiBatch('scheduled', '2026-11-05');
    $scheduledBatch->jpls()->sync([$jpl->id]);
    $newSchedule = $this->kaiSchedule($jpl, $train, ['jadwal_ka_batch_id' => $scheduledBatch->id, 'is_active' => false]);

    $this->artisan('jadwal-ka:activate-due-batches')->assertSuccessful();

    expect($scheduledBatch->fresh()->status)->toBe('active')
        ->and($oldSchedule->fresh()->is_active)->toBeFalse()
        ->and($newSchedule->fresh()->is_active)->toBeTrue();

    $this->artisan('jadwal-ka:activate-due-batches')->assertSuccessful();

    expect($scheduledBatch->fresh()->status)->toBe('active')
        ->and($newSchedule->fresh()->is_active)->toBeTrue();

});

test('Superadmin cannot use the Admin-only batch scheduling endpoint', function () {
    $superadminRole = $this->kaiRole('superadmin');
    $superadmin = $this->kaiUser($superadminRole, 'S-BATCH-1');
    $jpl = $this->kaiJpl();
    $batch = $this->kaiBatch('draft', null);
    $batch->jpls()->sync([$jpl->id]);
    $this->kaiSchedule($jpl, $this->kaiTrain(), [
        'jadwal_ka_batch_id' => $batch->id,
        'is_active' => false,
    ]);

    $this->actingAs($superadmin)
        ->post(route('admin.jadwal-ka-batches.schedule', $batch), [
            'tanggal_mulai_berlaku' => now()->toDateString(),
        ])
        ->assertForbidden();

    expect($batch->fresh()->status)->toBe('draft');
});
