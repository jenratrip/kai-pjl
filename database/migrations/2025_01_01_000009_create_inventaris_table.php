<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaris', function (Blueprint $table) {
            $table->id();
            $table->string('nama_barang');
            $table->enum('kategori', ['perangkat_pengamanan', 'alat_pendukung', 'inventaris_pjl']);
            $table->string('lokasi')->nullable(); // penambahan sesuai masukan KAI
            $table->foreignId('jpl_id')->nullable()->constrained('jpl')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris');
    }
};
