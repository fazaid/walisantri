<?php

namespace Database\Factories;

use App\Models\Pesantren;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PesantrenFactory extends Factory
{
    protected $model = Pesantren::class;

    public function definition(): array
    {
        $nama = fake()->unique()->words(2, true);
        return [
            'nama_pesantren'             => 'Pesantren ' . ucwords($nama),
            'slug'                       => Str::slug($nama) . '-' . fake()->numerify('##'),
            'paket_langganan'            => 'rintisan',
            'max_santri_kuota'           => 10,
            'status_berlangganan'        => 'trial',
            'expired_at'                 => now()->addDays(14),
            'santri_count_cache'         => 0,
            'onboarding_completed_steps' => [],
            'profil'                     => null,
        ];
    }

    public function aktif(): static
    {
        return $this->state(['status_berlangganan' => 'active', 'expired_at' => now()->addYear()]);
    }

    public function rintisan(): static
    {
        return $this->state(['paket_langganan' => 'rintisan', 'max_santri_kuota' => 100]);
    }

    public function berkembang(): static
    {
        return $this->state(['paket_langganan' => 'berkembang', 'max_santri_kuota' => 500]);
    }

    public function maju(): static
    {
        return $this->state(['paket_langganan' => 'maju', 'max_santri_kuota' => 1000]);
    }
}
