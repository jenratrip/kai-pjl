<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom jenis_petugas pada tabel users.
     *
     * reguler = login dengan NIPP
     * wakilan = login dengan NIPKWT
     * null    = akun non-petugas (admin/superadmin) atau data lama
     *           (akun PJL lama dianggap reguler oleh LoginController)
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'jenis_petugas')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('jenis_petugas', 20)
                    ->nullable()
                    ->after('role_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'jenis_petugas')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('jenis_petugas');
            });
        }
    }
};