<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\Support\CreatesKaiInMemorySchema;
use Tests\Support\KaiTestRecords;

uses(CreatesKaiInMemorySchema::class, KaiTestRecords::class);

beforeEach(function () {
    $this->createKaiInMemorySchema();
});

test('NIP is unique while email and phone may be shared across create edit and imports', function () {
    $adminRole = $this->kaiRole('admin');
    $pjlRole = $this->kaiRole('pjl');
    $superadminRole = $this->kaiRole('superadmin');
    $admin = $this->kaiUser($adminRole, 'A-1');
    $superadmin = $this->kaiUser($superadminRole, 'S-1');
    $existing = $this->kaiUser($pjlRole, 'P-1', [
        'email' => 'shared@example.test',
        'phone' => '08000000001',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $existing->id), [
            'nip' => 'P-1',
            'name' => $existing->name,
            'email' => 'shared@example.test',
            'phone' => '08000000001',
            'role' => 'pjl',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($superadmin)
        ->post(route('superadmin.users.store'), [
            'nip' => 'SAME-CONTACT-1',
            'name' => 'PJL Baru 1',
            'email' => 'shared@example.test',
            'phone' => '08000000001',
            'role_id' => $pjlRole->id,
            'is_active' => true,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasNoErrors();

    $token = (string) Str::uuid();
    Cache::put('admin_user_import_'.$token, [[
        'nip' => 'IMPORT-1',
        'name' => 'PJL Impor',
        'email' => 'shared@example.test',
        'phone' => '08000000001',
        'is_active' => true,
    ]]);

    $this->actingAs($admin)
        ->post(route('admin.users.import.confirm'), ['token' => $token])
        ->assertSessionHas('success');

    $superadminImportToken = (string) Str::uuid();
    Cache::put('user_import_preview:'.$superadminImportToken, [[
        'row' => 2,
        'data' => [
            'nip' => 'SA-IMPORT-1',
            'name' => 'PJL Impor Superadmin',
            'email' => 'shared@example.test',
            'phone' => '08000000001',
            'role' => 'pjl',
            'status' => 'aktif',
        ],
    ]]);

    $this->actingAs($superadmin)
        ->postJson(route('superadmin.users.import.confirm'), ['token' => $superadminImportToken])
        ->assertOk()
        ->assertJsonPath('imported', 1);

    expect(DB::table('users')->where('email', 'shared@example.test')->count())->toBe(4);

    $tokenDuplicateNip = (string) Str::uuid();
    Cache::put('admin_user_import_'.$tokenDuplicateNip, [[
        'nip' => 'P-1',
        'name' => 'Duplikat NIP',
        'email' => 'another@example.test',
        'phone' => '08000000002',
        'is_active' => true,
    ]]);

    $this->actingAs($admin)
        ->post(route('admin.users.import.confirm'), ['token' => $tokenDuplicateNip])
        ->assertSessionHas('error');

    expect(DB::table('users')->where('nip', 'P-1')->count())->toBe(1);
});
