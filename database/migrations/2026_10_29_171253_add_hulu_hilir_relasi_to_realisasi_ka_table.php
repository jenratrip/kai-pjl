
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('realisasi_ka', function (Blueprint $table) {
            $table->string('hulu')->nullable();
            $table->string('hilir')->nullable();
            $table->string('relasi')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('realisasi_ka', function (Blueprint $table) {
            $table->dropColumn(['hulu', 'hilir', 'relasi']);
        });
    }
};
