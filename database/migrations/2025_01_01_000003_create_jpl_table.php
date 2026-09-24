<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jpl', function (Blueprint $table) {
            $table->id();
            $table->string('kode_jpl')->unique()->nullable();
            $table->string('nama_jpl');
            $table->string('lokasi')->nullable(); // km / titik perlintasan
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jpl');
    }
};
