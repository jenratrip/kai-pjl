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

        User::factory()->create([
            'nip' => '0000000001',
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'role_id' => Role::where('name', 'admin')->value('id'),
        ]);
    }
}