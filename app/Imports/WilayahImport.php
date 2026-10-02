<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class WilayahImport implements ToCollection, WithHeadingRow
{
    public array $rows = [];

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {

            $excelRow = $index + 2;

            $this->rows[] = [
                'row' => $excelRow,

                'nama_wilayah' => trim(
                    (string) ($row['nama_wilayah'] ?? '')
                ),

                'keterangan' => trim(
                    (string) ($row['keterangan'] ?? '')
                ),

                'is_active' => $row['is_active'] ?? null,
            ];
        }
    }
}