<?php

namespace Database\Factories;

use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SantriFactory extends Factory
{
    protected $model = Santri::class;

    public function definition(): array
    {
        return [
            'pesantren_id'         => Pesantren::factory(),
            'wali_santri_id'       => User::factory()->waliSantri(),
            'pembimbing_ustadz_id' => User::factory()->ustadz(),
            'nis'                  => fake()->unique()->numerify('########'),
            'nama_lengkap'         => fake()->name(),
            'status_aktif'         => true,
        ];
    }

    public function nonAktif(): static
    {
        return $this->state(['status_aktif' => false]);
    }
}
