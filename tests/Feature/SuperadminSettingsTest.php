<?php

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('label')->nullable();
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table) {
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

function createSettingsTestUser(
    string $roleName,
    string $nip,
    ?string $email = null,
    string $password = 'password'
): User {
    $role = Role::firstOrCreate(
        ['name' => $roleName],
        ['label' => ucfirst($roleName)]
    );

    return User::create([
        'role_id' => $role->id,
        'nip' => $nip,
        'name' => ucfirst($roleName).' Test',
        'email' => $email,
        'password' => Hash::make($password),
        'is_active' => true,
    ]);
}

/**
 * @param  list<list<string>>  $rows
 * @return array{upload: UploadedFile, path: string}
 */
function createUserImportWorkbook(array $rows): array
{
    $path = tempnam(sys_get_temp_dir(), 'kai-user-import-');
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['NIP', 'Nama', 'Email', 'No HP', 'Role', 'Status'],
        ...$rows,
    ]);

    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return [
        'upload' => new UploadedFile(
            $path,
            'users.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        ),
        'path' => $path,
    ];
}

it('keeps the superadmin role and active state when self edit submits different values', function () {
    $superadmin = createSettingsTestUser('superadmin', 'SA-001');
    $admin = createSettingsTestUser('admin', 'AD-001', 'shared@example.test');

    $this->actingAs($superadmin)
        ->put(route('superadmin.users.update', $superadmin), [
            'nip' => $superadmin->nip,
            'name' => 'Nama Diperbarui',
            'email' => 'shared@example.test',
            'phone' => null,
            'role_id' => $admin->role_id,
            'is_active' => '0',
        ])
        ->assertRedirect(route('superadmin.users.index'))
        ->assertSessionHas('error', 'Role dan status aktif akun sendiri tidak dapat diubah. Perubahan field lain yang valid tetap diproses.');

    $superadmin->refresh();

    expect($superadmin->name)->toBe('Nama Diperbarui')
        ->and($superadmin->email)->toBe('shared@example.test')
        ->and($superadmin->role_id)->not->toBe($admin->role_id)
        ->and($superadmin->is_active)->toBeTrue();

    $this->patch(route('superadmin.users.toggle-status', $superadmin))
        ->assertSessionHas('error', 'Anda tidak dapat menonaktifkan akun sendiri.');

    expect($superadmin->fresh()->is_active)->toBeTrue();
});

it('allows duplicate email and phone values while keeping NIP unique in user management', function () {
    $superadmin = createSettingsTestUser('superadmin', 'SA-002');
    $adminRole = Role::create(['name' => 'admin', 'label' => 'Admin']);
    $sharedData = [
        'name' => 'Petugas A',
        'email' => 'shared@example.test',
        'phone' => '08123456789',
        'role_id' => $adminRole->id,
        'is_active' => '1',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $this->actingAs($superadmin)
        ->post(route('superadmin.users.store'), ['nip' => 'USER-001', ...$sharedData])
        ->assertRedirect(route('superadmin.users.index'));

    $this->post(route('superadmin.users.store'), ['nip' => 'USER-002', ...$sharedData])
        ->assertRedirect(route('superadmin.users.index'));

    $this->assertDatabaseCount('users', 3);
    $this->assertDatabaseCount('activity_logs', 2);

    $this->post(route('superadmin.users.store'), ['nip' => 'USER-001', ...$sharedData])
        ->assertSessionHasErrors('nip');

    $this->assertDatabaseCount('users', 3);
});

it('accepts repeated email values in user import while rejecting duplicate NIP rows', function () {
    $superadmin = createSettingsTestUser('superadmin', 'SA-003', 'shared@example.test');
    Role::create(['name' => 'pjl', 'label' => 'PJL']);
    $workbook = createUserImportWorkbook([
        ['IMPORT-001', 'Petugas Satu', 'shared@example.test', '08111111111', 'pjl', 'aktif'],
        ['IMPORT-002', 'Petugas Dua', 'shared@example.test', '08111111111', 'pjl', 'aktif'],
        ['IMPORT-001', 'Petugas Duplikat', 'shared@example.test', '08111111111', 'pjl', 'aktif'],
    ]);
    $upload = $workbook['upload'];
    $path = $workbook['path'];

    try {
        $preview = $this->actingAs($superadmin)
            ->post(route('superadmin.users.import.preview.store'), ['file' => $upload])
            ->assertOk()
            ->assertJsonPath('valid', 2)
            ->assertJsonPath('invalid', 1);

        expect(collect($preview->json('errors'))->first()['errors'])
            ->toContain('NIP duplikat dengan baris lain dalam file Excel.');

        $this->postJson(route('superadmin.users.import.confirm'), [
            'token' => $preview->json('token'),
        ])
            ->assertOk()
            ->assertJsonPath('imported', 2)
            ->assertJsonPath('failed', 0);

        $this->assertDatabaseCount('users', 3);
        $this->assertDatabaseHas('users', ['nip' => 'IMPORT-001', 'email' => 'shared@example.test']);
        $this->assertDatabaseHas('users', ['nip' => 'IMPORT-002', 'email' => 'shared@example.test']);
    } finally {
        @unlink($path);
    }
});

it('lets a superadmin update their own profile and password without exposing password values to activity logs', function () {
    $superadmin = createSettingsTestUser('superadmin', 'SA-004', 'before@example.test', 'old-password');
    createSettingsTestUser('admin', 'AD-SETTINGS-001', 'after@example.test');

    $this->actingAs($superadmin)
        ->get(route('superadmin.settings.index'))
        ->assertOk()
        ->assertViewIs('superadmin.settings.index')
        ->assertSeeText('Profil Superadmin')
        ->assertSeeText('Keamanan Akun');

    $this->put(route('superadmin.settings.profile.update'), [
        'name' => 'Superadmin Baru',
        'email' => 'after@example.test',
        'phone' => '08123456789',
    ])->assertRedirect(route('superadmin.settings.index').'#profil-superadmin');

    $this->put(route('superadmin.settings.password.update'), [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('superadmin.settings.index').'#keamanan-akun')
        ->assertSessionHas('password_success', 'Password berhasil diperbarui.');

    $superadmin->refresh();

    expect($superadmin->name)->toBe('Superadmin Baru')
        ->and($superadmin->email)->toBe('after@example.test')
        ->and($superadmin->phone)->toBe('08123456789')
        ->and(Hash::check('new-password-123', $superadmin->password))->toBeTrue();

    $passwordLog = ActivityLog::where('action', 'password_changed')->sole();
    expect($passwordLog->module)->toBe('account_security')
        ->and($passwordLog->description)->not->toContain('old-password')
        ->and($passwordLog->description)->not->toContain('new-password-123')
        ->and($passwordLog->old_values)->toBeNull()
        ->and($passwordLog->new_values)->toBeNull();
});

it('rejects an incorrect current password and an unconfirmed new password', function () {
    $superadmin = createSettingsTestUser('superadmin', 'SA-005', null, 'old-password');

    $this->actingAs($superadmin)
        ->put(route('superadmin.settings.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
        ->assertSessionHasErrors('current_password');

    $this->put(route('superadmin.settings.password.update'), [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'different-password',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('old-password', $superadmin->fresh()->password))->toBeTrue()
        ->and(ActivityLog::where('action', 'password_changed')->exists())->toBeFalse();
});

it('forbids admin and PJL users from viewing or changing superadmin settings', function () {
    foreach (['admin', 'pjl'] as $roleName) {
        $user = createSettingsTestUser($roleName, strtoupper($roleName).'-006');

        $this->actingAs($user)
            ->get(route('superadmin.settings.index'))
            ->assertForbidden();

        $this->put(route('superadmin.settings.profile.update'), [
            'name' => 'Tidak Diizinkan',
        ])->assertForbidden();

        $this->put(route('superadmin.settings.password.update'), [])->assertForbidden();
    }
});
