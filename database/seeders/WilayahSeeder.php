<?php

namespace Database\Seeders;

use App\Models\Wilayah;
use Illuminate\Database\Seeder;

class WilayahSeeder extends Seeder
{
    public function run(): void
    {
        Wilayah::updateOrCreate(
            ['id' => 1],
            [
                'nama_wilayah' => 'Daop 6',
                'keterangan' => 'Daerah Operasi 6 Yogyakarta',
                'is_active' => true,
            ]
        );
    }
}
