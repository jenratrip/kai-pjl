<?php

use App\Models\CoachingMessage;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\KaiTestRecords;

uses(KaiTestRecords::class);

beforeEach(function (): void {
    expect(DB::connection()->getDriverName())->toBe('sqlite')
        ->and(DB::connection()->getDatabaseName())->toBe(':memory:');

    $this->withoutVite();

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
        $table->dateTime('read_at')->nullable();
        $table->dateTime('created_at');
        $table->dateTime('updated_at');
        $table->unique(['source_system', 'message_uuid']);
        $table->unique(['source_system', 'idempotency_key']);
        $table->index(['recipient_user_id', 'expires_at']);
        $table->index(['recipient_user_id', 'read_at', 'expires_at']);
    });

    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'test-reverb-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-reverb-secret');
    config()->set('broadcasting.connections.reverb.app_id', 'test-reverb-app');
    require base_path('routes/channels.php');

});

it('authorizes only the active PJL account on its own private coaching message channel', function (): void {
    $owner = $this->kaiUser($this->kaiRole('pjl'), 'COACHING-CHANNEL-OWNER');
    $channel = "private-users.{$owner->getKey()}.coaching-messages";
    expect($owner->hasRole('pjl'))->toBeTrue()
        ->and($owner->is_active)->toBeTrue();

    $this->actingAs($owner)
        ->post('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => $channel,
        ])
        ->assertOk();
});

it('rejects another PJL account from the owner private coaching message channel', function (): void {
    $role = $this->kaiRole('pjl');
    $owner = $this->kaiUser($role, 'COACHING-CHANNEL-OWNER-OTHER');
    $other = $this->kaiUser($role, 'COACHING-CHANNEL-OTHER');

    $this->actingAs($other)
        ->post('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => "private-users.{$owner->getKey()}.coaching-messages",
        ])
        ->assertForbidden();
});

it('rejects Admin and Superadmin from private coaching message channels', function (string $roleName): void {
    $user = $this->kaiUser($this->kaiRole($roleName), 'COACHING-CHANNEL-DENIED-'.$roleName);

    $this->actingAs($user)
        ->post('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => "private-users.{$user->getKey()}.coaching-messages",
        ])
        ->assertForbidden();
})->with(['admin', 'superadmin']);

it('rejects an inactive PJL from its private coaching message channel', function (): void {
    $user = $this->kaiUser(
        $this->kaiRole('pjl'),
        'COACHING-CHANNEL-INACTIVE',
        ['is_active' => false],
    );

    $this->actingAs($user)
        ->post('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => "private-users.{$user->getKey()}.coaching-messages",
        ])
        ->assertForbidden();
});

it('rejects non-PJL access even when the configured broadcaster only logs events', function (): void {
    config()->set('broadcasting.default', 'log');
    $user = $this->kaiUser($this->kaiRole('admin'), 'COACHING-CHANNEL-LOG-DRIVER');

    $this->actingAs($user)
        ->post('/broadcasting/auth', [
            'socket_id' => '123.456',
            'channel_name' => "private-users.{$user->getKey()}.coaching-messages",
        ])
        ->assertForbidden();
});

it('allows the active PJL owner to list and open only their active messages', function (): void {
    $role = $this->kaiRole('pjl');
    $owner = $this->kaiUser($role, 'COACHING-INBOX-OWNER');
    $other = $this->kaiUser($role, 'COACHING-INBOX-OTHER');
    $ownMessage = coachingInboxMessage($owner, ['subject' => 'Pesan untuk pemilik']);
    coachingInboxMessage($other, ['subject' => 'Rahasia PJL lain']);

    $this->actingAs($owner)
        ->get(route('user.coaching-messages.index'))
        ->assertOk()
        ->assertSeeText('Pesan untuk pemilik')
        ->assertSeeText('1 baru')
        ->assertDontSeeText('Rahasia PJL lain');

    $this->get(route('user.coaching-messages.show', $ownMessage))
        ->assertOk()
        ->assertSeeText('Isi pesan pengujian.');
});

it('returns not found when a PJL requests another users message detail', function (): void {
    $role = $this->kaiRole('pjl');
    $owner = $this->kaiUser($role, 'COACHING-DETAIL-OWNER');
    $other = $this->kaiUser($role, 'COACHING-DETAIL-OTHER');
    $foreignMessage = coachingInboxMessage($other, ['subject' => 'Isi rahasia']);

    $this->actingAs($owner)
        ->get(route('user.coaching-messages.show', $foreignMessage))
        ->assertNotFound()
        ->assertDontSeeText('Isi rahasia');
});

it('denies Admin and Superadmin access to the inbox', function (string $roleName): void {
    $user = $this->kaiUser($this->kaiRole($roleName), 'COACHING-DENIED-'.$roleName);

    $this->actingAs($user)
        ->get(route('user.coaching-messages.index'))
        ->assertForbidden();
})->with(['admin', 'superadmin']);

it('denies an inactive PJL access even when its authenticated session remains valid', function (): void {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'COACHING-INACTIVE', ['is_active' => false]);

    $this->actingAs($user)
        ->get(route('user.coaching-messages.index'))
        ->assertForbidden();
});

it('hides expired messages from both the list and detail without deleting them', function (): void {
    $this->travelTo('2026-11-08 10:00:00');
    $owner = $this->kaiUser($this->kaiRole('pjl'), 'COACHING-EXPIRED');
    $expiredMessage = coachingInboxMessage($owner, [
        'subject' => 'Pesan kedaluwarsa',
        'received_at' => now()->subHours(73),
        'expires_at' => now()->subHour(),
    ]);

    $this->actingAs($owner)
        ->get(route('user.coaching-messages.index'))
        ->assertOk()
        ->assertSeeText('Belum ada pesan aktif')
        ->assertDontSeeText('Pesan kedaluwarsa');

    $this->get(route('user.coaching-messages.show', $expiredMessage))
        ->assertNotFound();

    $this->assertModelExists($expiredMessage);
});

it('marks an active message as read only through its owners POST action', function (): void {
    $owner = $this->kaiUser($this->kaiRole('pjl'), 'COACHING-MARK-READ');
    $message = coachingInboxMessage($owner);

    $this->actingAs($owner)
        ->post(route('user.coaching-messages.read', $message))
        ->assertRedirect(route('user.coaching-messages.show', $message))
        ->assertSessionHas('success', 'Pesan ditandai sudah dibaca.');

    expect($message->fresh()->read_at)->not->toBeNull();

    $this->get(route('user.coaching-messages.index'))
        ->assertOk()
        ->assertSeeText('Sudah dibaca')
        ->assertSeeText('0 belum dibaca');
});

it('does not allow another PJL to mark a message as read', function (): void {
    $role = $this->kaiRole('pjl');
    $owner = $this->kaiUser($role, 'COACHING-READ-OWNER');
    $other = $this->kaiUser($role, 'COACHING-READ-OTHER');
    $message = coachingInboxMessage($owner);

    $this->actingAs($other)
        ->post(route('user.coaching-messages.read', $message))
        ->assertNotFound();

    expect($message->fresh()->read_at)->toBeNull();
});

it('renders the empty state when the PJL has no messages and does not require an active Dinas session', function (): void {
    $user = $this->kaiUser($this->kaiRole('pjl'), 'COACHING-EMPTY');

    $this->actingAs($user)
        ->get(route('user.coaching-messages.index'))
        ->assertOk()
        ->assertSeeText('Belum ada pesan aktif')
        ->assertSeeText('Pesan pembinaan yang diterima akan muncul di halaman ini.');
});

function coachingInboxMessage(User $recipient, array $attributes = []): CoachingMessage
{
    $receivedAt = $attributes['received_at'] ?? now();

    return CoachingMessage::query()->create(array_merge([
        'source_system' => 'inbox-test',
        'message_uuid' => (string) Str::uuid(),
        'recipient_user_id' => $recipient->getKey(),
        'matched_identifier_type' => 'nipp',
        'subject' => 'Pesan pembinaan',
        'body' => 'Isi pesan pengujian.',
        'idempotency_key' => (string) Str::uuid(),
        'request_hash' => str_repeat('a', 64),
        'received_at' => $receivedAt,
        'expires_at' => $attributes['expires_at'] ?? $receivedAt->copy()->addHours(72),
    ], $attributes));
}
