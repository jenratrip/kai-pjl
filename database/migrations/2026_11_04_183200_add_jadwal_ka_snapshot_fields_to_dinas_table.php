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
        Schema::table('dinas', function (Blueprint $table) {
            $table->foreignId('jadwal_ka_batch_id')
                ->nullable()
                ->constrained('jadwal_ka_batches')
                ->nullOnDelete();

            $table->json('jadwal_ka_snapshot')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dinas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jadwal_ka_batch_id');
            $table->dropColumn('jadwal_ka_snapshot');
        });
    }
};
