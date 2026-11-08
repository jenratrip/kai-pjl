<?php

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
    } finally {
        @unlink($workbook['path']);
    }
});
