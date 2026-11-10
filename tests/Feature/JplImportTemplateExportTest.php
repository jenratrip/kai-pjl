<?php

use App\Imports\JplImport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

test('Admin JPL template headers are recognized by the JPL importer', function () {
    $admin = $this->kaiUser($this->kaiRole('admin'), 'ADMIN-JPL-TEMPLATE');

    $response = $this->actingAs($admin)
        ->get(route('admin.jpl.import.template'))
        ->assertOk()
        ->assertDownload('template-import-jpl.xlsx');

    $path = tempnam(sys_get_temp_dir(), 'kai-jpl-template-');
    file_put_contents($path, $response->streamedContent());
    $spreadsheet = null;

    try {
        $spreadsheet = IOFactory::load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->fromArray([
            [
                'JPL-TEST-01',
                '10+0',
                'Stasiun A - Stasiun B',
                'PT KAI - OP',
                'Double Track',
                'Aspal',
                '0,0',
                6,
                'Resort Contoh',
                'Jalan Contoh',
                'Klaten',
                'Jawa Tengah',
            ],
        ], null, 'A2');

        $import = new JplImport;
        $import->collection(collect(
            $worksheet->toArray(null, true, false, false)
        ));

        expect($import->rows)->toHaveCount(1)
            ->and($import->rows[0])->toMatchArray([
                'kode_jpl' => 'JPL-TEST-01',
                'km_hm' => '10+0',
                'antara' => 'Stasiun A - Stasiun B',
                'status_penjagaan' => 'PT KAI - OP',
                'jenis_jalur_ka' => 'Double Track',
                'konst_jalan' => 'Aspal',
                'resort' => 'Resort Contoh',
                'nama_jpl' => 'Jalan Contoh',
                'kota_kabupaten' => 'Klaten',
                'provinsi' => 'Jawa Tengah',
            ]);
    } finally {
        $spreadsheet?->disconnectWorksheets();
        @unlink($path);
    }
});
