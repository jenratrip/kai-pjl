<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Data master role dan shift
        $this->call([
            RoleSeeder::class,
            ShiftSeeder::class,
        ]);

        // 2. Akun testing Admin
        User::updateOrCreate(
            ['nip' => '0000000001'],
            [
                'name' => 'Test Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('TestPJL2026!'),
                'role_id' => Role::where('name', 'admin')->value('id'),
                'is_active' => true,
            ]
        );

        // 3. Akun testing Superadmin
        User::updateOrCreate(
            ['nip' => '0000000000'],
            [
                'name' => 'Test Superadmin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('TestPJL2026!'),
                'role_id' => Role::where('name', 'superadmin')->value('id'),
                'is_active' => true,
            ]
        );

        // 4. Akun testing PJL
        User::updateOrCreate(
            ['nip' => '0000000002'],
            [
                'name' => 'Test PJL',
                'email' => 'pjl@example.com',
                'password' => Hash::make('TestPJL2026!'),
                'role_id' => Role::where('name', 'pjl')->value('id'),
                'is_active' => true,
            ]
        );

        // 5. Inventaris standar untuk JPL yang sudah tersedia
        $this->call([
            InventarisDefaultSeeder::class,
        ]);
    }
}
