<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class JplImport implements ToCollection, WithHeadingRow
{
    public array $rows = [];

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $row) {
            $row = $row instanceof Collection
                ? $row
                : collect($row);

            $resort = $this->value($row, 'resort');
            $antara = $this->value($row, 'antara');
            $kmHm = $this->value($row, 'km_hm');
            $kodeJpl = $this->value($row, 'no_jpl');
            $namaJpl = $this->value($row, 'nama_jln_daerah');

            /*
             * Abaikan baris yang benar-benar bukan data JPL.
             *
             * Dari struktur Excel yang dibaca sebelumnya,
             * terdapat baris yang hanya berisi informasi seperti
             * RESMI / LIAR pada kolom tambahan.
             *
             * Jika tiga field utama semuanya kosong,
             * baris tersebut tidak dianggap sebagai data JPL.
             */
            if (
                $resort === null &&
                $kodeJpl === null &&
                $namaJpl === null
            ) {
                continue;
            }

            $this->rows[] = [
                'row' => $index + 2,

                'resort' => $resort,
                'antara' => $antara,
                'km_hm' => $kmHm,

                'kode_jpl' => $kodeJpl,
                'nama_jpl' => $namaJpl,

                'kota_kabupaten' =>
                    $this->value($row, 'kota_kabupaten'),

                'provinsi' =>
                    $this->value($row, 'provinsi'),

                'jenis_jalur_ka' =>
                    $this->value($row, 'jenis_jalur_ka'),

                'titik_koordinat' =>
                    $this->value($row, 'titik_koordinat'),

                'status_penjagaan' =>
                    $this->value($row, 'status_penjagaan'),
            ];
        }
    }

    private function value(Collection|array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }
}