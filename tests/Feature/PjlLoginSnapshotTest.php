<?php

use App\Models\Dinas;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

afterEach(function () {
    Carbon::setTestNow();
});

test('a new PJL wakilan Dinas captures one snapshot and relogin keeps that empty snapshot unchanged', function () {
    Carbon::setTestNow('2026-11-05 09:00:00');
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'PJL-LOGIN', ['password' => bcrypt('secret123')]);
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();

    $loginData = [
        'nip' => $user->nip,
        'password' => 'secret123',
        'nomor_pjl' => $jpl->kode_jpl,
        'dinasan' => $shift->id,
        'jenis_petugas' => 'wakilan',
    ];

    $this->post(route('login.store'), $loginData)->assertRedirect(route('user.realisasi-ka'));
    $dinas = Dinas::query()->sole();
    $firstSnapshot = $dinas->jadwal_ka_snapshot;

    expect($firstSnapshot)->toBeArray()
        ->and($firstSnapshot['source'])->toBe('legacy')
        ->and($firstSnapshot['dinas_id'])->toBe($dinas->id)
        ->and($firstSnapshot['jpl_id'])->toBe($jpl->id)
        ->and($firstSnapshot['shift_id'])->toBe($shift->id)
        ->and($firstSnapshot['tanggal_dinas'])->toBe('2026-11-05')
        ->and($firstSnapshot['jadwal'])->toBe([])
        ->and($dinas->jadwal_ka_batch_id)->toBeNull();

    Auth::logout();
    $this->flushSession();
    $this->post(route('login.store'), $loginData)->assertRedirect(route('user.realisasi-ka'));

    expect(Dinas::query()->sole()->jadwal_ka_snapshot)->toBe($firstSnapshot)
        ->and(Dinas::query()->sole()->user_digantikan_id)->toBeNull();

    Carbon::setTestNow();
});

test('logging in to an existing legacy Dinas does not create its missing snapshot', function () {
    Carbon::setTestNow('2026-11-05 09:00:00');
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'PJL-LEGACY', ['password' => bcrypt('secret123')]);
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $legacyDinas = $this->kaiDinas($user, $jpl, $shift, [
        'jadwal_ka_snapshot' => null,
        'is_pengganti' => true,
        'jam_mulai' => now()->subHour(),
    ]);

    $this->post(route('login.store'), [
        'nip' => $user->nip,
        'password' => 'secret123',
        'nomor_pjl' => $jpl->kode_jpl,
        'dinasan' => $shift->id,
        'jenis_petugas' => 'wakilan',
    ])->assertRedirect(route('user.realisasi-ka'));

    expect($legacyDinas->fresh()->jadwal_ka_snapshot)->toBeNull();

    Carbon::setTestNow();
});

test('logging in to an existing Dinas with an empty snapshot keeps the empty snapshot', function () {
    Carbon::setTestNow('2026-11-05 09:00:00');
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'PJL-EMPTY-SNAPSHOT', ['password' => bcrypt('secret123')]);
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'jadwal_ka_snapshot' => [],
        'is_pengganti' => true,
        'jam_mulai' => now()->subHour(),
    ]);

    $this->post(route('login.store'), [
        'nip' => $user->nip,
        'password' => 'secret123',
        'nomor_pjl' => $jpl->kode_jpl,
        'dinasan' => $shift->id,
        'jenis_petugas' => 'wakilan',
    ])->assertRedirect(route('user.realisasi-ka'));

    expect($dinas->fresh()->jadwal_ka_snapshot)->toBe([]);

    Carbon::setTestNow();
});

test('Admin and Superadmin still log in without PJL operational fields', function () {
    $adminRole = $this->kaiRole('admin');
    $superadminRole = $this->kaiRole('superadmin');
    $admin = $this->kaiUser($adminRole, 'ADMIN-LOGIN', ['password' => bcrypt('secret123')]);
    $superadmin = $this->kaiUser($superadminRole, 'SUPERADMIN-LOGIN', ['password' => bcrypt('secret123')]);

    $this->post(route('login.store'), ['nip' => $admin->nip, 'password' => 'secret123'])
        ->assertRedirect(route('admin.dashboard'));

    expect(DB::table('dinas')->count())->toBe(0);

    Auth::logout();
    $this->flushSession();

    $this->post(route('login.store'), ['nip' => $superadmin->nip, 'password' => 'secret123'])
        ->assertRedirect(route('superadmin.dashboard'));

    expect(DB::table('dinas')->count())->toBe(0);
});
