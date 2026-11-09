<?php

use App\Models\ActivityLog;
use App\Models\Dinas;
use App\Models\RealisasiKa;
use App\Models\RealisasiKaSetting;
use App\Services\ActivityLogService;
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
        'save_mode' => 'row',
        'data' => [[
            'nomor' => $train->nomor_ka,
            'nama' => $train->nama_ka,
            'jadwal_ka_id' => $capturedSchedule->id,
            'is_manual' => false,
            'status' => 'JALAN',
            'aktual' => '10:01',
            'semboyan21' => 'yes',
            'genta' => 'no',
            'pintu' => 'yes',
            'semboyan35' => 'no',
            'keterangan' => 'Catatan operasional yang tidak boleh masuk log.',
        ]],
    ])->assertOk();

    expect(RealisasiKa::query()->where('jadwal_ka_id', $capturedSchedule->id)->exists())->toBeTrue();
    $savedRealisasi = RealisasiKa::query()->sole();
    $activity = ActivityLog::query()->sole();
    expect($activity->action)->toBe('create')
        ->and($activity->module)->toBe('realisasi_ka')
        ->and($activity->user_id)->toBe($user->id)
        ->and($activity->subject_type)->toBe(RealisasiKa::class)
        ->and($activity->subject_id)->toBe($savedRealisasi->id)
        ->and($activity->new_values)->toMatchArray([
            'jadwal_ka_id' => $capturedSchedule->id,
            'status_lewat' => 'centang',
            'jam_aktual' => '10:01',
            'status_s21' => 'centang',
            'status_genta' => 'silang',
            'status_pintu_perlintasan' => 'centang',
            'status_s35' => 'silang',
        ])
        ->and($activity->new_values)->not->toHaveKey('catatan')
        ->and(json_encode($activity->new_values))->not->toContain('Catatan operasional');

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
    expect(ActivityLog::query()->count())->toBe(1);
});

test('Simpan Semua writes one summary log for changed rows only', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-REAL-BATCH-LOG');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $firstTrain = $this->kaiTrain('BATCH-JALAN');
    $secondTrain = $this->kaiTrain('BATCH-BATAL');
    $firstSchedule = $this->kaiSchedule($jpl, $firstTrain, ['jam_lewat' => '10:00:00']);
    $secondSchedule = $this->kaiSchedule($jpl, $secondTrain, ['jam_lewat' => '10:10:00']);
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
            'jadwal' => [
                [
                    'jadwal_ka_id' => $firstSchedule->id,
                    'kereta_api_id' => $firstTrain->id,
                    'jpl_id' => $jpl->id,
                    'nomor_ka' => $firstTrain->nomor_ka,
                    'nama_ka' => $firstTrain->nama_ka,
                    'jam_lewat' => '10:00:00',
                ],
                [
                    'jadwal_ka_id' => $secondSchedule->id,
                    'kereta_api_id' => $secondTrain->id,
                    'jpl_id' => $jpl->id,
                    'nomor_ka' => $secondTrain->nomor_ka,
                    'nama_ka' => $secondTrain->nama_ka,
                    'jam_lewat' => '10:10:00',
                ],
            ],
        ],
    ]);
    $snapshot = $dinas->jadwal_ka_snapshot;
    $snapshot['dinas_id'] = $dinas->id;
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);

    $this->actingAs($user)->withSession(['dinas_id' => $dinas->id])->postJson(route('user.realisasi-ka.store'), [
        'save_mode' => 'batch',
        'data' => [
            [
                'nomor' => $firstTrain->nomor_ka,
                'jadwal_ka_id' => $firstSchedule->id,
                'is_manual' => false,
                'status' => 'JALAN',
                'semboyan21' => 'yes',
                'keterangan' => 'Catatan batch rahasia satu.',
            ],
            [
                'nomor' => $secondTrain->nomor_ka,
                'jadwal_ka_id' => $secondSchedule->id,
                'is_manual' => false,
                'status' => 'BATAL',
                'genta' => 'no',
                'keterangan' => 'Catatan batch rahasia dua.',
            ],
        ],
    ])->assertOk();

    $activity = ActivityLog::query()->sole();
    expect(RealisasiKa::query()->count())->toBe(2)
        ->and($activity->action)->toBe('bulk_update')
        ->and($activity->module)->toBe('realisasi_ka')
        ->and($activity->user_id)->toBe($user->id)
        ->and($activity->subject_type)->toBe(Dinas::class)
        ->and($activity->subject_id)->toBe($dinas->id)
        ->and($activity->new_values)->toBe([
            'submitted_count' => 2,
            'updated_count' => 2,
            'jalan_count' => 1,
            'batal_count' => 1,
            'status_reset_count' => 0,
            'indicator_rows_changed_count' => 2,
        ]);

    expect(json_encode($activity->new_values))
        ->not->toContain('Catatan batch rahasia');
});

test('a persisted status and indicator reset is logged once and an unchanged resave creates no duplicate', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-REAL-RESET-LOG');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $train = $this->kaiTrain('RESET-LOG-KA');
    $schedule = $this->kaiSchedule($jpl, $train);
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
            'jadwal' => [[
                'jadwal_ka_id' => $schedule->id,
                'kereta_api_id' => $train->id,
                'jpl_id' => $jpl->id,
                'nomor_ka' => $train->nomor_ka,
                'nama_ka' => $train->nama_ka,
                'jam_lewat' => $schedule->jam_lewat,
            ]],
        ],
    ]);
    $snapshot = $dinas->jadwal_ka_snapshot;
    $snapshot['dinas_id'] = $dinas->id;
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $saved = RealisasiKa::query()->create([
        'dinas_id' => $dinas->id,
        'jadwal_ka_id' => $schedule->id,
        'kereta_api_id' => $train->id,
        'status_lewat' => 'centang',
        'jam_aktual' => '10:01',
        'status_s21' => 'centang',
        'status_genta' => 'centang',
        'status_pintu_perlintasan' => 'centang',
        'status_s35' => 'centang',
        'is_manual' => false,
    ]);
    $payload = [
        'save_mode' => 'row',
        'data' => [[
            'nomor' => $train->nomor_ka,
            'jadwal_ka_id' => $schedule->id,
            'realisasi_ka_id' => $saved->id,
            'is_manual' => false,
            'status' => 'BELUM',
        ]],
    ];

    $this->actingAs($user)->withSession(['dinas_id' => $dinas->id])
        ->postJson(route('user.realisasi-ka.store'), $payload)
        ->assertOk();

    expect($saved->fresh()->status_lewat)->toBeNull()
        ->and($saved->fresh()->status_s21)->toBeNull()
        ->and($saved->fresh()->status_genta)->toBeNull()
        ->and($saved->fresh()->status_pintu_perlintasan)->toBeNull()
        ->and($saved->fresh()->status_s35)->toBeNull();

    $activity = ActivityLog::query()->sole();
    expect($activity->action)->toBe('update')
        ->and($activity->new_values)->toMatchArray([
            'status_lewat' => null,
            'status_s21' => null,
            'status_genta' => null,
            'status_pintu_perlintasan' => null,
            'status_s35' => null,
            'status_reset' => true,
        ]);

    $this->actingAs($user)->withSession(['dinas_id' => $dinas->id])
        ->postJson(route('user.realisasi-ka.store'), $payload)
        ->assertOk();

    expect(ActivityLog::query()->count())->toBe(1);
});

test('a Realisasi database failure does not create an Activity Log', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-REAL-FAIL-LOG');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $train = $this->kaiTrain('FAIL-LOG-KA');
    $schedule = $this->kaiSchedule($jpl, $train);
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
            'jadwal' => [[
                'jadwal_ka_id' => $schedule->id,
                'kereta_api_id' => $train->id,
                'jpl_id' => $jpl->id,
                'nomor_ka' => $train->nomor_ka,
                'nama_ka' => $train->nama_ka,
                'jam_lewat' => $schedule->jam_lewat,
            ]],
        ],
    ]);
    $snapshot = $dinas->jadwal_ka_snapshot;
    $snapshot['dinas_id'] = $dinas->id;
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    RealisasiKa::creating(function (): void {
        throw new RuntimeException('Simulated Realisasi persistence failure.');
    });

    $this->actingAs($user)->withSession(['dinas_id' => $dinas->id])->postJson(route('user.realisasi-ka.store'), [
        'save_mode' => 'row',
        'data' => [[
            'nomor' => $train->nomor_ka,
            'jadwal_ka_id' => $schedule->id,
            'is_manual' => false,
            'status' => 'JALAN',
        ]],
    ])->assertServerError();

    expect(RealisasiKa::query()->count())->toBe(0)
        ->and(ActivityLog::query()->count())->toBe(0);
});

test('an Activity Log failure rolls back a Realisasi row saved in the same transaction', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-REAL-AUDIT-ROLLBACK');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $train = $this->kaiTrain('AUDIT-ROLLBACK-KA');
    $schedule = $this->kaiSchedule($jpl, $train);
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
            'jadwal' => [[
                'jadwal_ka_id' => $schedule->id,
                'kereta_api_id' => $train->id,
                'jpl_id' => $jpl->id,
                'nomor_ka' => $train->nomor_ka,
                'nama_ka' => $train->nama_ka,
                'jam_lewat' => $schedule->jam_lewat,
            ]],
        ],
    ]);
    $snapshot = $dinas->jadwal_ka_snapshot;
    $snapshot['dinas_id'] = $dinas->id;
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $this->mock(ActivityLogService::class)
        ->shouldReceive('log')
        ->once()
        ->andThrow(new RuntimeException('Activity log unavailable.'));

    $this->actingAs($user)->withSession(['dinas_id' => $dinas->id])->postJson(route('user.realisasi-ka.store'), [
        'save_mode' => 'row',
        'data' => [[
            'nomor' => $train->nomor_ka,
            'jadwal_ka_id' => $schedule->id,
            'is_manual' => false,
            'status' => 'JALAN',
        ]],
    ])->assertServerError();

    expect(RealisasiKa::query()->count())->toBe(0)
        ->and(ActivityLog::query()->count())->toBe(0);
});

test('the view data retains every schedule in the Dinas snapshot and attaches realization by schedule identity', function (): void {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-REAL-PRINT-SHIFT');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift('23:00:00', '07:00:00');
    $train = $this->kaiTrain('KA-SHARED-IDENTITY');
    $recordedSchedule = $this->kaiSchedule($jpl, $train, [
        'jam_lewat' => '23:55:00',
        'keterangan' => 'KA Bersamaan pada jalur berlawanan',
    ]);
    $pendingSchedule = $this->kaiSchedule($jpl, $train, [
        'jam_lewat' => '00:10:00',
        'keterangan' => 'Informasi operasional biasa',
    ]);
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
    $snapshot['jadwal'] = [
        [
            'jadwal_ka_id' => $recordedSchedule->id,
            'kereta_api_id' => $train->id,
            'jpl_id' => $jpl->id,
            'nomor_ka' => $train->nomor_ka,
            'nama_ka' => $train->nama_ka,
            'jam_lewat' => '23:55:00',
        ],
        [
            'jadwal_ka_id' => $pendingSchedule->id,
            'kereta_api_id' => $train->id,
            'jpl_id' => $jpl->id,
            'nomor_ka' => $train->nomor_ka,
            'nama_ka' => $train->nama_ka,
            'jam_lewat' => '00:10:00',
        ],
    ];
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    RealisasiKa::query()->create([
        'dinas_id' => $dinas->id,
        'jadwal_ka_id' => $recordedSchedule->id,
        'kereta_api_id' => $train->id,
        'status_lewat' => 'centang',
        'jam_aktual' => '23:57:00',
        'is_manual' => false,
    ]);

    $this->actingAs($user)
        ->withSession(['dinas_id' => $dinas->id])
        ->get(route('user.realisasi-ka'))
        ->assertOk()
        ->assertViewHas('dataKA', function (array $data) use ($recordedSchedule, $pendingSchedule): bool {
            $byScheduleId = collect($data)->keyBy('jadwal_ka_id');

            return $byScheduleId->count() === 2
                && $byScheduleId->get($recordedSchedule->id)['status'] === 'JALAN'
                && $byScheduleId->get($recordedSchedule->id)['aktual'] === '23:57'
                && $byScheduleId->get($recordedSchedule->id)['realisasi_ka_id'] !== null
                && $byScheduleId->get($recordedSchedule->id)['is_bersamaan'] === true
                && $byScheduleId->get($pendingSchedule->id)['status'] === 'BELUM'
                && $byScheduleId->get($pendingSchedule->id)['realisasi_ka_id'] === null
                && $byScheduleId->get($pendingSchedule->id)['is_bersamaan'] === false;
        });
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

test('Realisasi view passes the current Admin lateness settings to the browser', function (): void {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-SETTINGS-CURRENT');
    $dinas = $this->kaiDinas($user, $this->kaiJpl(), $this->kaiShift());
    RealisasiKaSetting::query()->create([
        'toleransi_keterlambatan_menit' => 7,
        'peringatan_keterlambatan_tinggi_menit' => 12,
        'warning_keterlambatan_aktif' => true,
        'warning_keterlambatan_tinggi_aktif' => true,
    ]);

    $this->actingAs($user)
        ->withSession(['dinas_id' => $dinas->id])
        ->get(route('user.realisasi-ka'))
        ->assertOk()
        ->assertSee('toleransiKeterlambatanMenit: 7', false)
        ->assertSee('peringatanKeterlambatanTinggiMenit: 12', false)
        ->assertSee('warningKeterlambatanAktif: true', false);
});
