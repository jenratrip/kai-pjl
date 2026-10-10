
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jpl', function (Blueprint $table) {
            $table->string('antara')->nullable()->after('resort_id');
            $table->string('km_hm')->nullable()->after('antara');
            $table->string('kota_kabupaten')->nullable()->after('lokasi');
            $table->string('provinsi')->nullable()->after('kota_kabupaten');
            $table->string('jenis_jalur_ka')->nullable()->after('provinsi');
            $table->string('titik_koordinat')->nullable()->after('jenis_jalur_ka');
            $table->string('status_penjagaan')->nullable()->after('titik_koordinat');
        });
    }

    public function down(): void
    {
        Schema::table('jpl', function (Blueprint $table) {
            $table->dropColumn([
                'antara',
                'km_hm',
                'kota_kabupaten',
                'provinsi',
                'jenis_jalur_ka',
                'titik_koordinat',
                'status_penjagaan',
            ]);
        });
    }
};