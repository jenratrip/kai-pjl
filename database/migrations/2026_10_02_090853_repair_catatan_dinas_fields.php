<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catatan_dinas', function (Blueprint $table) {

            $table->foreignId('user_id')
                ->nullable()
                ->after('dinas_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('judul')
                ->nullable()
                ->after('user_id');

            $table->string('kategori')
                ->default('umum')
                ->after('judul');

            $table->string('status')
                ->default('baru')
                ->after('isi_catatan');

            $table->text('tindak_lanjut')
                ->nullable()
                ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('catatan_dinas', function (Blueprint $table) {

            $table->dropForeign(['user_id']);

            $table->dropColumn([
                'user_id',
                'judul',
                'kategori',
                'status',
                'tindak_lanjut',
            ]);
        });
    }
};