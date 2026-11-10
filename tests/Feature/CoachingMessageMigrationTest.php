<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    expect(DB::connection()->getDriverName())->toBe('sqlite')
        ->and(DB::connection()->getDatabaseName())->toBe(':memory:');

    DB::connection()->getPdo()->sqliteCreateCollation(
        'utf8mb4_bin',
        static fn (string $left, string $right): int => strcmp($left, $right),
    );

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });
});

it('creates the coaching tables with the required indexes and guarded rollback', function (): void {
    $migration = require database_path('migrations/2026_11_08_181116_create_coaching_message_tables.php');

    $migration->up();

    expect(Schema::hasTable('coaching_messages'))->toBeTrue()
        ->and(Schema::hasTable('coaching_message_nonces'))->toBeTrue()
        ->and(Schema::hasColumn('coaching_messages', 'read_at'))->toBeTrue();

    expect(Schema::getColumnType('coaching_messages', 'received_at'))->toBe('datetime')
        ->and(Schema::getColumnType('coaching_messages', 'expires_at'))->toBe('datetime')
        ->and(Schema::getColumnType('coaching_message_nonces', 'request_timestamp'))->toBe('datetime')
        ->and(Schema::getColumnType('coaching_message_nonces', 'expires_at'))->toBe('datetime');

    DB::table('coaching_message_nonces')->insert([
        'source_system' => 'rollback-test',
        'nonce' => 'nonce-only',
        'request_timestamp' => now(),
        'expires_at' => now()->addMinute(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => $migration->down())
        ->toThrow(RuntimeException::class, 'Pesan atau nonce Pesan Pembinaan masih tersimpan');
    expect(Schema::hasTable('coaching_messages'))->toBeTrue()
        ->and(Schema::hasTable('coaching_message_nonces'))->toBeTrue();

    DB::table('coaching_message_nonces')->delete();

    $messageIndexes = collect(Schema::getIndexes('coaching_messages'))->keyBy('name');
    expect($messageIndexes->has('coaching_messages_source_uuid_unique'))->toBeTrue()
        ->and($messageIndexes->has('coaching_messages_source_idempotency_unique'))->toBeTrue()
        ->and($messageIndexes->has('coaching_messages_recipient_expiry_index'))->toBeTrue()
        ->and($messageIndexes->has('coaching_messages_recipient_read_expiry_index'))->toBeTrue();

    $foreignKeys = collect(Schema::getForeignKeys('coaching_messages'));
    expect($foreignKeys->contains(
        fn (array $foreignKey): bool => $foreignKey['columns'] === ['recipient_user_id']
            && $foreignKey['foreign_table'] === 'users'
            && $foreignKey['foreign_columns'] === ['id'],
    ))->toBeTrue();

    $migration->down();
    expect(Schema::hasTable('coaching_messages'))->toBeFalse()
        ->and(Schema::hasTable('coaching_message_nonces'))->toBeFalse()
        ->and(Schema::hasTable('users'))->toBeTrue();

    $migration->up();
    $userId = DB::table('users')->insertGetId(['name' => 'PJL test']);
    $messageUuid = (string) Str::uuid();

    DB::table('coaching_messages')->insert(coachingMigrationMessage($userId, $messageUuid, 'Key-A'));
    DB::table('coaching_messages')->insert(coachingMigrationMessage($userId, (string) Str::uuid(), 'key-a'));

    expect(DB::table('coaching_messages')->count())->toBe(2);

    expect(fn () => DB::table('coaching_messages')->insert(
        coachingMigrationMessage($userId, (string) Str::uuid(), 'Key-A'),
    ))->toThrow(QueryException::class);

    DB::table('coaching_message_nonces')->insert([
        'source_system' => 'test-source',
        'nonce' => 'Nonce-A',
        'request_timestamp' => now(),
        'expires_at' => now()->addMinute(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('coaching_message_nonces')->insert([
        'source_system' => 'test-source',
        'nonce' => 'nonce-a',
        'request_timestamp' => now(),
        'expires_at' => now()->addMinute(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(DB::table('coaching_message_nonces')->count())->toBe(2);

    expect(fn () => DB::table('coaching_message_nonces')->insert([
        'source_system' => 'test-source',
        'nonce' => 'Nonce-A',
        'request_timestamp' => now(),
        'expires_at' => now()->addMinute(),
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('users')->where('id', $userId)->delete())
        ->toThrow(QueryException::class);

    expect(fn () => $migration->down())
        ->toThrow(RuntimeException::class, 'Pesan atau nonce Pesan Pembinaan masih tersimpan');

    expect(Schema::hasTable('coaching_messages'))->toBeTrue()
        ->and(Schema::hasTable('coaching_message_nonces'))->toBeTrue()
        ->and(Schema::hasTable('users'))->toBeTrue();
});

/**
 * @return array<string, mixed>
 */
function coachingMigrationMessage(int $recipientId, string $messageUuid, string $idempotencyKey): array
{
    return [
        'source_system' => 'test-source',
        'message_uuid' => $messageUuid,
        'recipient_user_id' => $recipientId,
        'matched_identifier_type' => 'nipp',
        'subject' => 'Test',
        'body' => 'Isi test',
        'idempotency_key' => $idempotencyKey,
        'request_hash' => str_repeat('a', 64),
        'received_at' => now(),
        'expires_at' => now()->addHours(72),
        'created_at' => now(),
        'updated_at' => now(),
    ];
}
