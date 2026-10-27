<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $semuaHari = json_encode([
            'Senin', 'Selasa', 'Rabu', 'Kamis',
            'Jumat', 'Sabtu', 'Minggu',
        ]);

        $hariKerja = json_encode([
            'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat',
        ]);

        $akhirPekan = json_encode([
            'Sabtu', 'Minggu',
        ]);

        DB::table('jadwal_ka')
            ->whereRaw('LOWER(TRIM(hari_berlaku)) = ?', ['setiap hari'])
            ->update(['hari_berlaku' => $semuaHari]);

        DB::table('jadwal_ka')
            ->whereRaw('LOWER(TRIM(hari_berlaku)) = ?', ['senin-jumat'])
            ->update(['hari_berlaku' => $hariKerja]);

        DB::table('jadwal_ka')
            ->whereRaw('LOWER(TRIM(hari_berlaku)) = ?', ['sabtu-minggu'])
            ->update(['hari_berlaku' => $akhirPekan]);
    }

    public function down(): void
    {
        // Tidak mengembalikan nilai lama secara otomatis
    }
};