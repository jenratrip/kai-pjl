<?php

use App\Models\RealisasiKa;
use App\Models\SerahTerima;
use App\Services\DinasJadwalSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

test('Serah Terima uses the Dinas snapshot and exact shift boundaries without added tolerance', function () {
    CarbonImmutable::setTestNow('2026-11-05 09:00:00');
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-ST-SHIFT');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift('08:00:00', '16:00:00');
    $train = $this->kaiTrain('SHIFT-BOUNDARY-KA');
    $dinas = $this->kaiDinas($user, $jpl, $shift, [
        'tanggal_dinas' => '2026-11-05',
    ]);

    $beforeShift = $this->kaiSchedule($jpl, $train, [
        'hari_berlaku' => ['Kamis'],
        'jam_lewat' => '07:59:00',
    ]);
    $atShiftStart = $this->kaiSchedule($jpl, $train, [
        'hari_berlaku' => ['Kamis'],
        'jam_lewat' => '08:00:00',
    ]);
    $atShiftEnd = $this->kaiSchedule($jpl, $train, [
        'hari_berlaku' => ['Kamis'],
        'jam_lewat' => '16:00:00',
    ]);
    $afterShift = $this->kaiSchedule($jpl, $train, [
        'hari_berlaku' => ['Kamis'],
        'jam_lewat' => '16:01:00',
    ]);

    $snapshot = app(DinasJadwalSnapshotService::class)->resolveAndStore((int) $dinas->id);

    foreach ([$beforeShift, $atShiftStart, $atShiftEnd, $afterShift] as $schedule) {
        RealisasiKa::query()->create([
            'dinas_id' => $dinas->id,
            'jadwal_ka_id' => $schedule->id,
            'kereta_api_id' => $train->id,
            'is_manual' => false,
            'status_lewat' => 'centang',
        ]);
    }

    expect(collect($snapshot['jadwal'])->pluck('jadwal_ka_id')->all())
        ->toBe([$atShiftStart->id, $atShiftEnd->id]);

    $this->actingAs($user)
        ->get(route('user.serah-terima-dinas'))
        ->assertOk()
        ->assertViewHas('dataRealisasiKA', function ($schedules) use ($atShiftStart, $atShiftEnd): bool {
            return $schedules->pluck('jadwal_ka_id')->all() === [$atShiftStart->id, $atShiftEnd->id];
        });
});

test('Serah Terima renders stored yes and no checks as symbols without changing values', function () {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'P-ST-INDICATORS');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $train = $this->kaiTrain('INDICATOR-KA');
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
                'jam_lewat' => '10:00:00',
            ]],
        ],
    ]);
    $snapshot = $dinas->jadwal_ka_snapshot;
    $snapshot['dinas_id'] = $dinas->id;
    $dinas->update(['jadwal_ka_snapshot' => $snapshot]);
    $realisasi = RealisasiKa::query()->create([
        'dinas_id' => $dinas->id,
        'jadwal_ka_id' => $schedule->id,
        'kereta_api_id' => $train->id,
        'status_s21' => 'centang',
        'status_genta' => 'silang',
        'status_pintu_perlintasan' => null,
        'status_s35' => 'centang',
        'is_manual' => false,
    ]);

    $this->actingAs($user)
        ->get(route('user.serah-terima-dinas'))
        ->assertOk()
        ->assertSee('✓')
        ->assertSee('✗')
        ->assertSee('Semboyan 21')
        ->assertSee('Pintu perlintasan');

    expect($realisasi->fresh()->status_s21)->toBe('centang')
        ->and($realisasi->fresh()->status_genta)->toBe('silang')
        ->and($realisasi->fresh()->status_pintu_perlintasan)->toBeNull();
});

test('Serah Terima indicator rendering supports old yes and no values and leaves empty values blank', function () {
    $html = view('user.serahterimacatatan.partials.ka-check-indicators', [
        'ka' => [
            'semboyan21' => 'ya',
            'genta' => 'tidak',
            'pintu' => '',
            'semboyan35' => null,
        ],
    ])->render();

    expect(substr_count($html, '✓'))->toBe(1)
        ->and(substr_count($html, '✗'))->toBe(1)
        ->and($html)->not->toContain('>ya<')
        ->not->toContain('>tidak<');
});

test('saved Serah Terima summary remains the source for KA history after schedule changes', function () {
    $role = $this->kaiRole('pjl');
    $user = $this->kaiUser($role, 'P-ST-1');
    $jpl = $this->kaiJpl();
    $shift = $this->kaiShift();
    $train = $this->kaiTrain('OLD-HISTORY');
    $schedule = $this->kaiSchedule($jpl, $train);
    $dinas = $this->kaiDinas($user, $jpl, $shift);
    $storedSummary = [
        'kereta_api' => [[
            'nomor' => 'OLD-HISTORY',
            'nama' => 'Nama saat serah terima',
            'jadwal_ka_id' => $schedule->id,
            'jadwal' => '10:00',
            'status' => 'JALAN',
        ]],
    ];
    $serahTerima = SerahTerima::query()->create([
        'dinas_id' => $dinas->id,
        'penerima_user_id' => $user->id,
        'ringkasan' => $storedSummary,
        'status' => 'selesai',
        'status_review' => 'disetujui',
    ]);

    $schedule->update(['jam_lewat' => '22:15:00']);
    $train->update(['nomor_ka' => 'NEW-CURRENT']);

    $response = $this->actingAs($user)
        ->get(route('user.serah-terima-dinas.show', $serahTerima));

    $response->assertOk()->assertViewHas('dataRealisasiKA', function (Collection $result): bool {
        return $result->first()['nomor'] === 'OLD-HISTORY'
            && $result->first()['nama'] === 'Nama saat serah terima'
            && $result->first()['jadwal'] === '10:00';
    });

    expect($response->viewData('dataRealisasiKA'))->toHaveCount(1);
});
