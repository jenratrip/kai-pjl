<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realisasi_inventaris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dinas_id')->constrained('dinas')->cascadeOnDelete();
            $table->foreignId('inventaris_id')->constrained('inventaris')->restrictOnDelete();
            $table->enum('status', ['centang', 'silang'])->nullable(); // baik/ada vs rusak/tidak ada
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realisasi_inventaris');
    }
};
