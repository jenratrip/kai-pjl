<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class JadwalKaTemplateSheetExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(private readonly string $sheetTitle) {}

    public function title(): string
    {
        return $this->sheetTitle;
    }

    /**
     * @return array<int, array<array-key, mixed>>
     */
    public function array(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'nomor_ka',
            'nama_ka',
            'dari',
            'ke',
            'jam_lewat',
            'keterangan',
        ];
    }
}
