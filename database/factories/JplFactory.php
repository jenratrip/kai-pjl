<?php

namespace Database\Factories;

use App\Models\Jpl;
use App\Models\Resort;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jpl>
 */
class JplFactory extends Factory
{
    protected $model = Jpl::class;

    public function definition(): array
    {
        return [
            'kode_jpl' => fake()->unique()->bothify('JPL-???'),
            'nama_jpl' => fake()->company(),
            'resort_id' => Resort::factory(),
            'lokasi' => fake()->city(),
        ];
    }
}
