<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catatan_dinas', function (Blueprint $table) {
            $table->string('status_review', 30)
                ->default('menunggu_review');

            $table->text('catatan_revisi')->nullable();

            $table->foreignId('ditinjau_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('ditinjau_pada')->nullable();
        });

        Schema::table('serah_terima', function (Blueprint $table) {
            $table->string('status_review', 30)
                ->default('menunggu_review');

            $table->text('catatan_revisi')->nullable();

            $table->foreignId('ditinjau_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('ditinjau_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('serah_terima', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ditinjau_oleh');

            $table->dropColumn([
                'status_review',
                'catatan_revisi',
                'ditinjau_pada',
            ]);
        });

        Schema::table('catatan_dinas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ditinjau_oleh');

            $table->dropColumn([
                'status_review',
                'catatan_revisi',
                'ditinjau_pada',
            ]);
        });
    }
};
