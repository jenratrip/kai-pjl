<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_ka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kereta_api_id')->constrained('kereta_api')->cascadeOnDelete();
            $table->foreignId('jpl_id')->constrained('jpl')->cascadeOnDelete();
            $table->time('jam_lewat'); // jam jadwal KA melewati JPL ini
            $table->string('hari_berlaku')->nullable(); // mis. "setiap hari" / "senin-jumat", disesuaikan format excel
            $table->date('tanggal_berlaku')->nullable(); // untuk jadwal khusus tanggal tertentu
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('batch_import')->nullable(); // penanda batch hasil import excel
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_ka');
    }
};
