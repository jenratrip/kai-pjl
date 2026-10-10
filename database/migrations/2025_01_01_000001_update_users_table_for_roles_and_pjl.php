<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'nip')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nip')->nullable()->unique();
            });
        }

        if (!Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'photo')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('photo')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('role_id')->nullable();
            });
        }

        if (!Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')->default(true);
            });
        }

        // Tambahkan foreign key jika belum ada.
        $foreignKeys = collect(
            Schema::getForeignKeys('users')
        )->pluck('name')->all();

        if (
            Schema::hasTable('roles') &&
            Schema::hasColumn('users', 'role_id') &&
            !in_array('users_role_id_foreign', $foreignKeys)
        ) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('role_id')
                    ->references('id')
                    ->on('roles')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Tidak menghapus kolom atau data users lama.
    }
};