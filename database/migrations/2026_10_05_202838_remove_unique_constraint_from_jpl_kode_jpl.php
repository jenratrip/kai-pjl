<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jpl', function (Blueprint $table) {
            $table->dropUnique('jpl_kode_jpl_unique');

            $table->index(
                ['resort_id', 'kode_jpl'],
                'jpl_resort_kode_jpl_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('jpl', function (Blueprint $table) {
            $table->dropIndex('jpl_resort_kode_jpl_index');

            $table->unique(
                'kode_jpl',
                'jpl_kode_jpl_unique'
            );
        });
    }
};