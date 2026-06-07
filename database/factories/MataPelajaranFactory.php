<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MataPelajaranFactory extends Factory
{
    protected $model = MataPelajaran::class;

    public function definition(): array
    {
        return [
            'pesantren_id' => Pesantren::factory(),
            'kelas_id'     => function (array $attributes) {
                return Kelas::factory()->create(['pesantren_id' => $attributes['pesantren_id']])->id;
            },
            'ustadz_id'    => function (array $attributes) {
                return User::factory()->ustadz()->create(['pesantren_id' => $attributes['pesantren_id']])->id;
            },
            'nama_mapel'   => fake()->randomElement([
                'Tafsir', 'Hadits', 'Fiqih', 'Bahasa Arab', 'Akidah Akhlak',
            ]),
        ];
    }
}
