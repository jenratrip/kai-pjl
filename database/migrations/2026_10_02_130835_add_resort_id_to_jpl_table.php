<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jpl', function (Blueprint $table) {
            $table->foreignId('resort_id')
                ->after('nama_jpl')
                ->constrained('resorts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('jpl', function (Blueprint $table) {
            $table->dropForeign(['resort_id']);
            $table->dropColumn('resort_id');
        });
    }
};