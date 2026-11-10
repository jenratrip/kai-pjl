<?php

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
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

function createEmailPolicyUser(string $roleName, string $nip, ?string $email = null): User
{
    $role = Role::firstOrCreate(
        ['name' => $roleName],
        ['label' => ucfirst($roleName)]
    );

    return User::create([
        'role_id' => $role->id,
        'nip' => $nip,
        'name' => ucfirst($roleName).' Test '.$nip,
        'email' => $email,
        'password' => Hash::make('password'),
        'is_active' => true,
    ]);
}

/**
 * @param  list<list<string>>  $rows
 * @return array{upload: UploadedFile, path: string}
 */
function createAdminUserImportWorkbook(array $rows): array
{
    $path = tempnam(sys_get_temp_dir(), 'kai-admin-user-import-');
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([
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

it('allows Admin to update a managed user to an email already used by another account', function () {
    $admin = createEmailPolicyUser('admin', 'ADMIN-EMAIL-001');
    $managedPjl = createEmailPolicyUser('pjl', 'PJL-EMAIL-001');
    createEmailPolicyUser('pjl', 'PJL-EMAIL-002', 'shared@example.test');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $managedPjl->id), [
            'nip' => $managedPjl->nip,
            'name' => $managedPjl->name,
            'email' => 'shared@example.test',
            'phone' => '08123456789',
            'role' => 'pjl',
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($managedPjl->fresh()->email)->toBe('shared@example.test');
});

it('imports Admin PJL accounts that share an email while continuing to reject duplicate NIP', function () {
    $admin = createEmailPolicyUser('admin', 'ADMIN-EMAIL-002');
    createEmailPolicyUser('pjl', 'PJL-EMAIL-003', 'shared@example.test');
    $workbook = createAdminUserImportWorkbook([
        ['ADMIN-IMPORT-001', 'PJL Satu', 'shared@example.test', '08111111111', 'pjl', 'aktif'],
        ['ADMIN-IMPORT-002', 'PJL Dua', 'shared@example.test', '08111111111', 'pjl', 'aktif'],
        ['ADMIN-IMPORT-001', 'PJL Duplikat', 'shared@example.test', '08111111111', 'pjl', 'aktif'],
    ]);

    try {
        $preview = $this->actingAs($admin)
            ->post(route('admin.users.import.preview'), ['file' => $workbook['upload']])
            ->assertOk()
            ->assertViewIs('admin.users.import-preview')
            ->assertViewHas('validRows', fn (array $rows): bool => count($rows) === 2)
            ->assertViewHas('errors', fn (array $errors): bool => count($errors) === 1);

        $token = $preview->original->getData()['token'];

        $this->post(route('admin.users.import.confirm'), ['token' => $token])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', '2 akun PJL berhasil diimpor.');

        $this->assertDatabaseHas('users', [
            'nip' => 'ADMIN-IMPORT-001',
            'email' => 'shared@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'nip' => 'ADMIN-IMPORT-002',
            'email' => 'shared@example.test',
        ]);
        $this->assertDatabaseCount('users', 4);
        $activity = ActivityLog::query()->sole();
        expect($activity->module)->toBe('users')
            ->and($activity->action)->toBe('import')
            ->and($activity->new_values)->toBe(['imported_count' => 2, 'failed_count' => 1])
            ->and($activity->description)->not->toContain('PJL Satu')
            ->and(json_encode($activity->new_values))->not->toContain('ADMIN-IMPORT');
    } finally {
        @unlink($workbook['path']);
    }
});

it('rejects blank and malformed email values during Admin import preview', function () {
    $admin = createEmailPolicyUser('admin', 'ADMIN-EMAIL-VALIDATION');
    $workbook = createAdminUserImportWorkbook([
        ['PJL-EMAIL-BLANK', 'Petugas Email Kosong', '', '08111111111', 'pjl', 'aktif'],
        ['PJL-EMAIL-INVALID', 'Petugas Email Salah', 'bukan-email', '08122222222', 'pjl', 'aktif'],
    ]);

    try {
        $this->actingAs($admin)
            ->post(route('admin.users.import.preview'), ['file' => $workbook['upload']])
            ->assertOk()
            ->assertViewIs('admin.users.import-preview')
            ->assertViewHas('validRows', fn (array $rows): bool => $rows === [])
            ->assertViewHas('errors', function (array $errors): bool {
                return count($errors) === 2
                    && collect($errors)->every(fn (array $error): bool => collect($error['messages'])
                        ->contains(fn (string $message): bool => str_contains(strtolower($message), 'email'))
                    );
            });
    } finally {
        @unlink($workbook['path']);
    }
});

it('logs Admin user changes without personal values and skips unchanged updates', function () {
    $admin = createEmailPolicyUser('admin', 'ADMIN-AUDIT-001');
    $target = createEmailPolicyUser('pjl', 'PJL-AUDIT-001', 'old@example.test');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target->id), [
            'nip' => 'PJL-AUDIT-002',
            'name' => 'Nama Baru',
            'email' => 'new@example.test',
            'phone' => '08123456789',
            'role' => 'admin',
        ])
        ->assertRedirect(route('admin.users.index'));

    $activity = ActivityLog::query()->sole();
    expect($activity->module)->toBe('users')
        ->and($activity->old_values)->toBe(['role' => 'pjl'])
        ->and($activity->new_values['role'])->toBe('admin')
        ->and($activity->new_values['changed_fields'])->toContain('name', 'email', 'phone', 'nip', 'role')
        ->and(json_encode([$activity->old_values, $activity->new_values, $activity->description]))
        ->not->toContain('PJL-AUDIT-001', 'PJL-AUDIT-002', 'Nama Baru', 'old@example.test', 'new@example.test', '08123456789');

    $target->refresh();

    $this->put(route('admin.users.update', $target->id), [
        'nip' => $target->nip,
        'name' => $target->name,
        'email' => $target->email,
        'phone' => $target->phone,
        'role' => 'admin',
    ])->assertRedirect(route('admin.users.index'));

    expect(ActivityLog::query()->count())->toBe(1);
});

it('logs Admin account status changes without personal values', function () {
    $admin = createEmailPolicyUser('admin', 'ADMIN-STATUS-AUDIT');
    $target = createEmailPolicyUser('pjl', 'PJL-STATUS-AUDIT');

    $this->actingAs($admin)
        ->patch(route('admin.users.toggle-status', $target->id))
        ->assertRedirect(route('admin.users.index'));

    $activity = ActivityLog::query()->sole();
    expect($target->fresh()->is_active)->toBeFalse()
        ->and($activity->module)->toBe('users')
        ->and($activity->action)->toBe('deactivate')
        ->and($activity->old_values)->toBe(['is_active' => true])
        ->and($activity->new_values)->toBe(['is_active' => false])
        ->and($activity->description)->not->toContain('PJL-STATUS-AUDIT');
});

it('rolls back an Admin user update when its Activity Log cannot be written', function () {
    $admin = createEmailPolicyUser('admin', 'ADMIN-AUDIT-002');
    $target = createEmailPolicyUser('pjl', 'PJL-AUDIT-003');
    $this->mock(ActivityLogService::class)
        ->shouldReceive('log')
        ->once()
        ->andThrow(new RuntimeException('Activity Log gagal disimpan.'));

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target->id), [
            'nip' => $target->nip,
            'name' => 'Nama Tidak Tersimpan',
            'email' => null,
            'phone' => null,
            'role' => 'pjl',
        ])
        ->assertServerError();

    expect($target->fresh()->name)->toBe($target->name)
        ->and(ActivityLog::query()->count())->toBe(0);
});

it('rolls back an Admin user import when its summary Activity Log cannot be written', function () {
    $admin = createEmailPolicyUser('admin', 'ADMIN-IMPORT-ROLLBACK');
    createEmailPolicyUser('pjl', 'PJL-IMPORT-ROLLBACK-ROLE');
    $workbook = createAdminUserImportWorkbook([
        ['PJL-IMPORT-ROLLBACK', 'Petugas Rollback', 'pjl@example.test', '08123456789', 'pjl', 'aktif'],
    ]);

    try {
        $preview = $this->actingAs($admin)
            ->post(route('admin.users.import.preview'), ['file' => $workbook['upload']])
            ->assertOk();
        $token = $preview->original->getData()['token'];

        $this->mock(ActivityLogService::class)
            ->shouldReceive('log')
            ->once()
            ->andThrow(new RuntimeException('Activity Log gagal disimpan.'));

        $this->post(route('admin.users.import.confirm'), ['token' => $token])
            ->assertRedirect(route('admin.users.index'));

        expect(User::query()->count())->toBe(2)
            ->and(ActivityLog::query()->count())->toBe(0);
    } finally {
        @unlink($workbook['path']);
    }
});
