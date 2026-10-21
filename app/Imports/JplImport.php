<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use RuntimeException;

class JplImport implements ToCollection
{
    public array $rows = [];

    /**
     * Informasi hasil deteksi Excel.
     */
    public array $detectedColumns = [];

    public array $warnings = [];

    public ?int $headerRow = null;

    public ?int $dataStartRow = null;

    /**
     * Variasi nama header yang dikenali.
     */
    private array $aliases = [
        'kode_jpl' => [
            'no jpl',
            'nomor jpl',
            'kode jpl',
            'no perlintasan',
        ],

        'km_hm' => [
            'km hm',
            'km hm ka',
            'kilometer hektometer',
        ],

        'antara' => [
            'antara',
            'antara stasiun',
            'petak jalan',
            'petak',
        ],

        'status_penjagaan' => [
            'status penjagaan',
            'penjagaan',
        ],

        'jenis_jalur_ka' => [
            'jenis jalur',
            'jenis jalur ka',
            'jalur ka',
        ],

        'konst_jalan' => [
            'konst jalan',
            'konstruksi jalan',
            'konstruksi jln',
        ],

        'titik_koordinat' => [
            'titik koordinat',
            'koordinat',
            'gps',
            'latitude longitude',
        ],

        'lebar_jln' => [
            'lebar jln m',
            'lebar jalan m',
            'lebar jln',
            'lebar jalan',
        ],

        'resort' => [
            'resort',
            'nama resort',
        ],

        'nama_jpl' => [
            'nama jln daerah',
            'nama jalan daerah',
            'nama jln',
            'nama jalan',
            'nama jpl',
        ],

        'kota_kabupaten' => [
            'kota kabupaten',
            'kota kab',
            'kabupaten kota',
            'kabupaten',
        ],

        'provinsi' => [
            'provinsi',
            'propinsi',
        ],
    ];

    /**
     * Subkolom status penjagaan.
     *
     * Label menggunakan nama kategori Excel
     * agar tidak mengubah makna operasional.
     */
    private array $statusAliases = [
        'PT KAI - OP' => [
            'op',
        ],

        'PT KAI - JJ' => [
            'jj',
        ],

        'Pihak Lain - PEMDA' => [
            'pemda',
        ],

        'Pihak Lain - PIHAK LUAR' => [
            'pihak luar',
        ],

        'Tidak Dijaga' => [
            'tidak dijaga',
        ],

        'Liar' => [
            'liar',
        ],
    ];

    /**
     * Membaca worksheet Excel.
     */
    public function collection(Collection $collection): void
    {
        $this->rows = [];
        $this->detectedColumns = [];
        $this->warnings = [];
        $this->headerRow = null;
        $this->dataStartRow = null;

        $sheetRows = $collection
            ->map(function ($row) {
                return $row instanceof Collection
                    ? $row->values()->all()
                    : array_values((array) $row);
            })
            ->values();

        if ($sheetRows->isEmpty()) {
            throw new RuntimeException(
                'File Excel tidak memiliki data.'
            );
        }

        /**
         * Cari struktur header.
         */
        $header = $this->detectHeader($sheetRows);

        if ($header === null) {
            throw new RuntimeException(
                'Format Excel JPL tidak dikenali. '
                . 'Pastikan terdapat header RESORT '
                . 'dan NO JPL atau NOMOR JPL.'
            );
        }

        $columnMap = $header['columns'];
        $statusMap = $header['status_columns'];

        $this->headerRow = $header['row'] + 1;
        $this->detectedColumns = $columnMap;

        /**
         * Tentukan awal data.
         *
         * Header bertingkat dapat berisi
         * beberapa baris setelah header utama.
         */
        $startIndex = $this->detectDataStart(
            $sheetRows,
            $header['end_row'] + 1,
            $columnMap
        );

        if ($startIndex === null) {
            throw new RuntimeException(
                'Header Excel ditemukan, tetapi '
                . 'baris data JPL tidak ditemukan.'
            );
        }

        $this->dataStartRow = $startIndex + 1;

        /**
         * Catat kolom yang belum ditemukan.
         */
        foreach (array_keys($this->aliases) as $field) {
            if (
                ! array_key_exists($field, $columnMap)
                && ! (
                    $field === 'status_penjagaan'
                    && ! empty($statusMap)
                )
            ) {
                $this->warnings[] =
                    "Kolom {$field} tidak ditemukan dalam Excel.";
            }
        }

        /**
         * Baca seluruh baris data.
         */
        for (
            $index = $startIndex;
            $index < $sheetRows->count();
            $index++
        ) {
            $rawRow = $sheetRows[$index];

            /**
             * Lewati header berulang.
             */
            if ($this->isRepeatedHeader(
                $rawRow,
                $columnMap
            )) {
                continue;
            }

            $data = $this->mapRow(
                $rawRow,
                $columnMap,
                $statusMap
            );

            /**
             * Abaikan baris yang benar-benar
             * tidak memiliki data JPL.
             */
            if ($this->isEmptyRow($data)) {
                continue;
            }

            /**
             * Baris pemisah yang hanya berisi
             * nol pada beberapa kolom.
             */
            if ($this->isZeroSeparator($data)) {
                continue;
            }

            /**
             * Nomor baris asli Excel.
             */
            $data['row'] = $index + 1;

            $this->rows[] = $data;
        }

        if (empty($this->rows)) {
            throw new RuntimeException(
                'Tidak ditemukan baris JPL yang '
                . 'dapat dibaca dari Excel.'
            );
        }
    }

    /**
     * Deteksi header sederhana maupun bertingkat.
     *
     * Mencoba kombinasi 1 sampai 5 baris.
     */
    private function detectHeader(
        Collection $sheetRows
    ): ?array {
        $bestMatch = null;
        $bestScore = -1;

        $maxScan = min(
            $sheetRows->count(),
            30
        );

        for ($start = 0; $start < $maxScan; $start++) {
            for ($depth = 1; $depth <= 5; $depth++) {
                $end = $start + $depth - 1;

                if ($end >= $sheetRows->count()) {
                    break;
                }

                $headerRows = [];

                for ($i = $start; $i <= $end; $i++) {
                    $headerRows[] = $sheetRows[$i];
                }

                $columnMap = $this->detectColumns(
                    $headerRows
                );

                /**
                 * Dua header utama wajib ada.
                 */
                if (
                    ! isset($columnMap['resort'])
                    || ! isset($columnMap['kode_jpl'])
                ) {
                    continue;
                }

                $statusMap = $this->detectStatusColumns(
                    $headerRows
                );

                /**
                 * Lebih banyak kolom dikenali
                 * berarti kecocokan lebih baik.
                 */
                $score =
                    count($columnMap) * 10
                    + count($statusMap);

                if ($score > $bestScore) {
                    $bestScore = $score;

                    $bestMatch = [
                        'row' => $start,
                        'end_row' => $end,
                        'columns' => $columnMap,
                        'status_columns' => $statusMap,
                    ];
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Deteksi posisi kolom dari
     * beberapa baris header.
     */
    private function detectColumns(
        array $headerRows
    ): array {
        $columnMap = [];

        foreach ($headerRows as $row) {
            foreach ($row as $columnIndex => $heading) {
                $normalizedHeading = $this->normalizeHeader(
                    $heading
                );

                if ($normalizedHeading === '') {
                    continue;
                }

                foreach ($this->aliases as $field => $aliases) {
                    foreach ($aliases as $alias) {
                        if (
                            $normalizedHeading ===
                            $this->normalizeHeader($alias)
                        ) {
                            if (
                                ! array_key_exists(
                                    $field,
                                    $columnMap
                                )
                            ) {
                                $columnMap[$field] =
                                    $columnIndex;
                            }

                            break 2;
                        }
                    }
                }
            }
        }

        return $columnMap;
    }

    /**
     * Deteksi subkolom status penjagaan.
     *
     * Tidak menggunakan posisi tetap,
     * sehingga urutan kolom boleh berubah.
     */
    private function detectStatusColumns(
        array $headerRows
    ): array {
        $statusMap = [];

        /**
         * Ambil seluruh kolom dan label
         * yang ditemukan pada area header.
         */
        foreach ($headerRows as $row) {
            foreach ($row as $columnIndex => $heading) {
                $heading = $this->normalizeHeader(
                    $heading
                );

                if ($heading === '') {
                    continue;
                }

                foreach (
                    $this->statusAliases as $status => $aliases
                ) {
                    foreach ($aliases as $alias) {
                        if (
                            $heading ===
                            $this->normalizeHeader($alias)
                        ) {
                            $statusMap[$status] =
                                $columnIndex;

                            break 2;
                        }
                    }
                }
            }
        }

        return $statusMap;
    }

    /**
     * Cari baris pertama yang terlihat
     * sebagai data JPL.
     */
    private function detectDataStart(
        Collection $sheetRows,
        int $startIndex,
        array $columnMap
    ): ?int {
        for (
            $index = $startIndex;
            $index < $sheetRows->count();
            $index++
        ) {
            $row = $sheetRows[$index];

            $kodeJpl = $this->cellValue(
                $row,
                $columnMap,
                'kode_jpl'
            );

            $kmHm = $this->cellValue(
                $row,
                $columnMap,
                'km_hm'
            );

            $namaJpl = $this->cellValue(
                $row,
                $columnMap,
                'nama_jpl'
            );

            /**
             * NO JPL bukan nol dan bukan header.
             */
            $hasKode = $kodeJpl !== null
                && $kodeJpl !== '0'
                && $this->normalizeHeader($kodeJpl)
                    !== 'no jpl';

            /**
             * KM/HM dengan pola seperti 482+679.
             */
            $hasKm = $kmHm !== null
                && preg_match(
                    '/^\d+\s*\+\s*\d+$/',
                    $kmHm
                );

            /**
             * Nama jalan sebagai indikator tambahan.
             */
            $hasNama = $namaJpl !== null
                && $namaJpl !== '0'
                && $this->normalizeHeader($namaJpl)
                    !== 'nama jln daerah';

            if ($hasKode || $hasKm || $hasNama) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Normalisasi header.
     */
    private function normalizeHeader($value): string
    {
        if ($value === null) {
            return '';
        }

        $value = Str::lower(
            trim((string) $value)
        );

        $value = preg_replace(
            '/[^a-z0-9]+/u',
            ' ',
            $value
        );

        return trim(
            preg_replace('/\s+/', ' ', $value)
        );
    }

    /**
     * Ambil nilai sel berdasarkan
     * hasil deteksi kolom.
     */
    private function cellValue(
        array $row,
        array $columnMap,
        string $field
    ): ?string {
        if (! array_key_exists(
            $field,
            $columnMap
        )) {
            return null;
        }

        $columnIndex = $columnMap[$field];

        $value = $row[$columnIndex] ?? null;

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }

    /**
     * Memeriksa apakah sel berisi
     * penanda seperti V atau centang.
     */
    private function isMarked($value): bool
    {
        if ($value === null) {
            return false;
        }

        $value = mb_strtoupper(
            trim((string) $value)
        );

        return in_array(
            $value,
            [
                'V',
                '✓',
                '✔',
                '√',
                'X',
                '1',
                'YA',
                'YES',
            ],
            true
        );
    }

    /**
     * Membaca status penjagaan
     * berdasarkan subkolom bertanda.
     */
    private function resolveStatus(
        array $row,
        array $statusMap
    ): ?string {
        $statuses = [];

        foreach ($statusMap as $label => $columnIndex) {
            $value = $row[$columnIndex] ?? null;

            if ($this->isMarked($value)) {
                $statuses[] = $label;
            }
        }

        if (empty($statuses)) {
            return null;
        }

        return implode(', ', $statuses);
    }

    /**
     * Pemetaan Excel ke struktur
     * database JPL.
     */
    private function mapRow(
        array $row,
        array $columnMap,
        array $statusMap
    ): array {
        $status = $this->cellValue(
            $row,
            $columnMap,
            'status_penjagaan'
        );

        /**
         * Jika tidak ada status langsung,
         * baca tanda pada subkolom.
         */
        if ($status === null && ! empty($statusMap)) {
            $status = $this->resolveStatus(
                $row,
                $statusMap
            );
        }

        return [
            'kode_jpl' => $this->cellValue(
                $row, $columnMap, 'kode_jpl'
            ),

            'km_hm' => $this->cellValue(
                $row, $columnMap, 'km_hm'
            ),

            'antara' => $this->cellValue(
                $row, $columnMap, 'antara'
            ),

            'status_penjagaan' => $status,

            'jenis_jalur_ka' => $this->cellValue(
                $row, $columnMap, 'jenis_jalur_ka'
            ),

            'konst_jalan' => $this->cellValue(
                $row, $columnMap, 'konst_jalan'
            ),

            'titik_koordinat' => $this->cellValue(
                $row, $columnMap, 'titik_koordinat'
            ),

            'lebar_jln' => $this->cellValue(
                $row, $columnMap, 'lebar_jln'
            ),

            'resort' => $this->cellValue(
                $row, $columnMap, 'resort'
            ),

            'nama_jpl' => $this->cellValue(
                $row, $columnMap, 'nama_jpl'
            ),

            'kota_kabupaten' => $this->cellValue(
                $row, $columnMap, 'kota_kabupaten'
            ),

            'provinsi' => $this->cellValue(
                $row, $columnMap, 'provinsi'
            ),
        ];
    }

    /**
     * Apakah semua atribut JPL kosong?
     */
    private function isEmptyRow(array $data): bool
    {
        foreach ($data as $value) {
            if (
                $value !== null
                && trim((string) $value) !== ''
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Abaikan baris pemisah yang
     * hanya berisi nol.
     */
    private function isZeroSeparator(
        array $data
    ): bool {
        $hasValue = false;

        foreach ($data as $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $hasValue = true;

            if (trim((string) $value) !== '0') {
                return false;
            }
        }

        return $hasValue;
    }

    /**
     * Deteksi header berulang.
     */
    private function isRepeatedHeader(
        array $row,
        array $columnMap
    ): bool {
        $matches = 0;

        foreach ($columnMap as $field => $index) {
            $value = $this->normalizeHeader(
                $row[$index] ?? null
            );

            foreach (
                $this->aliases[$field] ?? [] as $alias
            ) {
                if (
                    $value !== ''
                    && $value === $this->normalizeHeader(
                        $alias
                    )
                ) {
                    $matches++;
                    break;
                }
            }
        }

        return $matches >= 2;
    }
}
