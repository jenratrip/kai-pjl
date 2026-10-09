<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

        User::updateOrCreate(
            ['nip' => '0000000001'],
            [
                'name' => 'Test Admin',
                'email' => 'admin@example.com',
                'password' => 'password',
                'role_id' => Role::where('name', 'admin')->value('id'),
            ]
        );

        User::updateOrCreate(
            ['nip' => '0000000000'],
            [
                'name' => 'Test Superadmin',
                'email' => 'superadmin@example.com',
                'password' => 'password',
                'role_id' => Role::where('name', 'superadmin')->value('id'),
            ]
        );
    }
}