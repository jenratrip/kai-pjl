<?php

namespace Database\Seeders;

use App\Models\Jpl;
use App\Services\DefaultInventarisService;
use Illuminate\Database\Seeder;

class InventarisDefaultSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(DefaultInventarisService::class);

        Jpl::query()
            ->chunkById(100, function ($jpls) use ($service) {
                foreach ($jpls as $jpl) {
                    $service->initializeForJpl($jpl);
                }
            });
    }
}
