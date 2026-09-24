<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dinas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // PJL bertugas
            $table->foreignId('jpl_id')->constrained('jpl')->restrictOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->date('tanggal_dinas');
            $table->dateTime('jam_mulai')->nullable();
            $table->dateTime('jam_selesai')->nullable();
            $table->enum('status', ['berlangsung', 'selesai'])->default('berlangsung');
            $table->boolean('is_pengganti')->default(false);
            $table->foreignId('user_digantikan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dinas');
    }
};
