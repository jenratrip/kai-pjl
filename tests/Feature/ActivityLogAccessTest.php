<?php

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('label')->nullable();
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('nip')->nullable();
        $table->string('name');
        $table->string('email')->nullable();
        $table->string('phone')->nullable();
        $table->string('password');
        $table->unsignedBigInteger('role_id')->nullable();
        $table->boolean('is_active')->default(true);
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('activity_logs', function (Blueprint $table) {
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

function createActivityLogAccessUser(string $roleName, string $nip): User
{
    $role = Role::create([
        'name' => $roleName,
        'label' => ucfirst($roleName),
    ]);

    return User::create([
        'nip' => $nip,
        'name' => ucfirst($roleName).' Activity Test',
        'email' => $roleName.'@example.test',
        'password' => 'password',
        'role_id' => $role->id,
        'is_active' => true,
    ]);
}

function createActivityLogRecord(array $attributes = []): ActivityLog
{
    $activity = ActivityLog::create(array_merge([
        'action' => 'update',
        'module' => 'users',
        'description' => 'Akun pengguna diperbarui.',
        'old_values' => null,
        'new_values' => null,
    ], $attributes));

    $timestamps = array_intersect_key($attributes, array_flip(['created_at', 'updated_at']));

    if ($timestamps !== []) {
        $activity->forceFill($timestamps)->save();
    }

    return $activity;
}

it('allows a Superadmin to view the Activity Log list and detail', function () {
    $superadmin = createActivityLogAccessUser('superadmin', 'SA-ACTIVITY-001');
    $activity = createActivityLogRecord([
        'user_id' => $superadmin->id,
        'description' => 'Pembaruan operasional tercatat.',
    ]);

    $this->withoutVite()
        ->actingAs($superadmin)
        ->get(route('superadmin.activities.index'))
        ->assertOk()
        ->assertSeeText('Pembaruan operasional tercatat.');

    $this->get(route('superadmin.activities.show', $activity))
        ->assertOk()
        ->assertSeeText('Detail Aktivitas')
        ->assertSeeText('Pembaruan operasional tercatat.');
});

it('denies Admin and PJL access to Activity Log list and detail', function (string $roleName, string $nip) {
    $user = createActivityLogAccessUser($roleName, $nip);
    $activity = createActivityLogRecord();

    $this->actingAs($user)
        ->get(route('superadmin.activities.index'))
        ->assertForbidden();

    $this->getJson(route('superadmin.activities.index'))
        ->assertForbidden();

    $this->get(route('superadmin.activities.show', $activity))
        ->assertForbidden();

    $this->getJson(route('superadmin.activities.show', $activity))
        ->assertForbidden();
})->with([
    'Admin' => ['admin', 'AD-ACTIVITY-001'],
    'PJL' => ['pjl', 'PJL-ACTIVITY-001'],
]);

it('redirects browser guests to login and returns unauthorized for JSON guests', function () {
    $this->get(route('superadmin.activities.index'))
        ->assertRedirect(route('login'));

    $this->getJson(route('superadmin.activities.index'))
        ->assertUnauthorized();
});

it('applies valid Activity Log filters together', function () {
    $superadmin = createActivityLogAccessUser('superadmin', 'SA-ACTIVITY-002');
    createActivityLogRecord([
        'action' => 'import',
        'module' => 'jadwal_ka',
        'description' => 'Batch jadwal baru dikonfirmasi.',
        'created_at' => '2026-11-01 10:00:00',
        'updated_at' => '2026-11-01 10:00:00',
    ]);
    createActivityLogRecord([
        'action' => 'update',
        'module' => 'users',
        'description' => 'Akun diperbarui.',
        'created_at' => '2026-11-05 10:00:00',
        'updated_at' => '2026-11-05 10:00:00',
    ]);

    $this->withoutVite()
        ->actingAs($superadmin)
        ->get(route('superadmin.activities.index', [
            'search' => 'Batch jadwal',
            'module' => 'jadwal_ka',
            'action' => 'import',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-03',
        ]))
        ->assertOk()
        ->assertSeeText('Batch jadwal baru dikonfirmasi.')
        ->assertDontSeeText('Akun diperbarui.');
});

it('rejects invalid dates, reversed ranges, and oversized search input', function () {
    $superadmin = createActivityLogAccessUser('superadmin', 'SA-ACTIVITY-003');

    $this->actingAs($superadmin)
        ->getJson(route('superadmin.activities.index', ['start_date' => '2026-02-30']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('start_date');

    $this->getJson(route('superadmin.activities.index', [
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-09',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('end_date');

    $this->getJson(route('superadmin.activities.index', ['search' => str_repeat('a', 121)]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('search');
});

it('binds search input safely and rejects invalid filter types without server errors', function () {
    $superadmin = createActivityLogAccessUser('superadmin', 'SA-ACTIVITY-004');
    createActivityLogRecord(['description' => 'Aman tercatat.']);

    $this->withoutVite()
        ->actingAs($superadmin)
        ->get(route('superadmin.activities.index', ['search' => "' OR 1=1 --"]))
        ->assertOk()
        ->assertDontSeeText('Aman tercatat.');

    $this->getJson(route('superadmin.activities.index', ['module' => ['users']]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('module');
});

it('redacts sensitive values from historical detail payloads without changing stored logs', function () {
    $superadmin = createActivityLogAccessUser('superadmin', 'SA-ACTIVITY-005');
    $activity = createActivityLogRecord([
        'description' => '<script>alert("xss")</script>',
        'old_values' => [
            'nip' => 'OLD-NIP-SECRET',
            'name' => 'Nama Rahasia',
            'profile' => [
                'email' => 'private@example.test',
                'token' => 'secret-token',
            ],
            'status' => 'active',
        ],
        'new_values' => ['password_hash' => 'private-hash'],
    ]);

    $this->withoutVite()
        ->actingAs($superadmin)
        ->get(route('superadmin.activities.show', $activity))
        ->assertOk()
        ->assertDontSee('OLD-NIP-SECRET', false)
        ->assertDontSee('Nama Rahasia', false)
        ->assertDontSee('private@example.test', false)
        ->assertDontSee('secret-token', false)
        ->assertDontSee('private-hash', false)
        ->assertDontSee('<script>alert("xss")</script>', false)
        ->assertSee('[disamarkan]');

    expect($activity->fresh()->old_values['nip'])->toBe('OLD-NIP-SECRET')
        ->and($activity->fresh()->old_values['profile']['token'])->toBe('secret-token');
});

it('exposes only read-only GET routes for Activity Log', function () {
    $listMethods = Route::getRoutes()
        ->getByName('superadmin.activities.index')
        ->methods();
    $detailMethods = Route::getRoutes()
        ->getByName('superadmin.activities.show')
        ->methods();

    expect($listMethods)->toContain('GET', 'HEAD')
        ->and(array_diff($listMethods, ['GET', 'HEAD']))->toBeEmpty()
        ->and($detailMethods)->toContain('GET', 'HEAD')
        ->and(array_diff($detailMethods, ['GET', 'HEAD']))->toBeEmpty();

    $activityRoutes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route): bool => str_starts_with($route->uri(), 'superadmin/activities'));

    expect($activityRoutes)->toHaveCount(2)
        ->and($activityRoutes->flatMap(fn ($route) => $route->methods())->unique()->all())
        ->toBe(['GET', 'HEAD']);
});
