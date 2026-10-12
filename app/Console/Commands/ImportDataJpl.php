<?php

namespace App\Console\Commands;

use App\Models\Jpl;
use App\Models\Resort;
use App\Models\Wilayah;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class ImportDataJpl extends Command
{
    protected $signature = 'jpl:import-csv';

    protected $description = 'Import master data JPL dari file CSV';

    public function handle(): int
    {
        $path = storage_path('app/imports/Data JPL.csv');

        if (!File::exists($path)) {
            $this->error("File tidak ditemukan: {$path}");
            return self::FAILURE;
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            $this->error('File CSV tidak dapat dibuka.');
            return self::FAILURE;
        }

        // CSV menggunakan delimiter titik koma (;).
        $header = fgetcsv($handle, 0, ';', '"', '\\');

        if (!$header) {
            fclose($handle);
            $this->error('Header CSV tidak ditemukan.');
            return self::FAILURE;
        }

        // Hilangkan BOM jika CSV disimpan dengan UTF-8 BOM.
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        $header = array_map(
            fn ($value) => strtoupper(trim((string) $value)),
            $header
        );

        $required = [
            'RESORT',
            'ANTARA',
            'KM / HM',
            'NO JPL',
            'NAMA JLN / DAERAH',
            'KOTA / KABUPATEN',
            'STATUS PENJAGAAN',
            'PROVINSI',
            'JENIS JALUR KA',
            'TITIK KOORDINAT',
        ];

        $indexes = [];

        foreach ($required as $column) {
            $index = array_search($column, $header, true);

            if ($index === false) {
                fclose($handle);
                $this->error("Kolom wajib tidak ditemukan: {$column}");
                return self::FAILURE;
            }

            $indexes[$column] = $index;
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            $line = 1;

            while (($row = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
                $line++;

                // Abaikan baris kategori dan baris kosong.
                if (count(array_filter(
                    $row,
                    fn ($value) => trim((string) $value) !== ''
                )) === 0) {
                    $skipped++;
                    continue;
                }

                $get = function (string $column) use ($row, $indexes): string {
                    return trim((string) ($row[$indexes[$column]] ?? ''));
                };

                $resortName = $get('RESORT');
                $kodeJpl = $get('NO JPL');
                $provinsi = $get('PROVINSI');

                // Baris data harus mempunyai kode, resort, dan provinsi.
                if (
                    $resortName === '' ||
                    $kodeJpl === '' ||
                    $provinsi === ''
                ) {
                    $skipped++;
                    continue;
                }

                $wilayah = Wilayah::firstOrCreate(
                    ['nama_wilayah' => $provinsi],
                    ['is_active' => true]
                );

                $resort = Resort::firstOrCreate(
                    [
                        'wilayah_id' => $wilayah->id,
                        'nama_resort' => $resortName,
                    ],
                    ['is_active' => true]
                );

                Jpl::updateOrCreate(
                    [
                        'kode_jpl' => $kodeJpl,
                        'resort_id' => $resort->id,
                    ],
                    [
                        'nama_jpl' => $resortName . ' - JPL ' . $kodeJpl,
                        'antara' => $get('ANTARA') ?: null,
                        'km_hm' => $get('KM / HM') ?: null,
                        'lokasi' => $get('NAMA JLN / DAERAH') ?: null,
                        'kota_kabupaten' => $get('KOTA / KABUPATEN') ?: null,
                        'provinsi' => $provinsi,
                        'jenis_jalur_ka' => $get('JENIS JALUR KA') ?: null,
                        'titik_koordinat' => $get('TITIK KOORDINAT') ?: null,
                        'status_penjagaan' => $get('STATUS PENJAGAAN') ?: null,
                        'is_active' => true,
                    ]
                );

                $imported++;
            }

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            fclose($handle);

            $this->error('Import gagal. Perubahan database dibatalkan.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        fclose($handle);

        $this->info('Import CSV selesai.');
        $this->info("Data JPL diproses: {$imported}");
        $this->info("Baris dilewati: {$skipped}");
        $this->info('Data JPL yang cocok akan diperbarui, bukan digandakan.');

        return self::SUCCESS;
    }
}