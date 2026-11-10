<?php

use App\Models\Role;
use App\Models\User;
use App\Notifications\AccountPasswordResetNotification;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    config(['mail.default' => 'smtp']);

    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
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
        $table->unsignedBigInteger('role_id');
        $table->string('password');
        $table->boolean('is_active')->default(true);
        $table->rememberToken();
        $table->timestamp('email_verified_at')->nullable();
        $table->timestamps();
    });

    Schema::create('user_password_reset_tokens', function (Blueprint $table): void {
        $table->unsignedBigInteger('user_id')->primary();
        $table->char('email_hash', 64);
        $table->char('token', 64);
        $table->timestamp('created_at')->nullable();
    });

    Schema::create('activity_logs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('action');
        $table->string('module');
        $table->text('description')->nullable();
        $table->string('subject_type')->nullable();
        $table->unsignedBigInteger('subject_id')->nullable();
        $table->json('old_values')->nullable();
        $table->json('new_values')->nullable();
        $table->string('ip_address', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->timestamps();
    });
});

function passwordRecoveryUser(string $nip, string $email, string $role = 'admin'): User
{
    $roleRecord = Role::query()->firstOrCreate(
        ['name' => $role],
        ['label' => ucfirst($role)],
    );

    return User::query()->create([
        'nip' => $nip,
        'name' => 'User '.$nip,
        'email' => $email,
        'role_id' => $roleRecord->id,
        'password' => 'old-password',
        'is_active' => true,
    ]);
}

function passwordRecoveryTokenFor(User $user): string
{
    $token = null;

    Notification::assertSentTo(
        $user,
        AccountPasswordResetNotification::class,
        function (AccountPasswordResetNotification $notification) use ($user, &$token): bool {
            $mail = $notification->toMail($user);
            $token = basename((string) parse_url($mail->actionUrl, PHP_URL_PATH));

            return $mail->actionText === 'Atur ulang kata sandi';
        },
    );

    return (string) $token;
}

it('issues separate account-bound reset tokens for two users with the same email', function (): void {
    Notification::fake();

    $firstUser = passwordRecoveryUser('NIP-RESET-001', 'shared@example.test');
    $secondUser = passwordRecoveryUser('NIP-RESET-002', 'shared@example.test', 'pjl');

    $this->post(route('password.email'), [
        'nip' => $firstUser->nip,
        'email' => 'SHARED@example.test',
    ])->assertRedirect()
        ->assertSessionHas('status');

    $firstToken = passwordRecoveryTokenFor($firstUser);
    $firstStoredToken = DB::table('user_password_reset_tokens')->where('user_id', $firstUser->id)->first();

    expect($firstStoredToken->token)->toBe(hash('sha256', $firstToken));
    expect($firstStoredToken->email_hash)->toBe(hash('sha256', 'shared@example.test'));
    expect(DB::table('user_password_reset_tokens')->where('user_id', $secondUser->id)->exists())->toBeFalse();
    Notification::assertNotSentTo($secondUser, AccountPasswordResetNotification::class);

    $this->post(route('password.email'), [
        'nip' => $secondUser->nip,
        'email' => 'shared@example.test',
    ])->assertRedirect()
        ->assertSessionHas('status');

    $secondToken = passwordRecoveryTokenFor($secondUser);

    expect($firstToken)->not->toBe($secondToken);
    expect(DB::table('user_password_reset_tokens')->where('user_id', $secondUser->id)->value('token'))
        ->toBe(hash('sha256', $secondToken));
});

it('does not reveal whether an NIPP/NIPKWT and email pair identifies an account', function (): void {
    Notification::fake();

    $user = passwordRecoveryUser('NIP-RESET-003', 'person@example.test');
    $matchingMessage = null;

    foreach ([
        ['nip' => $user->nip, 'email' => 'wrong@example.test'],
        ['nip' => 'NIP-UNKNOWN', 'email' => 'person@example.test'],
    ] as $credentials) {
        $response = $this->post(route('password.email'), $credentials)->assertRedirect();
        $message = $response->getSession()->get('status');

        $matchingMessage ??= $message;
        expect($message)->toBe($matchingMessage);
    }

    Notification::assertNothingSent();
    expect(DB::table('user_password_reset_tokens')->count())->toBe(0);
});

it('prevents one account token from changing another account password and allows the owner to reset once', function (): void {
    Notification::fake();

    $firstUser = passwordRecoveryUser('NIP-RESET-004', 'shared-2@example.test');
    $secondUser = passwordRecoveryUser('NIP-RESET-005', 'shared-2@example.test', 'pjl');

    $this->post(route('password.email'), ['nip' => $firstUser->nip, 'email' => $firstUser->email]);
    $token = passwordRecoveryTokenFor($firstUser);
    $oldFirstPassword = $firstUser->password;
    $oldSecondPassword = $secondUser->password;

    $this->post(route('password.update', ['user' => $secondUser->id, 'token' => $token]), [
        'password' => 'new-password-001',
        'password_confirmation' => 'new-password-001',
    ])->assertRedirect(route('password.request'));

    expect($firstUser->fresh()->password)->toBe($oldFirstPassword);
    expect($secondUser->fresh()->password)->toBe($oldSecondPassword);

    $this->post(route('password.update', ['user' => $firstUser->id, 'token' => $token]), [
        'password' => 'new-password-001',
        'password_confirmation' => 'new-password-001',
    ])->assertRedirect(route('login'));

    expect(Hash::check('new-password-001', $firstUser->fresh()->password))->toBeTrue();
    expect(DB::table('user_password_reset_tokens')->where('user_id', $firstUser->id)->exists())->toBeFalse();

    $this->post(route('password.update', ['user' => $firstUser->id, 'token' => $token]), [
        'password' => 'new-password-002',
        'password_confirmation' => 'new-password-002',
    ])->assertRedirect(route('password.request'));

    $this->post(route('login.store'), [
        'nip' => $firstUser->nip,
        'password' => 'new-password-001',
    ])->assertRedirect(route('admin.dashboard'));
});

it('rejects expired reset tokens and invalidates a token when the account email changes', function (): void {
    Notification::fake();

    $user = passwordRecoveryUser('NIP-RESET-006', 'expired@example.test');
    $this->post(route('password.email'), ['nip' => $user->nip, 'email' => $user->email]);
    $token = passwordRecoveryTokenFor($user);

    $this->travel(61)->minutes();

    $this->get(route('password.reset', ['user' => $user->id, 'token' => $token]))
        ->assertRedirect(route('password.request'));

    $this->travelBack();
    $this->post(route('password.email'), ['nip' => $user->nip, 'email' => $user->email]);
    $token = passwordRecoveryTokenFor($user);
    $user->forceFill(['email' => 'changed@example.test'])->save();

    $this->post(route('password.update', ['user' => $user->id, 'token' => $token]), [
        'password' => 'new-password-003',
        'password_confirmation' => 'new-password-003',
    ])->assertRedirect(route('password.request'));

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('requires a matching password confirmation', function (): void {
    Notification::fake();

    $user = passwordRecoveryUser('NIP-RESET-007', 'confirm@example.test');
    $this->post(route('password.email'), ['nip' => $user->nip, 'email' => $user->email]);
    $token = passwordRecoveryTokenFor($user);

    $this->from(route('password.reset', ['user' => $user->id, 'token' => $token]))
        ->post(route('password.update', ['user' => $user->id, 'token' => $token]), [
            'password' => 'new-password-004',
            'password_confirmation' => 'different-password',
        ])
        ->assertSessionHasErrors('password');

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('throttles repeated password reset requests', function (): void {
    $request = ['nip' => 'NIP-UNKNOWN', 'email' => 'unknown@example.test'];

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('password.email'), $request)->assertRedirect();
    }

    $this->post(route('password.email'), $request)->assertTooManyRequests();
});

it('rolls back the reset token when mail delivery fails', function (): void {
    $user = passwordRecoveryUser('NIP-RESET-008', 'delivery@example.test');
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('send')->once()->andThrow(new RuntimeException('transport failure'));
    app()->instance(Dispatcher::class, $dispatcher);

    $this->post(route('password.email'), ['nip' => $user->nip, 'email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(DB::table('user_password_reset_tokens')->where('user_id', $user->id)->exists())->toBeFalse();
});

it('refuses log mail transports so reset tokens are not written to application logs', function (): void {
    Notification::fake();

    $user = passwordRecoveryUser('NIP-RESET-009', 'log-transport@example.test');
    config(['mail.default' => 'log']);

    $this->post(route('password.email'), ['nip' => $user->nip, 'email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertNothingSent();
    expect(DB::table('user_password_reset_tokens')->where('user_id', $user->id)->exists())->toBeFalse();
});
