<?php

use App\Http\Controllers\Superadmin\SystemMonitoringController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function (): void {
    $this->createKaiInMemorySchema();
});

test('monitoring is restricted to authenticated superadmins', function (): void {
    $this->get(route('superadmin.monitoring.index'))->assertRedirect(route('login'));

    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-MONITORING');

    $this->actingAs($admin)
        ->get(route('superadmin.monitoring.index'))
        ->assertForbidden();
});

test('superadmin sees live health and integrity results', function (): void {
    $superadmin = $this->kaiUser($this->kaiRole('superadmin'), 'SUPERADMIN-MONITORING');
    $jpl = $this->kaiJpl('JPL-MONITORING');
    $shift = $this->kaiShift();
    $dinas = $this->kaiDinas($superadmin, $jpl, $shift);
    $dinas->forceFill([
        'jadwal_ka_snapshot' => [
            'format_version' => 1,
            'snapshot_at' => now()->toIso8601String(),
            'dinas_id' => $dinas->id,
            'jpl_id' => $jpl->id,
            'shift_id' => $shift->id,
            'tanggal_dinas' => $dinas->tanggal_dinas->toDateString(),
            'jadwal_ka_batch_id' => null,
            'source' => 'legacy',
            'jadwal' => [],
        ],
    ])->save();

    $this->withoutVite()
        ->actingAs($superadmin)
        ->get(route('superadmin.monitoring.index'))
        ->assertOk()
        ->assertViewIs('superadmin.monitoring.index')
        ->assertSeeText('Koneksi Database')
        ->assertSeeText('Storage')
        ->assertSeeText('Scheduler')
        ->assertSeeText('Queue')
        ->assertSeeText('Integritas Data')
        ->assertSeeText('1 Dinas memiliki snapshot; Dinas legacy tanpa snapshot tidak dianggap bermasalah.')
        ->assertSeeText('runtime belum terverifikasi')
        ->assertSeeText('Versi Laravel');
});

test('monitoring reports snapshot and schedule integrity issues', function (): void {
    $superadmin = $this->kaiUser($this->kaiRole('superadmin'), 'SUPERADMIN-INTEGRITY');
    $jpl = $this->kaiJpl('JPL-INTEGRITY');
    $train = $this->kaiTrain('KA-INTEGRITY');
    $batch = $this->kaiBatch('active', null);
    $schedule = $this->kaiSchedule($jpl, $train, ['jadwal_ka_batch_id' => $batch->id]);
    $dinas = $this->kaiDinas($superadmin, $jpl, $this->kaiShift());
    $dinas->forceFill([
        'jadwal_ka_snapshot' => [
            'format_version' => 1,
            'snapshot_at' => now()->toIso8601String(),
            'dinas_id' => $dinas->id,
            'jpl_id' => $jpl->id + 100,
            'tanggal_dinas' => $dinas->tanggal_dinas->toDateString(),
            'jadwal_ka_batch_id' => null,
            'source' => 'legacy',
            'jadwal' => [],
        ],
    ])->save();

    $this->withoutVite()
        ->actingAs($superadmin)
        ->get(route('superadmin.monitoring.index'))
        ->assertOk()
        ->assertSeeText('1 snapshot memiliki struktur atau konteks yang tidak konsisten.')
        ->assertSeeText('1 jadwal batch tidak memiliki cakupan JPL yang cocok.')
        ->assertSeeText('1 batch operasional tidak memiliki tanggal mulai berlaku.');

    expect($schedule->fresh()->jadwal_ka_batch_id)->toBe($batch->id);
});

test('monitoring resolves batch range identifiers and reports valid data as consistent', function (): void {
    $superadmin = $this->kaiUser($this->kaiRole('superadmin'), 'SUPERADMIN-VALID-BATCH');
    $jpl = $this->kaiJpl('JPL-VALID-BATCH');
    $train = $this->kaiTrain('KA-VALID-BATCH');
    $batch = $this->kaiBatch('active', now()->subDay()->toDateString());
    $batch->jpls()->sync([$jpl->id => ['tanggal_akhir_berlaku' => null]]);
    $this->kaiSchedule($jpl, $train, ['jadwal_ka_batch_id' => $batch->id]);

    $this->withoutVite()
        ->actingAs($superadmin)
        ->get(route('superadmin.monitoring.index'))
        ->assertOk()
        ->assertSeeText('Batch Jadwal KA')
        ->assertSeeText('Konsisten')
        ->assertSeeText('0 jadwal batch tidak memiliki cakupan JPL yang cocok.')
        ->assertDontSeeText('Pemeriksaan relasi atau rentang batch Jadwal KA gagal.');
});

test('monitoring reports a batch query failure instead of treating it as valid', function (): void {
    DB::connection()->beforeExecuting(function (string $query): void {
        if (str_contains($query, 'jadwal_ka') && str_contains(strtolower($query), 'left join')) {
            throw new RuntimeException('simulated read failure');
        }
    });

    $controller = app(SystemMonitoringController::class);
    $method = new ReflectionMethod($controller, 'checkJadwalIntegrity');
    $method->setAccessible(true);
    $result = $method->invoke($controller);

    expect($result['state'])->toBe('unknown')
        ->and($result['label'])->toBe('Tidak dapat diperiksa')
        ->and($result['summary'])->toBe('Pemeriksaan relasi atau rentang batch Jadwal KA gagal.');
});

test('error log output redacts credentials and bearer tokens', function (): void {
    $superadmin = $this->kaiUser($this->kaiRole('superadmin'), 'SUPERADMIN-LOGS');
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kai-pjl-monitoring-'.Str::random(12);
    File::ensureDirectoryExists($directory);
    $logPath = $directory.DIRECTORY_SEPARATOR.'laravel.log';
    $timestamp = now()->format('Y-m-d H:i:s');
    File::put(
        $logPath,
        "[{$timestamp}] local.ERROR: password=private-value Bearer bearer-value https://user:pass@example.test/?token=url-value\n",
    );
    config()->set('app.monitoring_log_directory', $directory);

    try {
        $response = $this->withoutVite()
            ->actingAs($superadmin)
            ->get(route('superadmin.monitoring.index'));

        $response->assertOk()
            ->assertSeeText('password=[disamarkan]')
            ->assertSeeText('Bearer [disamarkan]')
            ->assertDontSeeText('private-value')
            ->assertDontSeeText('bearer-value')
            ->assertDontSeeText('url-value')
            ->assertDontSeeText('user:pass');
    } finally {
        File::delete($logPath);
        File::deleteDirectory($directory);
    }
});

test('error monitoring reports event and fingerprint counts for a complete scan', function (): void {
    $superadmin = $this->kaiUser($this->kaiRole('superadmin'), 'SUPERADMIN-COMPLETE-LOGS');
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kai-pjl-monitoring-'.Str::random(12);
    File::ensureDirectoryExists($directory);
    $timestamp = now()->format('Y-m-d H:i:s');
    File::put(
        $directory.DIRECTORY_SEPARATOR.'laravel.log',
        "[{$timestamp}] local.ERROR: Repeated safe test failure\n[{$timestamp}] local.ERROR: Repeated safe test failure\n",
    );
    config()->set('app.monitoring_log_directory', $directory);

    try {
        $this->withoutVite()
            ->actingAs($superadmin)
            ->get(route('superadmin.monitoring.index'))
            ->assertOk()
            ->assertSeeText('2 kejadian')
            ->assertSeeText('1 kelompok unik')
            ->assertSeeText('Lengkap: seluruh 1 file log');
    } finally {
        File::deleteDirectory($directory);
    }
});

test('error monitoring marks a byte-limited scan as partial and shows a lower bound', function (): void {
    $superadmin = $this->kaiUser($this->kaiRole('superadmin'), 'SUPERADMIN-PARTIAL-LOGS');
    $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kai-pjl-monitoring-'.Str::random(12);
    File::ensureDirectoryExists($directory);
    $timestamp = now()->format('Y-m-d H:i:s');
    File::put(
        $directory.DIRECTORY_SEPARATOR.'laravel.log',
        str_repeat('x', 2_000_005)."\n[{$timestamp}] local.ERROR: Error after scan boundary\n",
    );
    config()->set('app.monitoring_log_directory', $directory);

    try {
        $this->withoutVite()
            ->actingAs($superadmin)
            ->get(route('superadmin.monitoring.index'))
            ->assertOk()
            ->assertSeeText('≥ 1 kejadian')
            ->assertSeeText('1 kelompok unik')
            ->assertSeeText('Parsial:')
            ->assertSeeText('ukuran satu atau lebih file melebihi batas 2 MB');
    } finally {
        File::deleteDirectory($directory);
    }
});
