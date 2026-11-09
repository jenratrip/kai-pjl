<?php

use App\Models\Dinas;
use App\Models\Jpl;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

function dinasCsvUpload(string ...$rows): UploadedFile
{
    $rows = array_map(fn (string $nip): string => implode(';', [
        $nip,
        'JPL-01',
        '1',
        '2099-10-01',
        '08:00:00',
        '16:00:00',
        '0',
        '',
    ]), $rows);

    return UploadedFile::fake()->createWithContent(
        'jadwal-dinas.csv',
        implode("\n", [
            'nip;kode_jpl;id_shift;tanggal_dinas;jam_mulai;jam_selesai;is_pengganti;nip_digantikan',
            ...$rows,
        ])
    );
}

test('CSV import creates all valid rows without inventing schedule snapshots', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-IMPORT');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-IMPORT');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift('08:00:00', '16:00:00');

    $this->actingAs($admin)
        ->post(route('admin.dinas.import'), [
            'file' => dinasCsvUpload($pjl->nip),
        ])
        ->assertRedirect(route('admin.dinas.index'));

    $dinas = Dinas::query()->sole();

    expect($dinas->user_id)->toBe($pjl->id)
        ->and($dinas->jpl_id)->toBe($jpl->id)
        ->and($dinas->shift_id)->toBe($shift->id)
        ->and($dinas->tanggal_dinas->toDateString())->toBe('2099-10-01')
        ->and($dinas->jadwal_ka_snapshot)->toBeNull()
        ->and($dinas->jadwal_ka_batch_id)->toBeNull();
});

test('CSV import rejects the entire file when one row is invalid', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-IMPORT-INVALID');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-IMPORT-VALID');
    $this->kaiJpl();
    $this->kaiShift('08:00:00', '16:00:00');

    $this->actingAs($admin)
        ->from(route('admin.dinas.index'))
        ->post(route('admin.dinas.import'), [
            'file' => dinasCsvUpload($pjl->nip, 'NO-SUCH-PJL'),
        ])
        ->assertRedirect(route('admin.dinas.index'))
        ->assertSessionHas('import_errors');

    expect(Dinas::query()->count())->toBe(0);
});

test('CSV import rolls back every row when a write fails during the transaction', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-IMPORT-ROLLBACK');
    $pjlRole = $this->kaiRole('pjl');
    $firstPjl = $this->kaiUser($pjlRole, 'PJL-IMPORT-ROLLBACK-1');
    $secondPjl = $this->kaiUser($pjlRole, 'PJL-IMPORT-ROLLBACK-2');
    $this->kaiJpl();
    $this->kaiShift('08:00:00', '16:00:00');

    Dinas::creating(function (): void {
        if (Dinas::query()->count() === 1) {
            throw new RuntimeException('Simulated Dinas insert failure.');
        }
    });

    $this->actingAs($admin)
        ->from(route('admin.dinas.index'))
        ->post(route('admin.dinas.import'), [
            'file' => dinasCsvUpload($firstPjl->nip, $secondPjl->nip),
        ])
        ->assertRedirect(route('admin.dinas.index'))
        ->assertSessionHas('error');

    expect(Dinas::query()->count())->toBe(0);
});

test('CSV import rechecks PJL active status inside the transaction', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-IMPORT-STALE-USER');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-IMPORT-STALE');
    $this->kaiJpl();
    $this->kaiShift('08:00:00', '16:00:00');
    $deactivated = false;

    User::retrieved(function (User $user) use (&$deactivated, $pjl): void {
        if (! $deactivated && $user->is($pjl)) {
            $deactivated = true;
            DB::table('users')
                ->where('id', $pjl->id)
                ->update(['is_active' => false]);
        }
    });

    $this->actingAs($admin)
        ->from(route('admin.dinas.index'))
        ->post(route('admin.dinas.import'), [
            'file' => dinasCsvUpload($pjl->nip),
        ])
        ->assertRedirect(route('admin.dinas.index'))
        ->assertSessionHas('error');

    expect($deactivated)->toBeTrue()
        ->and(Dinas::query()->count())->toBe(0);
});

test('CSV import rechecks PJL role inside the transaction', function () {
    $adminRole = $this->kaiRole('admin');
    $pjlRole = $this->kaiRole('pjl');
    $admin = $this->kaiUser($adminRole, 'ADMIN-IMPORT-STALE-ROLE');
    $pjl = $this->kaiUser($pjlRole, 'PJL-IMPORT-STALE-ROLE');
    $this->kaiJpl();
    $this->kaiShift('08:00:00', '16:00:00');
    $roleChanged = false;

    User::retrieved(function (User $user) use (&$roleChanged, $pjl, $adminRole): void {
        if (! $roleChanged && $user->is($pjl)) {
            $roleChanged = true;
            DB::table('users')
                ->where('id', $pjl->id)
                ->update(['role_id' => $adminRole->id]);
        }
    });

    $this->actingAs($admin)
        ->from(route('admin.dinas.index'))
        ->post(route('admin.dinas.import'), [
            'file' => dinasCsvUpload($pjl->nip),
        ])
        ->assertRedirect(route('admin.dinas.index'))
        ->assertSessionHas('error');

    expect($roleChanged)->toBeTrue()
        ->and(Dinas::query()->count())->toBe(0);
});

test('CSV import rechecks JPL active status inside the transaction', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-IMPORT-STALE-JPL');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-IMPORT-STALE-JPL');
    $jpl = $this->kaiJpl();
    $this->kaiShift('08:00:00', '16:00:00');
    $deactivated = false;

    Jpl::retrieved(function (Jpl $retrievedJpl) use (&$deactivated, $jpl): void {
        if (! $deactivated && $retrievedJpl->is($jpl)) {
            $deactivated = true;
            DB::table('jpl')
                ->where('id', $jpl->id)
                ->update(['is_active' => false]);
        }
    });

    $this->actingAs($admin)
        ->from(route('admin.dinas.index'))
        ->post(route('admin.dinas.import'), [
            'file' => dinasCsvUpload($pjl->nip),
        ])
        ->assertRedirect(route('admin.dinas.index'))
        ->assertSessionHas('error');

    expect($deactivated)->toBeTrue()
        ->and(Dinas::query()->count())->toBe(0);
});

test('CSV import rechecks schedule conflicts inside the transaction', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-IMPORT-CONFLICT');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-IMPORT-CONFLICT');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift('08:00:00', '16:00:00');
    $conflictInserted = false;

    DB::listen(function ($query) use (&$conflictInserted, $pjl, $jpl, $shift): void {
        $sql = strtolower($query->sql);

        if (
            $conflictInserted
            || ! str_contains($sql, 'from "dinas"')
            || ! str_contains($sql, 'jam_mulai')
            || ! str_contains($sql, 'jam_selesai')
        ) {
            return;
        }

        $conflictInserted = true;
        DB::table('dinas')->insert([
            'user_id' => $pjl->id,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => '2099-10-01',
            'jam_mulai' => '2099-10-01 08:00:00',
            'jam_selesai' => '2099-10-01 16:00:00',
            'status' => 'dijadwalkan',
            'is_pengganti' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    $this->actingAs($admin)
        ->from(route('admin.dinas.index'))
        ->post(route('admin.dinas.import'), [
            'file' => dinasCsvUpload($pjl->nip),
        ])
        ->assertRedirect(route('admin.dinas.index'))
        ->assertSessionHas('error');

    expect($conflictInserted)->toBeTrue()
        ->and(Dinas::query()->count())->toBe(1);
});
