<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('serah_terima', function (Blueprint $table) {
            $table->foreignId('penerima_user_id')
                ->nullable()
                ->after('dinas_penerima_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('serah_terima', function (Blueprint $table) {
            $table->dropConstrainedForeignId('penerima_user_id');
        });
    }
};
