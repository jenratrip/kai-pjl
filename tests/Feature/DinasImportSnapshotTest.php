<?php

use App\Models\ActivityLog;
use App\Models\Dinas;
use Illuminate\Http\UploadedFile;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

/**
 * @param  list<string>  $row
 */
function createDinasCsvUpload(array $row, string $extension = 'csv'): UploadedFile
{
    return createDinasCsvUploadWithRows([$row], $extension);
}

/**
 * @param  list<list<string>>  $rows
 */
function createDinasCsvUploadWithRows(array $rows, string $extension = 'csv'): UploadedFile
{
    $stream = fopen('php://temp', 'r+');

    if ($stream === false) {
        throw new RuntimeException('CSV sementara tidak dapat dibuat.');
    }

    fputcsv($stream, [
        'nip',
        'kode_jpl',
        'id_shift',
        'tanggal_dinas',
        'jam_mulai',
        'jam_selesai',
        'is_pengganti',
        'nip_digantikan',
    ], ';');
    foreach ($rows as $row) {
        fputcsv($stream, $row, ';');
    }
    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);

    return UploadedFile::fake()->createWithContent(
        "jadwal-dinas.{$extension}",
        $contents === false ? '' : $contents
    );
}

it('validates CSV and TXT imports through the shared server preview without writing data', function (string $extension) {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-PREVIEW-'.$extension);
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-PREVIEW-'.$extension);
    $jpl = $this->kaiJpl('JPL-PREVIEW-'.$extension);
    $shift = $this->kaiShift();

    $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => createDinasCsvUpload([
                $pjl->nip,
                $jpl->kode_jpl,
                (string) $shift->id,
                now()->toDateString(),
                '',
                '',
                '0',
                '',
            ], $extension),
        ])
        ->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('total_count', 1)
        ->assertJsonPath('valid_count', 1)
        ->assertJsonPath('failed_count', 0);

    $this->assertDatabaseCount('dinas', 0);
    $this->assertDatabaseCount('activity_logs', 0);
})->with(['csv', 'txt']);

it('reports server-side business validation errors for CSV rows without partial writes', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-PREVIEW-INVALID');
    $jpl = $this->kaiJpl('JPL-PREVIEW-INVALID');
    $shift = $this->kaiShift();

    $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.dinas.import.preview'), [
            'file' => createDinasCsvUpload([
                'NIP-TIDAK-TERDAFTAR',
                $jpl->kode_jpl,
                (string) $shift->id,
                now()->toDateString(),
                '',
                '',
                '0',
                '',
            ]),
        ])
        ->assertUnprocessable()
        ->assertJsonPath('valid', false)
        ->assertJsonPath('total_count', 1)
        ->assertJsonPath('valid_count', 0)
        ->assertJsonPath('failed_count', 1)
        ->assertJsonPath('row_errors.2', 'NIP petugas, kode JPL, atau shift tidak ditemukan atau tidak aktif.');

    $this->assertDatabaseCount('dinas', 0);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('Admin Dinas import creates and snapshots each new Dinas in the same transaction', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-IMPORT');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-IMPORT');
    $jpl = $this->kaiJpl('JPL-IMPORT-SNAPSHOT');
    $shift = $this->kaiShift();

    $this->actingAs($admin)
        ->post(route('admin.dinas.import'), [
            'file' => createDinasCsvUpload([
                $pjl->nip,
                $jpl->kode_jpl,
                (string) $shift->id,
                now()->toDateString(),
                '',
                '',
                '0',
                '',
            ]),
        ])
        ->assertRedirect(route('admin.dinas.index'))
        ->assertSessionHas('success', '1 jadwal dinas berhasil diimpor.');

    $dinas = Dinas::query()->sole();

    expect($dinas->jadwal_ka_snapshot)->toBeArray()
        ->and($dinas->jadwal_ka_snapshot['dinas_id'])->toBe($dinas->id)
        ->and($dinas->jadwal_ka_snapshot['jpl_id'])->toBe($jpl->id)
        ->and($dinas->jadwal_ka_snapshot['shift_id'])->toBe($shift->id)
        ->and($dinas->jadwal_ka_snapshot['tanggal_dinas'])->toBe(now()->toDateString())
        ->and($dinas->jadwal_ka_snapshot['jadwal'])->toBe([])
        ->and($dinas->jadwal_ka_batch_id)->toBeNull();

    $activity = ActivityLog::query()->sole();
    expect($activity->action)->toBe('import')
        ->and($activity->module)->toBe('dinas')
        ->and($activity->subject_type)->toBeNull()
        ->and($activity->subject_id)->toBeNull()
        ->and($activity->new_values)->toBe([
            'imported_count' => 1,
            'failed_count' => 0,
        ]);
});

test('CSV import rejects the whole file when any row is invalid', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-ALL-OR-NOTHING');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-ALL-OR-NOTHING');
    $jpl = $this->kaiJpl('JPL-ALL-OR-NOTHING');
    $shift = $this->kaiShift();
    $validRow = [
        $pjl->nip,
        $jpl->kode_jpl,
        (string) $shift->id,
        now()->toDateString(),
        '',
        '',
        '0',
        '',
    ];
    $invalidRow = [...$validRow];
    $invalidRow[0] = 'NIP-TIDAK-TERDAFTAR';

    $this->actingAs($admin)
        ->post(route('admin.dinas.import'), [
            'file' => createDinasCsvUploadWithRows([$validRow, $invalidRow]),
        ])
        ->assertRedirect()
        ->assertSessionHas('import_errors');

    $this->assertDatabaseCount('dinas', 0);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('Dinas import rolls back every new row when snapshot resolution fails', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-IMPORT-ROLLBACK');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-IMPORT-ROLLBACK');
    $jpl = $this->kaiJpl('JPL-IMPORT-ROLLBACK');
    $shift = $this->kaiShift();
    $batch = $this->kaiBatch('scheduled', null);
    $batch->jpls()->sync([$jpl->id]);

    $this->actingAs($admin)
        ->post(route('admin.dinas.import'), [
            'file' => createDinasCsvUpload([
                $pjl->nip,
                $jpl->kode_jpl,
                (string) $shift->id,
                now()->toDateString(),
                '',
                '',
                '0',
                '',
            ]),
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Impor gagal diproses. Periksa format file dan log aplikasi.');

    $this->assertDatabaseCount('dinas', 0);
    $this->assertDatabaseCount('activity_logs', 0);
});

test('Dinas CSV export does not synchronize or change Dinas status', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-DINAS-EXPORT');
    $pjl = $this->kaiUser($this->kaiRole('pjl'), 'PJL-DINAS-EXPORT');
    $jpl = $this->kaiJpl('JPL-EXPORT-READONLY');
    $shift = $this->kaiShift();
    $dinas = $this->kaiDinas($pjl, $jpl, $shift, [
        'status' => 'dijadwalkan',
        'jam_mulai' => now()->subMinute(),
        'jam_selesai' => now()->addMinute(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dinas.export'))
        ->assertOk();

    expect($dinas->fresh()->status)->toBe('dijadwalkan');
});
