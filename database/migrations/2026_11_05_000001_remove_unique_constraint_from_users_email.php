<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'email')) {
            return;
        }

        $emailUniqueIndex = collect(Schema::getIndexes('users'))
            ->first(fn (array $index): bool => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['email']
            );

        if ($emailUniqueIndex === null) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($emailUniqueIndex): void {
            $table->dropUnique($emailUniqueIndex['name']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'email')) {
            return;
        }

        $emailUniqueIndexExists = collect(Schema::getIndexes('users'))
            ->contains(fn (array $index): bool => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['email']
            );

        if ($emailUniqueIndexExists) {
            return;
        }

        $duplicateEmailExists = DB::table('users')
            ->select('email')
            ->whereNotNull('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateEmailExists) {
            throw new LogicException(
                'Unique email cannot be restored while duplicate email values exist in users.'
            );
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('email');
        });
    }
};
