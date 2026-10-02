<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resorts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wilayah_id')
                ->constrained('wilayah')
                ->restrictOnDelete();

            $table->string('nama_resort');
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resorts');
    }
};