<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the permintaan_tukar_jadwal table to track schedule swap requests
     * between PJL staff members. This is separate from serah_terima (handoff) workflow.
     *
     * Swap process:
     * 1. User A submits request to swap their schedule with User B's schedule
     * 2. User B receives request, can approve or reject with reason
     * 3. If approved, system validates and atomically swaps user_id on both dinas records
     * 4. No admin approval required - peer-to-peer transaction
     *
     * Foreign keys use nullOnDelete to preserve swap history even if original
     * dinas records are deleted (rare edge case).
     */
    public function up(): void
    {
        Schema::create('permintaan_tukar_jadwal', function (Blueprint $table) {
            $table->id();

            // Dinas records being swapped
            $table->foreignId('dinas_pemohon_id')
                ->nullable()
                ->constrained('dinas')
                ->nullOnDelete();  // Preserve history if dinas deleted

            $table->foreignId('dinas_tujuan_id')
                ->nullable()
                ->constrained('dinas')
                ->nullOnDelete();  // Preserve history if dinas deleted

            // Users involved in swap
            $table->foreignId('pemohon_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();  // Preserve history if user deleted

            $table->foreignId('penerima_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();  // Preserve history if user deleted

            // Three separate reason columns as per requirements
            // Must NOT be mixed together
            $table->text('alasan_pemohon')
                ->nullable(false);  // Mandatory at creation, immutable

            $table->text('alasan_penerima')
                ->nullable();  // Only filled if penerima rejects, immutable

            $table->text('alasan_gagal')
                ->nullable();  // Only filled by system if validation fails, read-only

            // Status tracking
            $table->enum('status', [
                'menunggu',    // Awaiting penerima response
                'ditolak',     // Penerima rejected
                'dibatalkan',  // Pemohon cancelled (only if status was 'menunggu')
                'berhasil',    // Successfully swapped
                'gagal',       // Validation failed during approval
            ])->default('menunggu')->index();

            // Timestamps for workflow tracking
            $table->timestamp('diajukan_pada')
                ->useCurrent()
                ->index();

            $table->timestamp('dijawab_pada')
                ->nullable()
                ->index();  // When penerima responded (approve, reject, or system failure)

            $table->timestamp('selesai_pada')
                ->nullable()
                ->index();  // When swap actually completed or process ended

            $table->timestamps();

            // Indexes for common queries
            $table->index('pemohon_id');
            $table->index('penerima_id');
            $table->index(['dinas_pemohon_id', 'status']);
            $table->index(['dinas_tujuan_id', 'status']);
            $table->index(['pemohon_id', 'status']);
            $table->index(['penerima_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaan_tukar_jadwal');
    }
};
