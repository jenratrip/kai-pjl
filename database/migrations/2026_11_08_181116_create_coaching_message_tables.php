<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coaching_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('source_system', 64);
            $table->string('message_uuid', 36);
            $table->string('inspection_id', 128)->nullable();
            $table->foreignId('recipient_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->string('matched_identifier_type', 8);
            $table->string('subject');
            $table->text('body');
            $table->json('payload')->nullable();
            $idempotencyKey = $table->string('idempotency_key', 128);
            if (DB::getDriverName() !== 'sqlite') {
                $idempotencyKey->collation('utf8mb4_bin');
            }
            $table->char('request_hash', 64);
            $table->dateTime('received_at');
            $table->dateTime('expires_at');
            $table->dateTime('read_at')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');

            $table->unique(
                ['source_system', 'message_uuid'],
                'coaching_messages_source_uuid_unique',
            );
            $table->unique(
                ['source_system', 'idempotency_key'],
                'coaching_messages_source_idempotency_unique',
            );
            $table->index(
                ['recipient_user_id', 'expires_at'],
                'coaching_messages_recipient_expiry_index',
            );
            $table->index(
                ['recipient_user_id', 'read_at', 'expires_at'],
                'coaching_messages_recipient_read_expiry_index',
            );
        });

        Schema::create('coaching_message_nonces', function (Blueprint $table): void {
            $table->id();
            $table->string('source_system', 64);
            $nonce = $table->string('nonce', 128);
            if (DB::getDriverName() !== 'sqlite') {
                $nonce->collation('utf8mb4_bin');
            }
            $table->timestamp('request_timestamp');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(
                ['source_system', 'nonce'],
                'coaching_message_nonces_source_nonce_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasStoredMessages = Schema::hasTable('coaching_messages')
            && DB::table('coaching_messages')->exists();
        $hasStoredNonces = Schema::hasTable('coaching_message_nonces')
            && DB::table('coaching_message_nonces')->exists();

        if ($hasStoredMessages || $hasStoredNonces) {
            throw new RuntimeException(
                'Pesan atau nonce Pesan Pembinaan masih tersimpan; migration tidak di-rollback agar data tidak terhapus.',
            );
        }

        Schema::dropIfExists('coaching_message_nonces');
        Schema::dropIfExists('coaching_messages');
    }
};
