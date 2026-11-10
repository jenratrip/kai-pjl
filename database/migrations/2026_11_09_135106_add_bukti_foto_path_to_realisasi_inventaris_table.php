<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('realisasi_inventaris', function (Blueprint $table): void {
            $table->string('bukti_foto_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('realisasi_inventaris', function (Blueprint $table): void {
            $table->dropColumn('bukti_foto_path');
        });
    }
};
