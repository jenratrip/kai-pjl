<?php

use App\Models\ActivityLog;
use App\Models\Jpl;
use App\Models\Resort;
use App\Services\DefaultInventarisService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();

    Schema::table('jpl', function (Blueprint $table): void {
        $table->string('antara')->nullable();
        $table->string('km_hm')->nullable();
        $table->string('status_penjagaan')->nullable();
        $table->string('jenis_jalur_ka')->nullable();
        $table->string('konst_jalan')->nullable();
        $table->string('titik_koordinat')->nullable();
        $table->decimal('lebar_jln', 8, 2)->nullable();
        $table->string('kota_kabupaten')->nullable();
        $table->string('provinsi')->nullable();
        $table->string('lokasi')->nullable();
        $table->text('keterangan')->nullable();
    });
});

function createJplWorkbook(string $resortName, string $kodeJpl = '6.1'): string
{
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([
        [
            'NO JPL',
            'KM / HM',
            'ANTARA',
            'STATUS PENJAGAAN',
            'JENIS JALUR',
            'KONST JALAN',
            'Titik Koordinat',
            'LEBAR JLN (m)',
            'RESORT',
            'NAMA JLN / DAERAH',
            'KOTA / KABUPATEN',
        ],
        [$kodeJpl, '10+0', 'Stasiun A - Stasiun B', 'PT KAI - OP', 'Double', 'Beton', '0,0', 6, $resortName, 'Jalan Uji', 'Klaten'],
    ]);

    $path = tempnam(sys_get_temp_dir(), 'kai-jpl-import-');

    if ($path === false) {
        throw new RuntimeException('Workbook sementara tidak dapat dibuat.');
    }

    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

function uploadJplWorkbook($test, string $path): void
{
    $test->post(route('admin.jpl.import.preview.store'), [
        'file' => new UploadedFile(
            $path,
            'data-jpl.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        ),
    ])->assertRedirect(route('admin.jpl.import.preview'));
}

function runJplCsvImport(callable $callback): void
{
    $originalStoragePath = app()->storagePath();
    $temporaryStoragePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kai-jpl-csv-'.bin2hex(random_bytes(8));
    $importsPath = $temporaryStoragePath.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'imports';

    mkdir($importsPath, 0777, true);
    file_put_contents(
        $importsPath.DIRECTORY_SEPARATOR.'Data JPL.csv',
        "RESORT;ANTARA;KM / HM;NO JPL;NAMA JLN / DAERAH;KOTA / KABUPATEN;STATUS PENJAGAAN;JENIS JALUR KA;TITIK KOORDINAT\n"
        .'Resort Tersedia;Petak A;1+0;CSV-1;Jalan A;Klaten;PT KAI - OP;Double;0,0'."\n"
        .'Resort Baru;Petak B;2+0;CSV-2;Jalan B;Sleman;PT KAI - OP;Single;1,1'."\n"
    );

    app()->useStoragePath($temporaryStoragePath);

    try {
        $callback();
    } finally {
        app()->useStoragePath($originalStoragePath);
        unlink($importsPath.DIRECTORY_SEPARATOR.'Data JPL.csv');
        rmdir($importsPath);
        rmdir($temporaryStoragePath.DIRECTORY_SEPARATOR.'app');
        rmdir($temporaryStoragePath);
    }
}

test('Excel import creates a Resort and JPL without a Wilayah table', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-JPL-NEW-RESORT');
    $path = createJplWorkbook('Resort Baru');

    try {
        $this->actingAs($admin)
            ->get(route('admin.jpl.import'))
            ->assertOk()
            ->assertDontSee('name="wilayah_id"');

        uploadJplWorkbook($this, $path);

        $this->get(route('admin.jpl.import.preview'))
            ->assertOk()
            ->assertSee('Resort Baru')
            ->assertSee('Resort Baru', false);

        expect(ActivityLog::query()->count())->toBe(0);

        $this->mock(DefaultInventarisService::class)
            ->shouldReceive('initializeForJpl')
            ->once();

        $this->post(route('admin.jpl.import.confirm'))
            ->assertRedirect(route('admin.jpl.index'));
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }

    expect(Schema::hasTable('wilayah'))->toBeFalse()
        ->and(Resort::query()->where('nama_resort', 'Resort Baru')->count())->toBe(1);

    $this->assertDatabaseHas('jpl', [
        'kode_jpl' => '6.1',
        'resort_id' => Resort::query()->where('nama_resort', 'Resort Baru')->value('id'),
    ]);

    $activity = ActivityLog::query()->sole();
    expect($activity->module)->toBe('jpl')
        ->and($activity->action)->toBe('import')
        ->and($activity->new_values)->toBe([
            'imported_count' => 1,
            'error_count' => 0,
            'duplicate_count' => 0,
        ]);
});

test('Excel import reuses an existing Resort globally', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-JPL-EXISTING-RESORT');
    $resort = Resort::query()->create([
        'nama_resort' => 'Resort Tersedia',
        'is_active' => true,
    ]);
    $path = createJplWorkbook('Resort Tersedia');

    try {
        $this->actingAs($admin);
        uploadJplWorkbook($this, $path);

        $this->mock(DefaultInventarisService::class)
            ->shouldReceive('initializeForJpl')
            ->once();

        $this->post(route('admin.jpl.import.confirm'))
            ->assertRedirect(route('admin.jpl.index'));
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }

    expect(Resort::query()->count())->toBe(1);

    $this->assertDatabaseHas('jpl', [
        'kode_jpl' => '6.1',
        'resort_id' => $resort->id,
    ]);
});

test('Excel import rejects a Resort name that matches multiple existing records', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-JPL-AMBIGUOUS-RESORT');
    Resort::query()->create(['nama_resort' => 'Resort Sama', 'is_active' => true]);
    Resort::query()->create(['nama_resort' => ' Resort Sama ', 'is_active' => true]);
    $path = createJplWorkbook('Resort Sama');

    try {
        $this->actingAs($admin);
        uploadJplWorkbook($this, $path);

        $this->get(route('admin.jpl.import.preview'))
            ->assertOk()
            ->assertSee('Nama Resort cocok dengan lebih dari satu data');

        $this->post(route('admin.jpl.import.confirm'))
            ->assertRedirect(route('admin.jpl.import.preview'))
            ->assertSessionHas('error');
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }

    $this->assertDatabaseCount('jpl', 0);
});

test('CSV command reuses an active Resort and creates a missing Resort without Wilayah', function () {
    $existingResort = Resort::query()->create([
        'nama_resort' => 'Resort Tersedia',
        'is_active' => true,
    ]);

    runJplCsvImport(function () {
        expect(Artisan::call('jpl:import-csv'))->toBe(0);
    });

    expect(Schema::hasTable('wilayah'))->toBeFalse()
        ->and(Resort::query()->count())->toBe(2);

    $this->assertDatabaseHas('jpl', [
        'kode_jpl' => 'CSV-1',
        'resort_id' => $existingResort->id,
    ]);

    $newResort = Resort::query()->where('nama_resort', 'Resort Baru')->firstOrFail();

    $this->assertDatabaseHas('jpl', [
        'kode_jpl' => 'CSV-2',
        'resort_id' => $newResort->id,
    ]);
});

test('JPL can be added, edited and viewed while Superadmin remains read-only', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-JPL-CRUD');
    $resort = Resort::query()->create([
        'nama_resort' => 'Resort Operasional',
        'is_active' => true,
    ]);
    $legacyJpl = Jpl::query()->create([
        'kode_jpl' => 'OLD-1',
        'nama_jpl' => 'JPL Historis',
        'resort_id' => $resort->id,
        'is_active' => true,
    ]);

    $this->mock(DefaultInventarisService::class)
        ->shouldReceive('initializeForJpl')
        ->once();

    $this->actingAs($admin)
        ->post(route('admin.jpl.store'), [
            'kode_jpl' => 'NEW-1',
            'nama_jpl' => 'JPL Baru',
            'resort_id' => $resort->id,
            'is_active' => 1,
        ])
        ->assertRedirect(route('admin.jpl.index'));

    expect(ActivityLog::query()->where('action', 'create')->sole()->subject_id)
        ->toBe(Jpl::query()->where('kode_jpl', 'NEW-1')->value('id'));

    $this->put(route('admin.jpl.update', $legacyJpl->id), [
        'kode_jpl' => 'OLD-1',
        'nama_jpl' => 'JPL Historis Diperbarui',
        'resort_id' => $resort->id,
        'is_active' => 1,
    ])->assertRedirect(route('admin.jpl.index'));

    expect(ActivityLog::query()->where('action', 'update')->sole()->old_values['nama_jpl'])
        ->toBe('JPL Historis')
        ->and(ActivityLog::query()->where('action', 'update')->sole()->new_values['nama_jpl'])
        ->toBe('JPL Historis Diperbarui');

    $this->get(route('admin.jpl.show', $legacyJpl->id))
        ->assertOk()
        ->assertSee('JPL Historis Diperbarui')
        ->assertSee('Resort Operasional')
        ->assertDontSee('DAOP 6');

    $superadmin = $this->kaiUser($this->kaiRole('superadmin'), 'SUPERADMIN-JPL-READONLY');

    $this->actingAs($superadmin)
        ->get(route('superadmin.jpl.index'))
        ->assertOk()
        ->assertSee('Resort Operasional')
        ->assertDontSee('DAOP 6');
});

test('JPL activation and deactivation are logged only after status changes', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-JPL-ACTIVITY');
    $jpl = $this->kaiJpl('JPL-ACTIVITY');

    $this->actingAs($admin)
        ->delete(route('admin.jpl.destroy', $jpl->id))
        ->assertRedirect(route('admin.jpl.index'));

    $deactivation = ActivityLog::query()->sole();
    expect($deactivation->action)->toBe('deactivate')
        ->and($deactivation->module)->toBe('jpl')
        ->and($deactivation->old_values['is_active'])->toBeTrue()
        ->and($deactivation->new_values['is_active'])->toBeFalse();

    $this->post(route('admin.jpl.activate', $jpl->id))
        ->assertRedirect(route('admin.jpl.index'));

    expect(ActivityLog::query()->count())->toBe(2)
        ->and(ActivityLog::query()->where('action', 'activate')->sole()->subject_id)
        ->toBe($jpl->id);
});

test('failed JPL creation rolls back the business change and its activity log', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-JPL-ACTIVITY-ROLLBACK');
    $resort = Resort::query()->create([
        'nama_resort' => 'Resort Rollback',
        'is_active' => true,
    ]);

    $this->mock(DefaultInventarisService::class)
        ->shouldReceive('initializeForJpl')
        ->once()
        ->andThrow(new RuntimeException('Inventaris gagal disiapkan.'));

    expect(fn () => $this->withoutExceptionHandling()
        ->actingAs($admin)
        ->post(route('admin.jpl.store'), [
            'kode_jpl' => 'ROLLBACK-JPL',
            'nama_jpl' => 'JPL Rollback',
            'resort_id' => $resort->id,
            'is_active' => 1,
        ]))->toThrow(RuntimeException::class);

    expect(Jpl::query()->count())->toBe(0)
        ->and(ActivityLog::query()->count())->toBe(0);
});

test('JPL import activity summary rolls back when an imported row fails', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-JPL-IMPORT-ROLLBACK');
    $path = createJplWorkbook('Resort Transaksi', 'JPL-IMPORT-ROLLBACK');

    try {
        $this->actingAs($admin);
        uploadJplWorkbook($this, $path);

        $this->mock(DefaultInventarisService::class)
            ->shouldReceive('initializeForJpl')
            ->once()
            ->andThrow(new RuntimeException('Inventaris gagal disiapkan.'));

        $this->post(route('admin.jpl.import.confirm'))
            ->assertRedirect(route('admin.jpl.import.preview'))
            ->assertSessionHas('error');
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }

    expect(Jpl::query()->count())->toBe(0)
        ->and(ActivityLog::query()->count())->toBe(0);
});

test('JPL import logs one batch summary and rolls the summary back with failed imports', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-JPL-IMPORT-ROLLBACK');
    $path = createJplWorkbook('Resort Transaksi', 'JPL-IMPORT-ROLLBACK');

    try {
        $this->actingAs($admin);
        uploadJplWorkbook($this, $path);

        $this->mock(DefaultInventarisService::class)
            ->shouldReceive('initializeForJpl')
            ->once()
            ->andThrow(new RuntimeException('Inventaris gagal disiapkan.'));

        $this->post(route('admin.jpl.import.confirm'))
            ->assertRedirect(route('admin.jpl.import.preview'))
            ->assertSessionHas('error');
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }

    expect(Jpl::query()->count())->toBe(0)
        ->and(ActivityLog::query()->count())->toBe(0);
});
