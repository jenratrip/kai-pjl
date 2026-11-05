<?php

use App\Models\Dinas;
use App\Models\JadwalKaBatch;
use App\Services\DinasJadwalSnapshotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

test('admin-created Dinas receives its snapshot inside the create transaction', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-SNAPSHOT');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-SNAPSHOT');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();

    $this->actingAs($admin)->post(route('admin.dinas.store'), [
        'user_id' => $pjl->id,
        'jpl_id' => $jpl->id,
        'shift_id' => $shift->id,
        'tanggal_dinas' => now()->toDateString(),
        'is_pengganti' => false,
    ])->assertRedirect(route('admin.dinas.index'));

    $dinas = Dinas::query()->sole();

    expect($dinas->jadwal_ka_snapshot)->toBeArray()
        ->and($dinas->jadwal_ka_snapshot['dinas_id'])->toBe($dinas->id)
        ->and($dinas->jadwal_ka_snapshot['jpl_id'])->toBe($jpl->id)
        ->and($dinas->jadwal_ka_snapshot['shift_id'])->toBe($shift->id)
        ->and($dinas->jadwal_ka_snapshot['tanggal_dinas'])->toBe(now()->toDateString())
        ->and($dinas->jadwal_ka_snapshot['jadwal'])->toBe([])
        ->and($dinas->jadwal_ka_batch_id)->toBeNull();
});

test('admin Dinas creation rolls back when snapshot resolution fails', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-ROLLBACK');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-ROLLBACK');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $batch = $this->kaiBatch('active', null);
    $batch->jpls()->sync([$jpl->id]);

    expect(fn () => $this->withoutExceptionHandling()->actingAs($admin)->post(route('admin.dinas.store'), [
        'user_id' => $pjl->id,
        'jpl_id' => $jpl->id,
        'shift_id' => $shift->id,
        'tanggal_dinas' => now()->toDateString(),
        'is_pengganti' => false,
    ]))->toThrow(LogicException::class, 'tidak memiliki tanggal mulai berlaku');

    expect(Dinas::query()->count())->toBe(0)
        ->and(DB::table('dinas')->count())->toBe(0);
});

test('snapshot resolver rejects non-draft batches without an effective date clearly', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'PJL-BATCH-NO-DATE');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $batch = JadwalKaBatch::query()->create([
        'uuid' => (string) Str::uuid(),
        'status' => 'scheduled',
        'tanggal_mulai_berlaku' => null,
    ]);
    $batch->jpls()->sync([$jpl->id]);
    $dinas = $this->kaiDinas($user, $jpl, $shift);

    expect(fn () => app(DinasJadwalSnapshotService::class)->resolveAndStore((int) $dinas->id))
        ->toThrow(LogicException::class, 'tidak memiliki tanggal mulai berlaku');

    expect($dinas->fresh()->jadwal_ka_snapshot)->toBeNull();
});
