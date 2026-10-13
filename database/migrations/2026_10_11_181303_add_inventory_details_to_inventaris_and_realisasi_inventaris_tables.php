
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan detail master inventaris dan pemeriksaan inventaris.
     */
    public function up(): void
    {
        Schema::table('inventaris', function (Blueprint $table) {
            // Kode unik untuk setiap barang.
            $table->string('kode_barang')->nullable()->unique();

            // Jumlah unit barang yang tercatat.
            $table->unsignedInteger('jumlah')->default(1);
        });

        Schema::table('realisasi_inventaris', function (Blueprint $table) {
            // Kondisi barang ketika diperiksa pada suatu dinas.
            $table->string('kondisi', 30)->nullable();

            // Waktu pemeriksaan inventaris.
            $table->dateTime('diperiksa_pada')->nullable();

            // Petugas yang melakukan pemeriksaan.
            $table->foreignId('diperiksa_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Membatalkan perubahan jika diperlukan.
     */
    public function down(): void
    {
        Schema::table('realisasi_inventaris', function (Blueprint $table) {
            $table->dropForeign(['diperiksa_oleh']);

            $table->dropColumn([
                'kondisi',
                'diperiksa_pada',
                'diperiksa_oleh',
            ]);
        });

        Schema::table('inventaris', function (Blueprint $table) {
            $table->dropUnique(['kode_barang']);

            $table->dropColumn([
                'kode_barang',
                'jumlah',
            ]);
        });
    }
};
