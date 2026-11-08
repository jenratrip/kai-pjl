<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasi_ka_settings', function (Blueprint $table) {
            // Ambang KA lebih cepat dari jadwal.
            $table->unsignedInteger('batas_lebih_awal_menit')
                ->default(2);

            $table->boolean('warning_lebih_awal_aktif')
                ->default(true);

            // Pengaturan khusus AWAS 2 arah.
            $table->unsignedInteger('batas_awas_2_arah_menit')
                ->default(5);

            $table->boolean('warning_awas_2_arah_aktif')
                ->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('realisasi_ka_settings', function (Blueprint $table) {
            $table->dropColumn([
                'batas_lebih_awal_menit',
                'warning_lebih_awal_aktif',
                'batas_awas_2_arah_menit',
                'warning_awas_2_arah_aktif',
            ]);
        });
    }
};
