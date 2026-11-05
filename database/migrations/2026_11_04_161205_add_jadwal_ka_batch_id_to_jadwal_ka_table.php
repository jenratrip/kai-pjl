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
        Schema::table('jadwal_ka', function (Blueprint $table) {
            $table->foreignId('jadwal_ka_batch_id')
                ->nullable()
                ->after('batch_import')
                ->constrained('jadwal_ka_batches')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jadwal_ka', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jadwal_ka_batch_id');
        });
    }
};
