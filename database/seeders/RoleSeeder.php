<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->upsert([
            [
                'name' => 'pjl',
                'label' => 'PJL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'admin',
                'label' => 'Admin/Atasan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'superadmin',
                'label' => 'Superadmin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['name'], ['label', 'updated_at']);
    }
}
