<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class JplImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
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
            'PROVINSI',
        ];
    }

    /**
     * @return array<int, array<array-key, mixed>>
     */
    public function array(): array
    {
        return [];
    }
}
