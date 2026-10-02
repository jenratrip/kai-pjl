<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UserImportTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    /**
     * Header kolom Excel.
     */
    public function headings(): array
    {
        return [
            'NIP',
            'Nama',
            'Email',
            'No HP',
            'Role',
            'Status',
        ];
    }

    /**
     * Contoh data.
     */
    public function array(): array
    {
        return [
            [
                '0000000003',
                'Contoh Nama',
                'contoh@kai.co.id',
                '081234567890',
                'pjl',
                'Aktif',
            ],
        ];
    }
}