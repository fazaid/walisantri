<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\Pesantren;
use Illuminate\Database\Eloquent\Factories\Factory;

class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    public function definition(): array
    {
        return [
            'pesantren_id' => Pesantren::factory(),
            'nama_kelas'   => fake()->unique()->randomElement([
                'Tahfidz 1', 'Tahfidz 2', 'Tahfidz 3', 'Ulya 1', 'Ulya 2', 'Wustha 1',
            ]),
        ];
    }
}
