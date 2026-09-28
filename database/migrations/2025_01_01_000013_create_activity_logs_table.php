<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            // Pengguna yang melakukan aktivitas
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Jenis aktivitas
            $table->string('action');

            // Modul yang terkait dengan aktivitas
            $table->string('module');

            // Deskripsi aktivitas
            $table->text('description')->nullable();

            // Data/model yang terkena aktivitas
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // Nilai sebelum dan sesudah perubahan
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Informasi teknis pengguna
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Index untuk kebutuhan monitoring Superadmin
            $table->index(['module', 'subject_id']);
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};