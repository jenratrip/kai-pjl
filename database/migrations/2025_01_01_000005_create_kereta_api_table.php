<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kereta_api', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_ka')->unique();
            $table->string('nama_ka')->nullable();
            $table->enum('jenis', ['penumpang', 'barang'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kereta_api');
    }
};
