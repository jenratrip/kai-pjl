<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE dinas
            MODIFY status
            ENUM('dijadwalkan', 'berlangsung', 'selesai')
            NOT NULL DEFAULT 'dijadwalkan'
        ");
    }

    public function down(): void
    {
        // Pastikan tidak ada status yang hilang saat rollback.
        DB::table('dinas')
            ->where('status', 'dijadwalkan')
            ->update(['status' => 'berlangsung']);

        DB::statement("
            ALTER TABLE dinas
            MODIFY status
            ENUM('berlangsung', 'selesai')
            NOT NULL DEFAULT 'berlangsung'
        ");
    }
};
