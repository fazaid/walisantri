<?php

namespace Database\Factories;

use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use App\Models\Pesantren;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Factories\Factory;

class NilaiAkademikFactory extends Factory
{
    protected $model = NilaiAkademik::class;

    public function definition(): array
    {
        return [
            'pesantren_id'      => Pesantren::factory(),
            'santri_id'         => function (array $attributes) {
                return Santri::factory()->create(['pesantren_id' => $attributes['pesantren_id']])->id;
            },
            'mata_pelajaran_id' => function (array $attributes) {
                return MataPelajaran::factory()->create(['pesantren_id' => $attributes['pesantren_id']])->id;
            },
            'tahun_ajaran'      => '2026/2027',
            'periode'           => fake()->randomElement(['Bulanan', 'Semester_Ganjil', 'Semester_Genap']),
            'nilai'             => fake()->numberBetween(60, 100),
            'catatan'           => null,
        ];
    }
}
