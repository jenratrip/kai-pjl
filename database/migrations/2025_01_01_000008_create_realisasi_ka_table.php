<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisasi_ka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dinas_id')->constrained('dinas')->cascadeOnDelete();
            $table->foreignId('jadwal_ka_id')->nullable()->constrained('jadwal_ka')->nullOnDelete();
            $table->foreignId('kereta_api_id')->nullable()->constrained('kereta_api')->nullOnDelete(); // dipakai kalau is_manual
            $table->enum('status_lewat', ['centang', 'silang'])->nullable(); // lewat / batal
            $table->time('jam_aktual')->nullable();
            $table->enum('status_s21', ['centang', 'silang'])->nullable();
            $table->enum('status_genta', ['centang', 'silang'])->nullable();
            $table->enum('status_pintu_perlintasan', ['centang', 'silang'])->nullable();
            $table->enum('status_s35', ['centang', 'silang'])->nullable();
            $table->boolean('is_manual')->default(false); // KA di luar jadwal
            $table->text('catatan')->nullable();
            $table->timestamp('diedit_terakhir_pada')->nullable(); // untuk validasi window edit 1 jam
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi_ka');
    }
};
