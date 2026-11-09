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

test('a scheduled PJL reguler can log in to the Dinas assigned by Admin', function () {
    Carbon::setTestNow('2026-11-05 09:00:00');
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'PJL-REGULER-SCHEDULED', ['password' => bcrypt('secret123')]);
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'tanggal_dinas' => '2026-11-05',
        'is_pengganti' => false,
        'jadwal_ka_snapshot' => null,
    ]);

    $this->post(route('login.store'), [
        'nip' => $user->nip,
        'password' => 'secret123',
        'nomor_pjl' => $jpl->kode_jpl,
        'dinasan' => $shift->id,
        'jenis_petugas' => 'reguler',
    ])->assertRedirect(route('user.realisasi-ka'));

    expect(session('dinas_id'))->toBe($dinas->id)
        ->and($dinas->fresh()->jadwal_ka_snapshot)->toBeNull();
});

test('an unscheduled PJL reguler is rejected without creating a Dinas', function () {
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'PJL-REGULER-UNSCHEDULED', ['password' => bcrypt('secret123')]);
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();

    $this->from(route('login'))->post(route('login.store'), [
        'nip' => $user->nip,
        'password' => 'secret123',
        'nomor_pjl' => $jpl->kode_jpl,
        'dinasan' => $shift->id,
        'jenis_petugas' => 'reguler',
    ])->assertRedirect(route('login'))
        ->assertSessionHasErrors('nomor_pjl');

    expect(DB::table('dinas')->count())->toBe(0)
        ->and(Auth::check())->toBeFalse();
});

test('the login role check endpoint returns PJL without authenticating or creating a Dinas', function () {
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'PJL-CHECK', ['password' => bcrypt('secret123')]);

    $this->postJson(route('login.cek'), [
        'nip' => $user->nip,
        'password' => 'secret123',
    ])->assertOk()->assertJson(['role' => 'pjl']);

    expect(Auth::check())->toBeFalse()
        ->and(DB::table('dinas')->count())->toBe(0);
});

test('the login page links its role check request to the registered route', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-cek-url="'.route('login.cek').'"', false)
        ->assertDontSee('login-tab');
});

test('relogging to a Dinas with a non-empty snapshot preserves its snapshot and wakilan identity', function () {
    Carbon::setTestNow('2026-11-05 09:00:00');
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'PJL-LOCKED-WAKILAN', ['password' => bcrypt('secret123')]);
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $replacementUser = $this->kaiUser($role, 'PJL-REPLACED');
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'is_pengganti' => true,
        'user_digantikan_id' => $replacementUser->id,
        'jadwal_ka_snapshot' => [
            'format_version' => 1,
            'snapshot_at' => now()->toIso8601String(),
            'dinas_id' => 1,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => '2026-11-05',
            'jadwal_ka_batch_id' => null,
            'source' => 'legacy',
            'jadwal' => [['jadwal_ka_id' => 991]],
        ],
    ]);
    $snapshot = $dinas->jadwal_ka_snapshot;
    $snapshot['dinas_id'] = $dinas->id;
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);

    $this->post(route('login.store'), [
        'nip' => $user->nip,
        'password' => 'secret123',
        'nomor_pjl' => $jpl->kode_jpl,
        'dinasan' => $shift->id,
        'jenis_petugas' => 'wakilan',
    ])->assertRedirect(route('user.realisasi-ka'));

    expect($dinas->fresh()->jadwal_ka_snapshot)->toBe($snapshot)
        ->and($dinas->fresh()->user_digantikan_id)->toBe($replacementUser->id);
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
