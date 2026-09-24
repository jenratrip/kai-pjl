<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('serah_terima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dinas_id')->constrained('dinas')->cascadeOnDelete(); // yang menyerahkan
            $table->foreignId('dinas_penerima_id')->nullable()->constrained('dinas')->nullOnDelete(); // dinas PJL pengganti
            $table->dateTime('waktu_serah_terima')->nullable();
            $table->json('ringkasan')->nullable(); // snapshot ringkasan realisasi_ka + realisasi_inventaris + catatan_dinas
            $table->string('tanda_tangan_penyerah')->nullable(); // path file gambar hasil canvas
            $table->string('tanda_tangan_penerima')->nullable();
            $table->enum('status', ['draft', 'menunggu_penerima', 'selesai'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('serah_terima');
    }
};
