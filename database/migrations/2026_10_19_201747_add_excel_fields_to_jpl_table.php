<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jpl', function (Blueprint $table) {
            // NAMA JLN / DAERAH boleh kosong
            $table->string('nama_jpl')
                ->nullable()
                ->change();

            // KONST JALAN
            $table->string('konst_jalan')
                ->nullable()
                ->after('jenis_jalur_ka');

            // LEBAR JLN (m)
            $table->string('lebar_jln')
                ->nullable()
                ->after('titik_koordinat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jpl', function (Blueprint $table) {
            $table->dropColumn([
                'konst_jalan',
                'lebar_jln',
            ]);
        });

        Schema::table('jpl', function (Blueprint $table) {
            $table->string('nama_jpl')
                ->nullable(false)
                ->change();
        });
    }
};
