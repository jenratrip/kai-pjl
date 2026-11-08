<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jadwal_ka_batches', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'archived', 'scheduled'])
                ->default('draft')
                ->change();
        });

        Schema::table('jadwal_ka_batch_jpl', function (Blueprint $table) {
            $table->date('tanggal_akhir_berlaku')
                ->nullable()
                ->after('jpl_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('jadwal_ka_batches')->where('status', 'scheduled')->exists()) {
            throw new RuntimeException(
                'Status scheduled masih digunakan. Jadwalkan atau batalkan seluruh batch sebelum rollback migration.'
            );
        }

        if (DB::table('jadwal_ka_batch_jpl')->whereNotNull('tanggal_akhir_berlaku')->exists()) {
            throw new RuntimeException(
                'Tanggal akhir versi masih tersimpan. Jangan rollback migration karena histori rentang efektif akan hilang.'
            );
        }

        Schema::table('jadwal_ka_batches', function (Blueprint $table) {
            $table->enum('status', ['draft', 'active', 'archived'])
                ->default('draft')
                ->change();
        });

        Schema::table('jadwal_ka_batch_jpl', function (Blueprint $table) {
            $table->dropColumn('tanggal_akhir_berlaku');
        });
    }
};
