<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class DinasRosterWorkbookParser
{
    private const HEADER_ROW = 6;

    private const DATE_ROW = 7;

    private const FIRST_DATA_ROW = 8;

    private const FIRST_DATE_COLUMN = 5;

    private const LAST_DATE_COLUMN = 35;

    private const VALID_CODES = ['P', 'S', 'M', 'L'];

    /**
     * @return array{month: int, year: int, days_in_month: int, staff: list<array<string, mixed>>}
     */
    public function parse(string $path, int $month, int $year): array
    {
        if ($month < 1 || $month > 12 || ! checkdate($month, 1, $year)) {
            throw new \InvalidArgumentException('Periode bulan dan tahun tidak valid.');
        }

        try {
            $spreadsheet = IOFactory::load($path);
        } catch (Throwable) {
            throw new \InvalidArgumentException('Workbook Excel tidak dapat dibaca. Pastikan file tidak rusak.');
        }

        $matchingSheets = array_values(array_filter(
            iterator_to_array($spreadsheet->getWorksheetIterator()),
            fn ($worksheet): bool => str_starts_with(
                mb_strtolower(trim($worksheet->getTitle())),
                'jadwal '
            )
        ));

        if (count($matchingSheets) !== 1) {
            throw new \InvalidArgumentException('Workbook harus memiliki tepat satu sheet dengan nama yang diawali “Jadwal ”.');
        }

        $sheet = $matchingSheets[0];
        $this->validateHeaders($sheet);

        $daysInMonth = CarbonImmutable::createSafe($year, $month, 1)->daysInMonth;
        $this->validateDateColumns($sheet, $daysInMonth);

        $staff = [];

        for ($row = self::FIRST_DATA_ROW; $row <= $sheet->getHighestRow(); $row++) {
            $serial = $this->cellText($sheet->getCell("A{$row}")->getValue());
            $name = $this->cellText($sheet->getCell("B{$row}")->getValue());
            $jobTitle = $this->cellText($sheet->getCell("C{$row}")->getValue());
            $nipkwt = $this->cellText($sheet->getCell("D{$row}")->getValue());

            if (! $this->isStaffRow($serial, $name, $nipkwt)) {
                continue;
            }

            $errors = [];
            if ($name === '') {
                $errors[] = "Baris {$row}: nama petugas wajib diisi.";
            }
            if ($nipkwt === '') {
                $errors[] = "Baris {$row}: NIPKWT wajib diisi.";
            }

            $days = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $column = Coordinate::stringFromColumnIndex(self::FIRST_DATE_COLUMN + $day - 1);
                $code = mb_strtoupper(trim($this->cellText($sheet->getCell("{$column}{$row}")->getValue())));
                $valid = in_array($code, self::VALID_CODES, true);

                if ($code === '') {
                    $errors[] = "Baris {$row}, tanggal {$day}: kode jadwal belum diisi.";
                } elseif (! $valid) {
                    $errors[] = "Baris {$row}, tanggal {$day}: kode “{$code}” tidak dikenal; gunakan P, S, M, atau L.";
                }

                $days[] = [
                    'day' => $day,
                    'date' => CarbonImmutable::createSafe($year, $month, $day)->toDateString(),
                    'code' => $code,
                    'valid' => $valid,
                    'holiday' => $valid && $code === 'L',
                ];
            }

            for ($day = $daysInMonth + 1; $day <= self::LAST_DATE_COLUMN - self::FIRST_DATE_COLUMN + 1; $day++) {
                $column = Coordinate::stringFromColumnIndex(self::FIRST_DATE_COLUMN + $day - 1);
                $extraCode = trim($this->cellText($sheet->getCell("{$column}{$row}")->getValue()));

                if ($extraCode !== '') {
                    $errors[] = "Baris {$row}, kolom tanggal {$day}: jadwal terisi untuk tanggal yang tidak ada pada periode terpilih.";
                }
            }

            $staff[] = [
                'workbook_row' => $row,
                'serial' => $serial,
                'name' => $name,
                'job_title' => $jobTitle,
                'nipkwt' => $nipkwt,
                'days' => $days,
                'errors' => $errors,
            ];
        }

        if ($staff === []) {
            throw new \InvalidArgumentException('Tidak ada baris petugas pada workbook.');
        }

        return [
            'month' => $month,
            'year' => $year,
            'days_in_month' => $daysInMonth,
            'staff' => $staff,
        ];
    }

    private function validateHeaders(Worksheet $sheet): void
    {
        $expected = [
            'A' => 'NO.',
            'B' => 'NAMA',
            'C' => 'JABATAN',
            'D' => 'NIPKWT',
            'E' => 'TANGGAL',
        ];

        foreach ($expected as $column => $header) {
            $actual = mb_strtoupper(trim($this->cellText(
                $sheet->getCell("{$column}".self::HEADER_ROW)->getValue()
            )));

            if ($actual !== $header) {
                throw new \InvalidArgumentException("Header kolom {$column} pada baris 6 harus {$header}.");
            }
        }
    }

    private function validateDateColumns(Worksheet $sheet, int $daysInMonth): void
    {
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $column = Coordinate::stringFromColumnIndex(self::FIRST_DATE_COLUMN + $day - 1);
            $actual = trim($this->cellText($sheet->getCell("{$column}".self::DATE_ROW)->getValue()));

            if (! is_numeric($actual) || (int) $actual !== $day || (float) $actual !== (float) $day) {
                throw new \InvalidArgumentException("Header tanggal pada kolom {$column}, baris 7 tidak sesuai dengan tanggal {$day}.");
            }
        }
    }

    private function isStaffRow(string $serial, string $name, string $nipkwt): bool
    {
        $hasNumericSerial = $serial !== '' && is_numeric($serial) && (float) $serial === (float) (int) $serial;

        return $hasNumericSerial || ($name !== '' && $nipkwt !== '');
    }

    private function cellText(mixed $value): string
    {
        if ($value instanceof RichText) {
            return trim($value->getPlainText());
        }

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
