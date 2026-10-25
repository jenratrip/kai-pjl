<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            [
                'id' => 1,
                'nama_shift' => 'Pagi',
                'jam_mulai' => '06:00:00',
                'jam_selesai' => '14:00:00',
            ],
            [
                'id' => 2,
                'nama_shift' => 'Siang',
                'jam_mulai' => '14:00:00',
                'jam_selesai' => '22:00:00',
            ],
            [
                'id' => 3,
                'nama_shift' => 'Malam',
                'jam_mulai' => '22:00:00',
                'jam_selesai' => '06:00:00',
            ],
        ];

        foreach ($shifts as $shift) {
            $exists = DB::table('shifts')
                ->where('id', $shift['id'])
                ->exists();

            if (!$exists) {
                DB::table('shifts')->insert([
                    ...$shift,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
