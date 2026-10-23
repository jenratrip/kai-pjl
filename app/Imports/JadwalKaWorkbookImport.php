<?php

namespace App\Imports;

use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class JadwalKaWorkbookImport
{
    public function parseSheet(Worksheet $sheet): array
    {
        $title = trim($sheet->getTitle());
        $lastRow = $sheet->getHighestDataRow();
        $lastColumn = $sheet->getHighestDataColumn();

        if ($lastRow < 1) {
            return [
                'sheet' => $title,
                'header_found' => false,
                'rows' => [],
                'errors' => [],
            ];
        }

        $matrix = $sheet->rangeToArray(
            "A1:{$lastColumn}{$lastRow}",
            null,
            true,
            false,
            false
        );

        $headerIndex = null;
        $columnMap = [];

        // Cari header dalam 20 baris pertama.
        foreach (array_slice($matrix, 0, 20, true) as $index => $row) {
            $candidate = [];

            foreach ($row as $column => $value) {
                $normalized = $this->normalize($value);

                if (in_array($normalized, [
                    'nomor ka',
                    'no ka',
                    'nomor kereta api',
                    'no kereta api',
                ], true)) {
                    $candidate['nomor_ka'] ??= $column;
                } elseif (in_array($normalized, [
                    'nama ka',
                    'nama kereta',
                    'nama kereta api',
                ], true)) {
                    $candidate['nama_ka'] ??= $column;
                } elseif (in_array($normalized, [
                    'dari',
                    'stasiun asal',
                ], true)) {
                    $candidate['dari'] ??= $column;
                } elseif (in_array($normalized, [
                    'ke',
                    'tujuan',
                    'stasiun tujuan',
                ], true)) {
                    $candidate['ke'] ??= $column;
                } elseif (in_array($normalized, [
                    'jam',
                    'jam lewat',
                    'waktu',
                    'jam perjalanan',
                ], true)) {
                    $candidate['jam'] ??= $column;
                } elseif (in_array($normalized, [
                    'keterangan',
                    'ket',
                ], true)) {
                    $candidate['keterangan'] ??= $column;
                }
            }

            if (isset($candidate['nomor_ka'], $candidate['jam'])) {
                $headerIndex = $index;
                $columnMap = $candidate;
                break;
            }
        }

        if ($headerIndex === null) {
            return [
                'sheet' => $title,
                'header_found' => false,
                'rows' => [],
                'errors' => [],
            ];
        }

        $dataStart = $headerIndex + 1;
        $subRow = $matrix[$dataStart] ?? [];
        $subFound = false;

        // Mendukung header bertingkat seperti:
        // JURUSAN -> DARI | KE
        foreach ($subRow as $column => $value) {
            $normalized = $this->normalize($value);

            if ($normalized === 'dari') {
                $columnMap['dari'] ??= $column;
                $subFound = true;
            } elseif ($normalized === 'ke') {
                $columnMap['ke'] ??= $column;
                $subFound = true;
            }
        }

        if ($subFound) {
            $dataStart++;
        }

        $rows = [];
        $errors = [];

        foreach (array_slice($matrix, $dataStart, null, true) as $index => $row) {
            $raw = fn (string $key) => isset($columnMap[$key])
                ? ($row[$columnMap[$key]] ?? null)
                : null;

            $text = fn (string $key) => trim(
                (string) ($raw($key) ?? '')
            );

            $nomor = $text('nomor_ka');
            $nama = $text('nama_ka');
            $dari = $text('dari');
            $ke = $text('ke');
            $keterangan = $text('keterangan');
            $jamRaw = $raw('jam');

            $jamEmpty = $jamRaw === null
                || trim((string) $jamRaw) === '';

            // Abaikan baris yang benar-benar kosong.
            if ($nomor === '' && $nama === '' && $jamEmpty) {
                continue;
            }

            $jam = $this->normalizeTime($jamRaw);
            $error = null;

            if ($nomor === '' && $jam === null) {
                $error = 'Nomor KA dan jam belum diisi atau tidak valid.';
            } elseif ($nomor === '') {
                $error = 'Nomor KA belum diisi.';
            } elseif ($jam === null) {
                $error = 'Format jam tidak valid.';
            }

            $valid = $error === null;

            $parsedRow = [
                'sheet' => $title,
                'row' => $index + 1,
                'nomor_ka' => $nomor,
                'nama_ka' => $nama !== '' ? $nama : null,
                'dari' => $dari !== '' ? $dari : null,
                'ke' => $ke !== '' ? $ke : null,
                'jam_lewat' => $jam,
                'keterangan' => $keterangan !== ''
                    ? $keterangan
                    : null,
                'valid' => $valid,
                'duplicate' => false,
                'error' => $error,
            ];

            $rows[] = $parsedRow;

            if (! $valid) {
                $errors[] = sprintf(
                    'Sheet "%s", baris %d: %s',
                    $title,
                    $index + 1,
                    $error
                );
            }
        }

        return [
            'sheet' => $title,
            'header_found' => true,
            'rows' => $rows,
            'errors' => $errors,
        ];
    }

    private function normalize(mixed $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value);

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function normalizeTime(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('H:i:s');
        }

        if (is_int($value) || is_float($value)) {
            return $this->fractionToTime((float) $value);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // Mendukung format 00.29 atau 00.29.00.
        if (preg_match('/^\d{1,2}\.\d{2}(?:\.\d{2})?$/', $value)) {
            $value = str_replace('.', ':', $value);
        }

        if (preg_match(
            '/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/',
            $value,
            $matches
        )) {
            $hour = (int) $matches[1];
            $minute = (int) $matches[2];
            $second = isset($matches[3])
                ? (int) $matches[3]
                : 0;

            if (
                $hour <= 23
                && $minute <= 59
                && $second <= 59
            ) {
                return sprintf(
                    '%02d:%02d:%02d',
                    $hour,
                    $minute,
                    $second
                );
            }

            return null;
        }

        if (is_numeric($value)) {
            return $this->fractionToTime((float) $value);
        }

        return null;
    }

    private function fractionToTime(float $fraction): ?string
    {
        if ($fraction < 0 || $fraction >= 1) {
            return null;
        }

        $seconds = (int) round($fraction * 86400) % 86400;

        return sprintf(
            '%02d:%02d:%02d',
            intdiv($seconds, 3600),
            intdiv($seconds % 3600, 60),
            $seconds % 60
        );
    }
}
