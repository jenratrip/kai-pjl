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
        Schema::create('jadwal_dinas_libur', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('jpl_id')
                ->constrained('jpl')
                ->restrictOnDelete();
            $table->date('tanggal_dinas');
            $table->unique(
                ['user_id', 'jpl_id', 'tanggal_dinas'],
                'jadwal_dinas_libur_user_jpl_tanggal_unique'
            );
            $table->index(
                ['jpl_id', 'tanggal_dinas'],
                'jadwal_dinas_libur_jpl_tanggal_index'
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            Schema::hasTable('jadwal_dinas_libur')
            && DB::table('jadwal_dinas_libur')->exists()
        ) {
            throw new RuntimeException(
                'Rollback dibatalkan karena jadwal libur masih berisi data.'
            );
        }

        Schema::dropIfExists('jadwal_dinas_libur');
    }
};
