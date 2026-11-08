<?php

use App\Models\JadwalKa;
use App\Models\JadwalKaBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

/**
 * @param  list<array<string>>  $scheduleRows
 * @return array{file: UploadedFile, path: string}
 */
function createJadwalKaWorkbookUpload(string $sheetTitle, array $scheduleRows): array
{
    $temporaryFile = tempnam(sys_get_temp_dir(), 'kai-jadwal-');

    if ($temporaryFile === false) {
        throw new RuntimeException('File sementara untuk workbook tidak dapat dibuat.');
    }

    $xlsxPath = $temporaryFile.'.xlsx';
    @unlink($temporaryFile);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($sheetTitle);
    $sheet->fromArray(array_merge([
        ['Nomor KA', 'Nama KA', 'Dari', 'Ke', 'Jam lewat', 'Keterangan'],
    ], $scheduleRows));

    (new Xlsx($spreadsheet))->save($xlsxPath);
    $spreadsheet->disconnectWorksheets();

    return [
        'file' => new UploadedFile(
            $xlsxPath,
            'jadwal.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        ),
        'path' => $xlsxPath,
    ];
}

test('confirming a workbook creates a draft without changing the currently active schedule', function () {
    Storage::fake('local');
    $adminRole = $this->kaiRole('admin');
    $admin = $this->kaiUser($adminRole, 'A-1');
    $jpl = $this->kaiJpl();
    $train = $this->kaiTrain('OLD-1');
    $oldSchedule = $this->kaiSchedule($jpl, $train, ['is_active' => true]);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle($jpl->kode_jpl);
    $sheet->fromArray([
        ['Nomor KA', 'Nama KA', 'Dari', 'Ke', 'Jam lewat', 'Keterangan'],
        ['NEW-1', 'Kereta Baru', 'Stasiun C', 'Stasiun D', '10:30', 'uji draft'],
    ]);

    $temporaryPath = tempnam(sys_get_temp_dir(), 'kai-jadwal-').'.xlsx';
    (new Xlsx($spreadsheet))->save($temporaryPath);
    $spreadsheet->disconnectWorksheets();

    $uploadedFile = new UploadedFile(
        $temporaryPath,
        'jadwal.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    $preview = $this->actingAs($admin)->post(route('admin.jadwal-ka.import.preview'), [
        'tanggal_berlaku' => now()->toDateString(),
        'file' => $uploadedFile,
    ]);

    $preview->assertOk();
    $token = $preview->viewData('token');

    $this->post(route('admin.jadwal-ka.import.confirm'), ['token' => $token])
        ->assertRedirect(route('admin.jadwal-ka.index'));

    $batch = JadwalKaBatch::query()->firstOrFail();
    $draftSchedule = JadwalKa::query()->where('jadwal_ka_batch_id', $batch->id)->firstOrFail();

    expect($batch->status)->toBe('draft')
        ->and($draftSchedule->is_active)->toBeFalse()
        ->and($draftSchedule->jpl_id)->toBe($jpl->id)
        ->and($batch->jpls()->whereKey($jpl->id)->exists())->toBeTrue()
        ->and($oldSchedule->fresh()->is_active)->toBeTrue()
        ->and(JadwalKa::query()->count())->toBe(2);

    @unlink($temporaryPath);
});

test('duplicate rows in the same workbook create only one schedule', function () {
    Storage::fake('local');
    $admin = $this->kaiUser($this->kaiRole('admin'), 'A-DUP-1');
    $jpl = $this->kaiJpl();
    $row = ['DUP-1', 'Kereta Duplikat', 'Stasiun A', 'Stasiun B', '10:30', 'uji duplikat'];
    $upload = createJadwalKaWorkbookUpload($jpl->kode_jpl, [$row, $row]);

    try {
        $preview = $this->actingAs($admin)->post(route('admin.jadwal-ka.import.preview'), [
            'tanggal_berlaku' => '2026-11-05',
            'file' => $upload['file'],
        ]);

        $preview->assertOk();
        expect($preview->viewData('duplicateCount'))->toBe(1);

        $this->post(route('admin.jadwal-ka.import.confirm'), [
            'token' => $preview->viewData('token'),
        ])->assertRedirect(route('admin.jadwal-ka.index'));

        expect(JadwalKa::query()->count())->toBe(1)
            ->and(JadwalKaBatch::query()->firstOrFail()->status)->toBe('draft');
    } finally {
        @unlink($upload['path']);
    }
});

test('the same schedule can be imported into a new batch without changing the active batch', function () {
    Storage::fake('local');
    $admin = $this->kaiUser($this->kaiRole('admin'), 'A-VERSION-1');
    $jpl = $this->kaiJpl();
    $effectiveDate = '2026-11-05';
    $oldBatch = $this->kaiBatch('active', '2026-10-01');
    $oldBatch->jpls()->sync([
        $jpl->id => ['tanggal_akhir_berlaku' => null],
    ]);
    $oldSchedule = $this->kaiSchedule(
        $jpl,
        $this->kaiTrain('SAME-1'),
        [
            'jadwal_ka_batch_id' => $oldBatch->id,
            'tanggal_berlaku' => $effectiveDate,
            'jam_lewat' => '10:30:00',
            'is_active' => true,
        ]
    );
    $upload = createJadwalKaWorkbookUpload($jpl->kode_jpl, [[
        'SAME-1',
        'Kereta SAME-1',
        'Stasiun A',
        'Stasiun B',
        '10:30',
        'jadwal versi baru',
    ]]);

    try {
        $preview = $this->actingAs($admin)->post(route('admin.jadwal-ka.import.preview'), [
            'tanggal_berlaku' => $effectiveDate,
            'file' => $upload['file'],
        ]);

        $preview->assertOk();
        expect($preview->viewData('duplicateCount'))->toBe(0);

        $this->post(route('admin.jadwal-ka.import.confirm'), [
            'token' => $preview->viewData('token'),
        ])->assertRedirect(route('admin.jadwal-ka.index'));

        $newBatch = JadwalKaBatch::query()
            ->where('id', '!=', $oldBatch->id)
            ->firstOrFail();
        $newSchedule = JadwalKa::query()
            ->where('jadwal_ka_batch_id', $newBatch->id)
            ->firstOrFail();

        expect($newBatch->status)->toBe('draft')
            ->and($newSchedule->id)->not->toBe($oldSchedule->id)
            ->and($newSchedule->is_active)->toBeFalse()
            ->and($oldBatch->fresh()->status)->toBe('active')
            ->and($oldSchedule->fresh()->is_active)->toBeTrue()
            ->and($oldBatch->jpls()->whereKey($jpl->id)->first()->pivot->tanggal_akhir_berlaku)->toBeNull()
            ->and(JadwalKa::query()->count())->toBe(2);
    } finally {
        @unlink($upload['path']);
    }
});
