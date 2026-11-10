<?php

use App\Models\ActivityLog;
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

test('Admin Dinas CSV template matches the importer header without invalid sample records', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-TEMPLATE');

    $response = $this->actingAs($admin)
        ->get(route('admin.dinas.template'))
        ->assertOk()
        ->assertDownload('template-jadwal-dinas.csv');

    $csv = preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());
    $rows = array_values(array_filter(explode("\n", trim($csv))));

    expect(str_getcsv($rows[0], ';'))->toBe([
        'nip',
        'kode_jpl',
        'id_shift',
        'tanggal_dinas',
        'jam_mulai',
        'jam_selesai',
        'is_pengganti',
        'nip_digantikan',
    ])->and($rows)->toHaveCount(1);
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

    $activity = ActivityLog::query()->sole();
    expect($activity->user_id)->toBe($admin->id)
        ->and($activity->action)->toBe('create')
        ->and($activity->module)->toBe('dinas')
        ->and($activity->subject_type)->toBe(Dinas::class)
        ->and($activity->subject_id)->toBe($dinas->id)
        ->and($activity->new_values)->toHaveKeys([
            'user_id', 'jpl_id', 'shift_id', 'tanggal_dinas', 'status',
        ])
        ->and($activity->new_values)->not->toHaveKey('password')
        ->and($activity->new_values)->not->toHaveKey('jadwal_ka_snapshot');
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
        ->and(DB::table('dinas')->count())->toBe(0)
        ->and(ActivityLog::query()->count())->toBe(0);
});

test('Admin Dinas updates and deletes are audited with safe before and after summaries', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-AUDIT-CRUD');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-AUDIT-CRUD');
    $jpl = $this->kaiJpl('JPL-DINAS-AUDIT-CRUD');
    $shift = $this->kaiShift();
    $dinas = $this->kaiDinas($pjl, $jpl, $shift, [
        'status' => 'dijadwalkan',
        'jam_mulai' => now()->addDay(),
        'jam_selesai' => now()->addDay()->addHours(8),
        'jadwal_ka_snapshot' => null,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.dinas.update', $dinas), [
            'user_id' => $pjl->id,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => now()->addDay()->toDateString(),
            'is_pengganti' => false,
        ])
        ->assertRedirect(route('admin.dinas.index'));

    $updateLog = ActivityLog::query()->where('action', 'update')->sole();
    expect($updateLog->module)->toBe('dinas')
        ->and($updateLog->subject_id)->toBe($dinas->id)
        ->and($updateLog->old_values)->toHaveKey('tanggal_dinas')
        ->and($updateLog->new_values)->not->toHaveKey('password')
        ->and($updateLog->new_values)->not->toHaveKey('catatan');

    $this->delete(route('admin.dinas.destroy', $dinas))
        ->assertRedirect(route('admin.dinas.index'));

    $deleteLog = ActivityLog::query()->where('action', 'delete')->sole();
    expect($deleteLog->subject_id)->toBe($dinas->id)
        ->and($deleteLog->old_values['status'])->toBe('dijadwalkan');

    expect(Dinas::query()->whereKey($dinas->id)->exists())->toBeFalse();
});

test('Dinas status synchronization logs only rows whose status changed', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-STATUS-SYNC');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-STATUS-SYNC');
    $jpl = $this->kaiJpl('JPL-DINAS-STATUS-SYNC');
    $shift = $this->kaiShift();

    $starting = $this->kaiDinas($pjl, $jpl, $shift, [
        'status' => 'dijadwalkan',
        'jam_mulai' => now()->subMinute(),
        'jam_selesai' => now()->addHour(),
    ]);
    $expired = $this->kaiDinas($pjl, $jpl, $shift, [
        'status' => 'berlangsung',
        'jam_mulai' => now()->subHours(9),
        'jam_selesai' => now()->subMinute(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dinas.index'))
        ->assertOk();

    expect($starting->fresh()->status)->toBe('berlangsung')
        ->and($expired->fresh()->status)->toBe('selesai')
        ->and(ActivityLog::query()->count())->toBe(2)
        ->and(ActivityLog::query()->where('action', 'status_sync')->count())->toBe(2);

    $this->get(route('admin.dinas.index'))->assertOk();

    expect(ActivityLog::query()->count())->toBe(2);
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

test('admin cannot change a Dinas context after its snapshot is captured', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-LOCKED');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-LOCKED');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift('08:00:00', '16:00:00');
    $dinas = $this->kaiDinas($pjl, $jpl, $shift, [
        'tanggal_dinas' => '2026-11-05',
        'jam_mulai' => '2026-11-05 08:00:00',
        'jam_selesai' => '2026-11-05 16:00:00',
        'jadwal_ka_snapshot' => [
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => '2026-11-05',
            'jadwal' => [],
        ],
    ]);

    $this->actingAs($admin)
        ->put(route('admin.dinas.update', $dinas), [
            'user_id' => $pjl->id,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => '2026-11-06',
            'is_pengganti' => false,
        ])
        ->assertSessionHasErrors('dinas');

    expect($dinas->fresh()->tanggal_dinas->toDateString())->toBe('2026-11-05')
        ->and($dinas->fresh()->jadwal_ka_snapshot['tanggal_dinas'])->toBe('2026-11-05');
});

test('admin cannot delete a Dinas after its snapshot is captured', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-DELETE-LOCKED');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-DELETE-LOCKED');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $dinas = $this->kaiDinas($pjl, $jpl, $shift, [
        'jadwal_ka_snapshot' => ['jadwal' => []],
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.dinas.destroy', $dinas))
        ->assertSessionHasErrors('dinas');

    $this->assertModelExists($dinas);
});
