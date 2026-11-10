<?php

use App\Events\CoachingMessageReceived;
use App\Models\CoachingMessage;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\KaiTestRecords;
use Tests\TestCase;

uses(KaiTestRecords::class);

beforeEach(function (): void {
    expect(DB::connection()->getDriverName())->toBe('sqlite')
        ->and(DB::connection()->getDatabaseName())->toBe(':memory:');

    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->unique();
        $table->string('label')->nullable();
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('nip')->unique();
        $table->string('name');
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->string('photo')->nullable();
        $table->foreignId('role_id');
        $table->string('jenis_petugas', 20)->nullable();
        $table->string('password');
        $table->boolean('is_active')->default(true);
        $table->timestamp('email_verified_at')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('coaching_messages', function (Blueprint $table): void {
        $table->id();
        $table->string('source_system', 64);
        $table->string('message_uuid', 36);
        $table->string('inspection_id', 128)->nullable();
        $table->foreignId('recipient_user_id')->constrained('users')->restrictOnDelete();
        $table->string('matched_identifier_type', 8);
        $table->string('subject');
        $table->text('body');
        $table->json('payload')->nullable();
        $table->string('idempotency_key', 128)->collation('binary');
        $table->char('request_hash', 64);
        $table->dateTime('received_at');
        $table->dateTime('expires_at');
        $table->dateTime('created_at');
        $table->dateTime('updated_at');
        $table->unique(['source_system', 'message_uuid']);
        $table->unique(['source_system', 'idempotency_key']);
        $table->index(['recipient_user_id', 'expires_at']);
    });

    Schema::create('coaching_message_nonces', function (Blueprint $table): void {
        $table->id();
        $table->string('source_system', 64);
        $table->string('nonce', 128)->collation('binary');
        $table->timestamp('request_timestamp');
        $table->timestamp('expires_at');
        $table->timestamps();
        $table->unique(['source_system', 'nonce']);
    });

    config()->set('services.coaching_messages.api_key', str_repeat('k', 32));
    config()->set('services.coaching_messages.shared_secret', str_repeat('s', 32));
    config()->set('services.coaching_messages.source_system', 'test-inspection-system');
    config()->set('services.coaching_messages.timestamp_tolerance_seconds', 300);
    config()->set('services.coaching_messages.max_body_bytes', 32768);
});

it('stores a NIPP message for a PJL account and calculates expiry from receipt time', function (): void {
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-NIPP-001');
    $recipient->forceFill(['jenis_petugas' => 'reguler'])->save();
    $payload = coachingMessagePayload([
        'recipient_nipp' => $recipient->nip,
        'recipient_user_id' => 999999,
    ]);

    $response = sendSignedCoachingMessage($this, $payload);

    $response->assertCreated()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('message_uuid', $payload['message_uuid']);

    $message = CoachingMessage::query()->sole();

    expect($message->recipient_user_id)->toBe($recipient->id)
        ->and($message->matched_identifier_type)->toBe('nipp')
        ->and($message->expires_at->equalTo($message->received_at->addHours(72)))->toBeTrue()
        ->and(preg_match('/\A[a-f0-9]{64}\z/', $message->request_hash))->toBe(1);
});

it('keeps message audit timestamps as datetime values and key comparisons case-sensitive', function (): void {
    expect(Schema::getColumnType('coaching_messages', 'received_at'))->toBe('datetime')
        ->and(Schema::getColumnType('coaching_messages', 'expires_at'))->toBe('datetime')
        ->and(Schema::getColumnType('coaching_messages', 'created_at'))->toBe('datetime')
        ->and(Schema::getColumnType('coaching_messages', 'updated_at'))->toBe('datetime');

    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-CASE-SENSITIVE-KEYS');
    $timestamp = now();
    $messageAttributes = [
        'source_system' => 'test-inspection-system',
        'inspection_id' => null,
        'recipient_user_id' => $recipient->id,
        'matched_identifier_type' => 'nipp',
        'subject' => 'Pesan audit',
        'body' => 'Isi pesan.',
        'payload' => null,
        'request_hash' => str_repeat('b', 64),
        'received_at' => $timestamp,
        'expires_at' => $timestamp->copy()->addHours(72),
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ];

    DB::table('coaching_messages')->insert([
        ...$messageAttributes,
        'message_uuid' => (string) Str::uuid(),
        'idempotency_key' => 'Idempotency-Key',
    ]);
    DB::table('coaching_messages')->insert([
        ...$messageAttributes,
        'message_uuid' => (string) Str::uuid(),
        'idempotency_key' => 'idempotency-key',
    ]);

    foreach (['Nonce-Token', 'nonce-token'] as $nonce) {
        DB::table('coaching_message_nonces')->insert([
            'source_system' => 'test-inspection-system',
            'nonce' => $nonce,
            'request_timestamp' => $timestamp,
            'expires_at' => $timestamp->copy()->addMinutes(11),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    expect(CoachingMessage::query()->count())->toBe(2)
        ->and(DB::table('coaching_message_nonces')->count())->toBe(2);
});

it('stores a NIPKWT message only for a wakilan PJL account', function (): void {
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-NIPKWT-001');
    $recipient->forceFill(['jenis_petugas' => 'wakilan'])->save();
    $payload = coachingMessagePayload([
        'recipient_nipp' => null,
        'recipient_nipkwt' => $recipient->nip,
    ]);

    sendSignedCoachingMessage($this, $payload)
        ->assertCreated()
        ->assertJsonPath('message_uuid', $payload['message_uuid']);

    expect(CoachingMessage::query()->sole()->recipient_user_id)->toBe($recipient->id)
        ->and(CoachingMessage::query()->sole()->matched_identifier_type)->toBe('nipkwt');
});

it('does not treat a wakilan identity as NIPP', function (): void {
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-WAKILAN-AS-NIPP');
    $recipient->forceFill(['jenis_petugas' => 'wakilan'])->save();

    sendSignedCoachingMessage(
        $this,
        coachingMessagePayload(['recipient_nipp' => $recipient->nip]),
    )->assertUnprocessable();

    expect(CoachingMessage::query()->count())->toBe(0);
});

it('does not resolve an Admin or Superadmin identity as a message recipient', function (string $role): void {
    $admin = $this->kaiUser($this->kaiRole($role), 'ACCOUNT-'.$role);
    $payload = coachingMessagePayload(['recipient_nipp' => $admin->nip]);

    sendSignedCoachingMessage($this, $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Payload tidak valid.')
        ->assertJsonPath('errors.recipient.0', 'Target penerima tidak dapat diverifikasi.');

    expect(CoachingMessage::query()->count())->toBe(0);
})->with(['admin', 'superadmin']);

it('does not accept an inactive PJL account as a message recipient', function (): void {
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-INACTIVE');
    $recipient->forceFill([
        'jenis_petugas' => 'reguler',
        'is_active' => false,
    ])->save();

    sendSignedCoachingMessage(
        $this,
        coachingMessagePayload(['recipient_nipp' => $recipient->nip]),
    )->assertUnprocessable()
        ->assertJsonPath('errors.recipient.0', 'Target penerima tidak dapat diverifikasi.');

    expect(CoachingMessage::query()->count())->toBe(0);
});

it('rejects two identities that resolve to different PJL accounts without storing either target', function (): void {
    $pjlRole = $this->kaiRole('pjl');
    $regular = $this->kaiUser($pjlRole, 'PJL-CONFLICT-NIPP');
    $regular->forceFill(['jenis_petugas' => 'reguler'])->save();
    $wakilan = $this->kaiUser($pjlRole, 'PJL-CONFLICT-NIPKWT');
    $wakilan->forceFill(['jenis_petugas' => 'wakilan'])->save();
    $payload = coachingMessagePayload([
        'recipient_nipp' => $regular->nip,
        'recipient_nipkwt' => $wakilan->nip,
    ]);

    sendSignedCoachingMessage($this, $payload)
        ->assertUnprocessable()
        ->assertJsonPath('errors.recipient.0', 'Target penerima tidak dapat diverifikasi.');

    expect(CoachingMessage::query()->count())->toBe(0);
});

it('accepts an old regular PJL account whose jenis_petugas value is null', function (): void {
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-LEGACY-REGULAR');
    $payload = coachingMessagePayload(['recipient_nipp' => $recipient->nip]);

    sendSignedCoachingMessage($this, $payload)->assertCreated();

    expect(CoachingMessage::query()->sole()->recipient_user_id)->toBe($recipient->id);
});

it('does not store a message when no supplied identity matches a PJL account', function (): void {
    sendSignedCoachingMessage(
        $this,
        coachingMessagePayload(['recipient_nipp' => 'UNKNOWN-NIPP']),
    )->assertUnprocessable();

    expect(CoachingMessage::query()->count())->toBe(0);
});

it('returns 401 when the HMAC signature is invalid and does not consume a nonce', function (): void {
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-BAD-SIGNATURE');
    $recipient->forceFill(['jenis_petugas' => 'reguler'])->save();
    $payload = coachingMessagePayload(['recipient_nipp' => $recipient->nip]);

    sendSignedCoachingMessage($this, $payload, signatureOverride: str_repeat('0', 64))
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Signature tidak valid.');

    expect(DB::table('coaching_message_nonces')->count())->toBe(0)
        ->and(CoachingMessage::query()->count())->toBe(0);
});

it('returns 401 when a signed timestamp falls outside the configured tolerance', function (): void {
    $payload = coachingMessagePayload(['recipient_nipp' => 'NO-ACCOUNT']);
    $staleTimestamp = (string) (now()->getTimestamp() - 301);

    sendSignedCoachingMessage($this, $payload, timestamp: $staleTimestamp)
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Request tidak valid.');

    expect(DB::table('coaching_message_nonces')->count())->toBe(0);
});

it('accepts a valid RFC3339 timestamp', function (): void {
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-RFC3339');
    $recipient->forceFill(['jenis_petugas' => 'reguler'])->save();
    $timestamp = now()->toIso8601String();

    sendSignedCoachingMessage(
        $this,
        coachingMessagePayload(['recipient_nipp' => $recipient->nip]),
        timestamp: $timestamp,
    )->assertCreated();

    expect(CoachingMessage::query()->count())->toBe(1);
});

it('rejects an impossible RFC3339 calendar date', function (): void {
    sendSignedCoachingMessage(
        $this,
        coachingMessagePayload(),
        timestamp: '2026-02-30T12:00:00+00:00',
    )->assertUnauthorized();

    expect(DB::table('coaching_message_nonces')->count())->toBe(0);
});

it('returns 401 when the same authenticated nonce is replayed', function (): void {
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-REPLAY');
    $recipient->forceFill(['jenis_petugas' => 'reguler'])->save();
    $payload = coachingMessagePayload(['recipient_nipp' => $recipient->nip]);
    $nonce = (string) Str::uuid();
    $timestamp = (string) now()->getTimestamp();

    sendSignedCoachingMessage($this, $payload, nonce: $nonce, timestamp: $timestamp)->assertCreated();
    sendSignedCoachingMessage($this, $payload, nonce: $nonce, timestamp: $timestamp)->assertUnauthorized();

    expect(CoachingMessage::query()->count())->toBe(1)
        ->and(DB::table('coaching_message_nonces')->count())->toBe(1);
});

it('returns 422 for malformed payloads and unexpected root fields', function (array $payload): void {
    sendSignedCoachingMessage($this, $payload)
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Payload tidak valid.');

    expect(CoachingMessage::query()->count())->toBe(0);
})->with([
    'missing both staff identities' => [
        array_merge(coachingMessagePayload(), ['recipient_nipp' => null, 'recipient_nipkwt' => null]),
    ],
    'unexpected field' => [
        array_merge(coachingMessagePayload(), ['role' => 'superadmin']),
    ],
]);

it('returns 200 for an identical idempotent retry without storing another message', function (): void {
    Event::fake([CoachingMessageReceived::class]);
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-IDEMPOTENT');
    $recipient->forceFill(['jenis_petugas' => 'reguler'])->save();
    $payload = coachingMessagePayload(['recipient_nipp' => $recipient->nip]);

    sendSignedCoachingMessage($this, $payload)->assertCreated();
    sendSignedCoachingMessage($this, array_reverse($payload, true))->assertOk()
        ->assertJsonPath('message_uuid', $payload['message_uuid']);

    expect(CoachingMessage::query()->count())->toBe(1)
        ->and(DB::table('coaching_message_nonces')->count())->toBe(2);

    Event::assertDispatchedTimes(CoachingMessageReceived::class, 1);
    Event::assertDispatched(
        CoachingMessageReceived::class,
        fn (CoachingMessageReceived $event): bool => $event->broadcastWith() === [
            'message_id' => (string) CoachingMessage::query()->sole()->getKey(),
        ],
    );
});

it('queues a broadcast only after the surrounding database transaction commits', function (): void {
    Queue::fake();
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-BROADCAST-AFTER-COMMIT');
    $recipient->forceFill(['jenis_petugas' => 'reguler'])->save();
    DB::beginTransaction();

    try {
        sendSignedCoachingMessage(
            $this,
            coachingMessagePayload(['recipient_nipp' => $recipient->nip]),
        )->assertCreated();

        $messageId = (string) CoachingMessage::query()->sole()->getKey();
        Queue::assertNothingPushed();
        DB::commit();

        Queue::assertPushed(
            BroadcastEvent::class,
            fn ($job): bool => $job->event instanceof CoachingMessageReceived
                && $job->event->broadcastWith() === ['message_id' => $messageId],
        );
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }
});

it('discards the pending broadcast when the surrounding transaction rolls back', function (): void {
    Queue::fake();
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-BROADCAST-ROLLBACK');
    $recipient->forceFill(['jenis_petugas' => 'reguler'])->save();
    DB::beginTransaction();

    sendSignedCoachingMessage(
        $this,
        coachingMessagePayload(['recipient_nipp' => $recipient->nip]),
    )->assertCreated();

    Queue::assertNothingPushed();
    DB::rollBack();

    Queue::assertNothingPushed();
    expect(CoachingMessage::query()->count())->toBe(0);
});

it('returns 409 when an idempotency key is reused with a different payload', function (): void {
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-IDEMPOTENCY-CONFLICT');
    $recipient->forceFill(['jenis_petugas' => 'reguler'])->save();
    $payload = coachingMessagePayload(['recipient_nipp' => $recipient->nip]);
    $changedPayload = array_merge($payload, ['body' => 'Isi pesan berbeda.']);

    sendSignedCoachingMessage($this, $payload)->assertCreated();
    sendSignedCoachingMessage($this, $changedPayload)->assertStatus(409);

    expect(CoachingMessage::query()->count())->toBe(1);
});

it('returns 409 when a message UUID is reused with a different payload and idempotency key', function (): void {
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-UUID-CONFLICT');
    $recipient->forceFill(['jenis_petugas' => 'reguler'])->save();
    $payload = coachingMessagePayload(['recipient_nipp' => $recipient->nip]);
    $changedPayload = array_merge($payload, [
        'body' => 'Isi pesan berbeda.',
        'idempotency_key' => (string) Str::uuid(),
    ]);

    sendSignedCoachingMessage($this, $payload)->assertCreated();
    sendSignedCoachingMessage($this, $changedPayload)->assertStatus(409);

    expect(CoachingMessage::query()->count())->toBe(1);
});

it('prunes only nonces older than the full 30-day retention and leaves messages intact', function (): void {
    $this->travelTo('2026-11-08 10:00:00');
    $cutoff = now()->subDays(30);
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-NONCE-RETENTION');
    $recipient->forceFill(['jenis_petugas' => 'reguler'])->save();
    $message = CoachingMessage::query()->create([
        'source_system' => 'test-inspection-system',
        'message_uuid' => (string) Str::uuid(),
        'recipient_user_id' => $recipient->id,
        'matched_identifier_type' => 'nipp',
        'subject' => 'Pesan audit',
        'body' => 'Isi yang harus dipertahankan.',
        'payload' => ['audit' => true],
        'idempotency_key' => (string) Str::uuid(),
        'request_hash' => str_repeat('a', 64),
        'received_at' => now(),
        'expires_at' => now()->addHours(72),
    ]);
    $nonceIds = [];

    foreach ([
        'expired-before-retention' => $cutoff->copy()->subSecond(),
        'exact-retention-boundary' => $cutoff->copy(),
        'inside-retention-window' => $cutoff->copy()->addSecond(),
        'not-expired' => now()->addDay(),
    ] as $nonce => $expiresAt) {
        $nonceIds[$nonce] = DB::table('coaching_message_nonces')->insertGetId([
            'source_system' => 'test-inspection-system',
            'nonce' => $nonce,
            'request_timestamp' => now(),
            'expires_at' => $expiresAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    expect(Artisan::call('app:prune-expired-coaching-message-nonces'))->toBe(0)
        ->and(DB::table('coaching_message_nonces')->where('id', $nonceIds['expired-before-retention'])->exists())->toBeFalse()
        ->and(DB::table('coaching_message_nonces')->whereIn('id', [
            $nonceIds['exact-retention-boundary'],
            $nonceIds['inside-retention-window'],
            $nonceIds['not-expired'],
        ])->count())->toBe(3)
        ->and(CoachingMessage::query()->find($message->id))->not->toBeNull();

    expect(Artisan::call('app:prune-expired-coaching-message-nonces'))->toBe(0)
        ->and(DB::table('coaching_message_nonces')->count())->toBe(3)
        ->and(CoachingMessage::query()->count())->toBe(1);
});

it('excludes a message from the active scope after its 72-hour expiry', function (): void {
    $this->travelTo('2026-11-08 10:00:00');
    $recipient = $this->kaiUser($this->kaiRole('pjl'), 'PJL-EXPIRY');
    $recipient->forceFill(['jenis_petugas' => 'reguler'])->save();
    $payload = coachingMessagePayload(['recipient_nipp' => $recipient->nip]);

    sendSignedCoachingMessage($this, $payload)->assertCreated();

    expect(CoachingMessage::query()->active()->count())->toBe(1);

    $this->travel(72)->hours();

    expect(CoachingMessage::query()->count())->toBe(1)
        ->and(CoachingMessage::query()->active()->count())->toBe(0);
});

it('logs a rejected signature reason without logging the key or message body', function (): void {
    Log::shouldReceive('warning')
        ->once()
        ->with('Coaching message request rejected.', ['reason' => 'invalid_signature']);

    sendSignedCoachingMessage(
        $this,
        coachingMessagePayload(['recipient_nipp' => 'UNRESOLVED-SECRET-TARGET']),
        signatureOverride: str_repeat('0', 64),
    )->assertUnauthorized();
});

it('does not disclose the attempted recipient identity in rejection responses or logs', function (): void {
    $identity = 'PRIVATE-NIPP-NOT-FOUND';
    Log::shouldReceive('notice')
        ->once()
        ->with('Coaching message target could not be verified.');

    $response = sendSignedCoachingMessage(
        $this,
        coachingMessagePayload(['recipient_nipp' => $identity]),
    )->assertUnprocessable()
        ->assertJsonPath('errors.recipient.0', 'Target penerima tidak dapat diverifikasi.');

    expect($response->getContent())->not->toContain($identity)
        ->and(CoachingMessage::query()->count())->toBe(0);
});

it('fails closed when receiver credentials are absent or too short', function (): void {
    Log::shouldReceive('critical')
        ->once()
        ->with('Coaching message receiver is not configured.');
    config()->set('services.coaching_messages.shared_secret', str_repeat('s', 31));

    sendSignedCoachingMessage($this, coachingMessagePayload())
        ->assertServiceUnavailable();

    expect(DB::table('coaching_message_nonces')->count())->toBe(0)
        ->and(CoachingMessage::query()->count())->toBe(0);
});

function coachingMessagePayload(array $overrides = []): array
{
    return array_merge([
        'message_uuid' => (string) Str::uuid(),
        'inspection_id' => 123,
        'recipient_nipp' => 'PJL-DEFAULT',
        'recipient_nipkwt' => null,
        'subject' => 'Pesan Pembinaan',
        'body' => 'Mohon periksa kembali kewaspadaan saat patroli.',
        'payload' => ['inspection_code' => 'JPL-2026-0001'],
        'expires_at' => now()->addDays(2)->toIso8601String(),
        'idempotency_key' => (string) Str::uuid(),
    ], $overrides);
}

function coachingMessageJson(array $payload): string
{
    return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

function sendSignedCoachingMessage(
    TestCase $test,
    array $payload,
    ?string $nonce = null,
    ?string $timestamp = null,
    ?string $signatureOverride = null,
    ?string $apiKeyOverride = null,
): TestResponse {
    $timestamp ??= (string) now()->getTimestamp();
    $nonce ??= (string) Str::uuid();
    $rawBody = coachingMessageJson($payload);
    $signature = $signatureOverride ?? hash_hmac(
        'sha256',
        $timestamp.':'.$nonce.':'.$rawBody,
        (string) config('services.coaching_messages.shared_secret'),
    );

    return $test->call(
        'POST',
        '/api/v1/coaching-messages/receive',
        [],
        [],
        [],
        [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_API_KEY' => $apiKeyOverride ?? (string) config('services.coaching_messages.api_key'),
            'HTTP_X_TIMESTAMP' => $timestamp,
            'HTTP_X_NONCE' => $nonce,
            'HTTP_X_SIGNATURE' => $signature,
        ],
        $rawBody,
    );
}
