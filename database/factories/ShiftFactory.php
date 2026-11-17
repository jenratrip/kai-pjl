<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition(): array
    {
        return [
            'nama_shift' => fake()->randomElement(['Pagi', 'Siang', 'Malam', 'Shift 1', 'Shift 2', 'Shift 3']),
            'jam_mulai' => fake()->time(),
            'jam_selesai' => fake()->time(),
        ];
    }
}
