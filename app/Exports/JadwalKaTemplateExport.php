<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class JadwalKaTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return [
            'nomor_ka',
            'kode_jpl',
            'jam_lewat',
            'hari_berlaku',
            'tanggal_berlaku',
            'keterangan',
            'is_active',
        ];
    }
}