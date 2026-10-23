
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_ka', function (Blueprint $table) {
            $table->string('dari', 20)->nullable()->after('kereta_api_id');
            $table->string('ke', 20)->nullable()->after('dari');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_ka', function (Blueprint $table) {
            $table->dropColumn(['dari', 'ke']);
        });
    }
};
