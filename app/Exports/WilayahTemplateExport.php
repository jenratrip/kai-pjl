<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class WilayahTemplateExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return [
            'nama_wilayah',
            'keterangan',
            'is_active',
        ];
    }

    public function array(): array
    {
        return [
            [
                'Wilayah 6.6 Klaten',
                'Contoh keterangan wilayah',
                1,
            ],
        ];
    }
}