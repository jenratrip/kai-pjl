<?php

use App\Models\Dinas;
use App\Models\RealisasiKa;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

test('scheduled realization accepts an inactive schedule only when its ID is in the Dinas snapshot', function () {
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'P-REAL-1');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $train = $this->kaiTrain();
    $capturedSchedule = $this->kaiSchedule($jpl, $train, ['is_active' => false]);
    $outsideSchedule = $this->kaiSchedule($jpl, $train, ['jam_lewat' => '11:00:00']);
    $snapshot = [
        'format_version' => 1,
        'snapshot_at' => now()->toIso8601String(),
        'dinas_id' => 1,
        'jpl_id' => $jpl->id,
        'shift_id' => $shift->id,
        'tanggal_dinas' => now()->toDateString(),
        'jadwal_ka_batch_id' => null,
        'source' => 'legacy',
        'jadwal' => [[
            'jadwal_ka_id' => $capturedSchedule->id,
            'kereta_api_id' => $train->id,
            'jpl_id' => $jpl->id,
            'nomor_ka' => $train->nomor_ka,
            'nama_ka' => $train->nama_ka,
            'jam_lewat' => '10:00:00',
        ]],
    ];
    $dinas = $this->kaiDinas($user, $jpl, $shift, ['jadwal_ka_snapshot' => $snapshot]);
    $snapshot['dinas_id'] = $dinas->id;
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);

    $this->actingAs($user)
        ->withSession(['dinas_id' => $dinas->id])
        ->get(route('user.realisasi-ka'))
        ->assertOk()
        ->assertViewHas('dataKA', function (array $data): bool {
            return count($data) === 1
                && $data[0]['nomor'] === 'KA-001'
                && $data[0]['jadwal_ka_id'] > 0;
        });

    $this->actingAs($user)->withSession(['dinas_id' => $dinas->id])->postJson(route('user.realisasi-ka.store'), [
        'data' => [[
            'nomor' => $train->nomor_ka,
            'nama' => $train->nama_ka,
            'jadwal_ka_id' => $capturedSchedule->id,
            'is_manual' => false,
            'status' => 'JALAN',
        ]],
    ])->assertOk();

    expect(RealisasiKa::query()->where('jadwal_ka_id', $capturedSchedule->id)->exists())->toBeTrue();

    $this->actingAs($user)->withSession(['dinas_id' => $dinas->id])->postJson(route('user.realisasi-ka.store'), [
        'data' => [[
            'nomor' => $train->nomor_ka,
            'nama' => $train->nama_ka,
            'jadwal_ka_id' => $outsideSchedule->id,
            'is_manual' => false,
            'status' => 'JALAN',
        ]],
    ])->assertUnprocessable();

    expect(RealisasiKa::query()->where('jadwal_ka_id', $outsideSchedule->id)->exists())->toBeFalse();
});

test('manual realization can be saved when the Dinas snapshot is empty', function () {
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'P-REAL-2');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'jadwal_ka_snapshot' => [
            'format_version' => 1,
            'snapshot_at' => now()->toIso8601String(),
            'dinas_id' => 1,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => now()->toDateString(),
            'jadwal_ka_batch_id' => null,
            'source' => 'legacy',
            'jadwal' => [],
        ],
    ]);
    $snapshot = $dinas->jadwal_ka_snapshot;
    $snapshot['dinas_id'] = $dinas->id;
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);

    $this->actingAs($user)->withSession(['dinas_id' => $dinas->id])->postJson(route('user.realisasi-ka.store'), [
        'data' => [[
            'nomor' => 'MANUAL-99',
            'nama' => 'KA Manual',
            'hulu' => 'Asal',
            'hilir' => 'Tujuan',
            'relasi' => 'Asal - Tujuan',
            'jadwal_ka_id' => null,
            'is_manual' => true,
            'status' => 'BELUM',
        ]],
    ])->assertOk();

    $saved = RealisasiKa::query()->sole();
    expect($saved->is_manual)->toBeTrue()
        ->and($saved->jadwal_ka_id)->toBeNull()
        ->and($saved->keretaApi->nomor_ka)->toBe('MANUAL-99');
});

test('an empty Dinas snapshot does not fall back to active schedules', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-REAL-3');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $this->kaiSchedule($jpl, $this->kaiTrain('ACTIVE-OUTSIDE'), [
        'is_active' => true,
    ]);
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'jadwal_ka_snapshot' => null,
    ]);
    $dinas->update([
        'jadwal_ka_snapshot' => [
            'format_version' => 1,
            'snapshot_at' => now()->toIso8601String(),
            'dinas_id' => $dinas->id,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => now()->toDateString(),
            'jadwal_ka_batch_id' => null,
            'source' => 'legacy',
            'jadwal' => [],
        ],
    ]);

    $this->actingAs($user)
        ->withSession(['dinas_id' => $dinas->id])
        ->get(route('user.realisasi-ka'))
        ->assertOk()
        ->assertViewHas('dataKA', []);
});

test('a legacy Dinas without a snapshot does not use current active schedules as history', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-REAL-NULL');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $this->kaiSchedule($jpl, $this->kaiTrain('ACTIVE-NO-SNAPSHOT'), [
        'is_active' => true,
    ]);
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'jadwal_ka_snapshot' => null,
    ]);

    $this->actingAs($user)
        ->withSession(['dinas_id' => $dinas->id])
        ->get(route('user.realisasi-ka'))
        ->assertOk()
        ->assertViewHas('dataKA', []);

    expect($dinas->fresh()->jadwal_ka_snapshot)->toBeNull();
});

test('legacy Dinas still displays its stored realization when the source schedule is inactive', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-REAL-4');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $train = $this->kaiTrain('LEGACY-HISTORY');
    $schedule = $this->kaiSchedule($jpl, $train, [
        'is_active' => false,
        'jam_lewat' => '09:45:00',
        'dari' => 'Stasiun Lama',
        'ke' => 'Stasiun Tujuan',
    ]);
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'jadwal_ka_snapshot' => null,
    ]);
    RealisasiKa::query()->create([
        'dinas_id' => $dinas->id,
        'jadwal_ka_id' => $schedule->id,
        'kereta_api_id' => $train->id,
        'status_lewat' => 'centang',
        'jam_aktual' => '09:46:00',
        'is_manual' => false,
        'hulu' => 'Stasiun Lama',
        'hilir' => 'Stasiun Tujuan',
        'relasi' => 'Stasiun Lama - Stasiun Tujuan',
    ]);

    $this->actingAs($user)
        ->withSession(['dinas_id' => $dinas->id])
        ->get(route('user.realisasi-ka'))
        ->assertOk()
        ->assertViewHas('dataKA', function (array $data): bool {
            return count($data) === 1
                && $data[0]['nomor'] === 'LEGACY-HISTORY'
                && $data[0]['jadwal'] === '09:45'
                && $data[0]['relasi'] === 'Stasiun Lama - Stasiun Tujuan';
        });
});

test('Realisasi uses the Dinas ID from session instead of the latest active Dinas', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-SESSION-CANONICAL');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $train = $this->kaiTrain('SESSION-CANONICAL-KA');
    $schedule = $this->kaiSchedule($jpl, $train);
    $oldDinas = $this->kaiDinas($user, $jpl, $shift, [
        'jadwal_ka_snapshot' => [
            'format_version' => 1,
            'snapshot_at' => now()->toIso8601String(),
            'dinas_id' => 1,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => now()->toDateString(),
            'jadwal_ka_batch_id' => null,
            'source' => 'legacy',
            'jadwal' => [[
                'jadwal_ka_id' => $schedule->id,
                'kereta_api_id' => $train->id,
                'jpl_id' => $jpl->id,
                'nomor_ka' => $train->nomor_ka,
                'nama_ka' => $train->nama_ka,
                'jam_lewat' => '10:00:00',
            ]],
        ],
    ]);
    $snapshot = $oldDinas->jadwal_ka_snapshot;
    $snapshot['dinas_id'] = $oldDinas->id;
    $oldDinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $newestDinas = $this->kaiDinas($user, $jpl, $shift, ['jadwal_ka_snapshot' => []]);

    $this->actingAs($user)
        ->withSession(['dinas_id' => $oldDinas->id])
        ->get(route('user.realisasi-ka'))
        ->assertOk()
        ->assertViewHas('dinasAktif', fn (Dinas $dinas): bool => $dinas->id === $oldDinas->id)
        ->assertViewHas('dataKA', fn (array $data): bool => count($data) === 1);

    $this->actingAs($user)->withSession(['dinas_id' => $oldDinas->id])->postJson(route('user.realisasi-ka.store'), [
        'data' => [[
            'nomor' => $train->nomor_ka,
            'jadwal_ka_id' => $schedule->id,
            'is_manual' => false,
            'status' => 'JALAN',
        ]],
    ])->assertOk();

    expect(RealisasiKa::query()->sole()->dinas_id)->toBe($oldDinas->id)
        ->and($newestDinas->realisasiKa()->exists())->toBeFalse();
});

test('missing or foreign Dinas session never falls back to another active Dinas', function () {
    $role = $this->kaiRole('pjl');
    $owner = $this->kaiUser($role, 'P-SESSION-OWNER');
    $other = $this->kaiUser($role, 'P-SESSION-OTHER');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $otherDinas = $this->kaiDinas($other, $jpl, $shift);

    $this->actingAs($owner)
        ->get(route('user.realisasi-ka'))
        ->assertOk()
        ->assertViewHas('dinasAktif', null)
        ->assertViewHas('dataKA', []);

    $this->actingAs($owner)
        ->withSession(['dinas_id' => $otherDinas->id])
        ->get(route('user.realisasi-ka'))
        ->assertOk()
        ->assertViewHas('dinasAktif', null)
        ->assertViewHas('dataKA', []);

    $this->actingAs($owner)
        ->withSession(['dinas_id' => $otherDinas->id])
        ->postJson(route('user.realisasi-ka.store'), [
            'data' => [[
                'nomor' => 'SESSION-FOREIGN',
                'is_manual' => true,
                'status' => 'BELUM',
            ]],
        ])
        ->assertUnprocessable();

    expect(RealisasiKa::query()->count())->toBe(0)
        ->and($otherDinas->realisasiKa()->exists())->toBeFalse();
});

test('Realisasi GET does not create a settings row when none exists', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-SETTINGS-READ');
    $this->kaiDinas($user, $this->kaiJpl(), $this->kaiShift());

    $this->actingAs($user)
        ->withSession(['dinas_id' => 1])
        ->get(route('user.realisasi-ka'))
        ->assertOk()
        ->assertViewHas('realisasiKaSetting', function ($setting): bool {
            return ! $setting->exists
                && $setting->toleransi_keterlambatan_menit === 5
                && $setting->peringatan_keterlambatan_tinggi_menit === 10
                && $setting->warning_lebih_awal_aktif === true;
        });

    expect(DB::table('realisasi_ka_settings')->count())->toBe(0);
});
