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
        Schema::create('jadwal_ka_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->enum('status', ['draft', 'active', 'archived'])
                ->default('draft')
                ->index();
            $table->date('tanggal_mulai_berlaku')->index();
            $table->timestamps();
        });

        Schema::create('jadwal_ka_batch_jpl', function (Blueprint $table) {
            $table->foreignId('jadwal_ka_batch_id')
                ->constrained('jadwal_ka_batches')
                ->cascadeOnDelete();
            $table->foreignId('jpl_id')
                ->constrained('jpl')
                ->cascadeOnDelete();
            $table->primary(['jadwal_ka_batch_id', 'jpl_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jadwal_ka_batch_jpl');
        Schema::dropIfExists('jadwal_ka_batches');
    }
};
