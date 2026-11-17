<?php

namespace Database\Factories;

use App\Models\Dinas;
use App\Models\Jpl;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dinas>
 */
class DinasFactory extends Factory
{
    protected $model = Dinas::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'jpl_id' => Jpl::factory(),
            'shift_id' => Shift::factory(),
            'tanggal_dinas' => fake()->dateTimeBetween('+5 days', '+30 days')->format('Y-m-d'),
            'status' => 'dijadwalkan',
            'jam_mulai' => fake()->time(),
            'jam_selesai' => fake()->time(),
        ];
    }
}
