<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisasi_ka_settings', function (Blueprint $table) {
            $table->id();

            // Seluruh ambang batas ditentukan melalui panel admin.
            $table->unsignedInteger('toleransi_keterlambatan_menit')->default(0);
            $table->unsignedInteger('peringatan_keterlambatan_tinggi_menit')->default(0);
            $table->unsignedInteger('jarak_minimum_antar_ka_menit')->default(0);
            $table->unsignedInteger('batas_penutupan_palang_menit')->default(0);

            // Sakelar setiap jenis warning.
            $table->boolean('warning_keterlambatan_aktif')->default(false);
            $table->boolean('warning_keterlambatan_tinggi_aktif')->default(false);
            $table->boolean('warning_antar_ka_aktif')->default(false);
            $table->boolean('warning_penutupan_palang_aktif')->default(false);

            // Admin terakhir yang mengubah konfigurasi.
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi_ka_settings');
    }
};
