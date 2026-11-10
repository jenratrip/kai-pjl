<?php

use App\Imports\JadwalKaWorkbookImport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

test('admins download one correctly named template sheet per active JPL', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'A-TEMPLATE-1');
    $activeJpl = $this->kaiJpl('JPL 351');
    $inactiveJpl = $this->kaiJpl('JPL 352');
    $inactiveJpl->update(['is_active' => false]);

    $response = $this->actingAs($admin)
        ->get(route('admin.jadwal-ka.template'))
        ->assertOk()
        ->assertDownload('template-jadwal-ka.xlsx');

    $path = tempnam(sys_get_temp_dir(), 'kai-jadwal-ka-template-');
    file_put_contents($path, $response->streamedContent());
    $spreadsheet = null;

    try {
        $spreadsheet = IOFactory::load($path);
        expect($spreadsheet->getSheetNames())->toBe([$activeJpl->kode_jpl]);

        $worksheet = $spreadsheet->getSheetByName($activeJpl->kode_jpl);
        $worksheet->fromArray([
            ['KA-001', 'Kereta Uji', 'Stasiun A', 'Stasiun B', '10:30', 'contoh'],
        ], null, 'A2');

        $parsed = (new JadwalKaWorkbookImport)->parseSheet($worksheet);
        $headings = $worksheet->rangeToArray('A1:F1', null, true, false, false)[0];
        $spreadsheet->disconnectWorksheets();

        expect($headings)->toBe([
            'nomor_ka',
            'nama_ka',
            'dari',
            'ke',
            'jam_lewat',
            'keterangan',
        ])->and($parsed['header_found'])->toBeTrue()
            ->and($parsed['sheet'])->toBe($activeJpl->kode_jpl)
            ->and($parsed['rows'][0]['nomor_ka'])->toBe('KA-001')
            ->and($parsed['rows'][0]['nama_ka'])->toBe('Kereta Uji')
            ->and($parsed['rows'][0]['dari'])->toBe('Stasiun A')
            ->and($parsed['rows'][0]['ke'])->toBe('Stasiun B')
            ->and($parsed['rows'][0]['jam_lewat'])->toBe('10:30:00');
    } finally {
        $spreadsheet?->disconnectWorksheets();
        @unlink($path);
    }
});
