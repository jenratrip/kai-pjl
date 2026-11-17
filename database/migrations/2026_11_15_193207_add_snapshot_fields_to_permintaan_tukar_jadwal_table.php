<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds snapshot fields to preserve pemohon/penerima identity and jadwal details
     * for historical record retention even if original users or dinas records are deleted.
     *
     * Snapshots are immutable:
     * - Filled at request creation time
     * - Never updated
     * - Allows riwayat views to show complete information even after deletions
     */
    public function up(): void
    {
        Schema::table('permintaan_tukar_jadwal', function (Blueprint $table) {
            // Pemohon identity snapshot (filled at creation, immutable)
            $table->string('pemohon_name')->nullable()->after('pemohon_id');
            $table->string('pemohon_nip')->nullable()->after('pemohon_name');

            // Penerima identity snapshot (filled at creation, immutable)
            $table->string('penerima_name')->nullable()->after('penerima_id');
            $table->string('penerima_nip')->nullable()->after('penerima_name');

            // Jadwal pemohon snapshot (filled at creation, immutable)
            $table->json('dinas_pemohon_snapshot')->nullable()->after('dinas_pemohon_id');

            // Jadwal penerima snapshot (filled at creation, immutable)
            $table->json('dinas_tujuan_snapshot')->nullable()->after('dinas_tujuan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permintaan_tukar_jadwal', function (Blueprint $table) {
            $table->dropColumn([
                'pemohon_name',
                'pemohon_nip',
                'penerima_name',
                'penerima_nip',
                'dinas_pemohon_snapshot',
                'dinas_tujuan_snapshot',
            ]);
        });
    }
};
