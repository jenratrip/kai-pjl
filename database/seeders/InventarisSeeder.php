<?php

namespace Database\Seeders;

use App\Models\Dinas;
use App\Models\Inventaris;
use Illuminate\Database\Seeder;

class InventarisSeeder extends Seeder
{
    public function run(): void
    {
        // JPL dari dinas yang sedang berlangsung, cadangan: 80.
        $jplId = Dinas::where('status', 'berlangsung')->value('jpl_id') ?? 80;

        // [kode, nama barang, jumlah]
        $items = [
            ['INV-A', 'Daftar perjalanan KA',          1],
            ['INV-B', 'Buku realisasi KA yang lewat',  1],
            ['INV-C', 'Buku gangguan',                 1],
            ['INV-D', 'Buku tamu/instruksi',           2],
            ['INV-E', 'Gambar semboyan',               1],
            ['INV-F', 'Lentera',                       1],
            ['INV-G', 'Lampu senter',                  1],
            ['INV-H', 'HT',                            1],
            ['INV-I', 'Bendera merah (semboyan 3)',    2],
            ['INV-J', 'Bendera kuning (semboyan 2A)',  1],
            ['INV-K', 'Bendera kuning (semboyan 2B)',  1],
            ['INV-L', 'Rambu stop',                    2],
            ['INV-M', 'Pet PJL',                       1],
            ['INV-N', 'Peluit',                        1],
            ['INV-O', 'Jam dinding',                   1],
            ['INV-P', 'Kotak P3K',                     1],
            ['INV-Q', 'APAR',                          1],
        ];

        foreach ($items as [$kode, $nama, $jumlah]) {
            Inventaris::updateOrCreate(
                ['kode_barang' => $kode, 'jpl_id' => $jplId],
                [
                    'nama_barang'     => $nama,
                    'kategori'        => 'inventaris_pjl',
                    'lokasi'          => 'Pos JPL',
                    'jumlah'          => $jumlah,
                    'is_active'       => true,
                    'status_tinjauan' => 'normal',
                ]
            );
        }

        $this->command?->info("Inventaris dibuat untuk jpl_id {$jplId}: " . count($items) . ' barang.');
    }
}