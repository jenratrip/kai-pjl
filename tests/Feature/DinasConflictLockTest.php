<?php

use App\Models\Dinas;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

test('updating a dinas rejects overlapping time for the selected pjl and keeps original data', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'A-1');
    $pjlRole = $this->kaiRole('pjl');
    $currentPjl = $this->kaiUser($pjlRole, 'P-1');
    $targetPjl = $this->kaiUser($pjlRole, 'P-2');
    $currentJpl = $this->kaiJpl('JPL-01');
    $targetJpl = $this->kaiJpl('JPL-02');
    $currentShift = $this->kaiShift('08:00:00', '16:00:00');
    $targetShift = $this->kaiShift('10:00:00', '18:00:00');
    $date = '2026-11-05';

    $dinasToUpdate = $this->kaiDinas($currentPjl, $currentJpl, $currentShift, [
        'tanggal_dinas' => $date,
        'jam_mulai' => $date.' 08:00:00',
        'jam_selesai' => $date.' 16:00:00',
    ]);

    $this->kaiDinas($targetPjl, $targetJpl, $targetShift, [
        'tanggal_dinas' => $date,
        'jam_mulai' => $date.' 10:00:00',
        'jam_selesai' => $date.' 18:00:00',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.dinas.update', $dinasToUpdate), [
            'user_id' => $targetPjl->id,
            'jpl_id' => $targetJpl->id,
            'shift_id' => $targetShift->id,
            'tanggal_dinas' => $date,
            'is_pengganti' => false,
        ])
        ->assertSessionHasErrors('user_id');

    expect($dinasToUpdate->fresh()->user_id)->toBe($currentPjl->id)
        ->and($dinasToUpdate->fresh()->jpl_id)->toBe($currentJpl->id)
        ->and(Dinas::query()->count())->toBe(2);
});
