<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventaris', function (Blueprint $table) {
            $table->string('kondisi_terakhir', 30)
                ->default('baik');

            $table->unsignedInteger('jumlah_aktual_terakhir')
                ->nullable();

            $table->string('status_tinjauan', 30)
                ->default('normal');

            $table->text('catatan_masalah')->nullable();

            $table->timestamp('dilaporkan_pada')->nullable();

            $table->foreignId('dilaporkan_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('catatan_tinjauan')->nullable();

            $table->foreignId('ditinjau_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('ditinjau_pada')->nullable();
        });

        Schema::table('realisasi_inventaris', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_aktual')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('realisasi_inventaris', function (Blueprint $table) {
            $table->dropColumn('jumlah_aktual');
        });

        Schema::table('inventaris', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dilaporkan_oleh');
            $table->dropConstrainedForeignId('ditinjau_oleh');

            $table->dropColumn([
                'kondisi_terakhir',
                'jumlah_aktual_terakhir',
                'status_tinjauan',
                'catatan_masalah',
                'dilaporkan_pada',
                'catatan_tinjauan',
                'ditinjau_pada',
            ]);
        });
    }
};
