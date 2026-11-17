<?php

namespace Database\Factories;

use App\Models\Resort;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resort>
 */
class ResortFactory extends Factory
{
    protected $model = Resort::class;

    public function definition(): array
    {
        return [
            'nama_resort' => fake()->word() . ' Resort',
            'is_active' => true,
        ];
    }
}
