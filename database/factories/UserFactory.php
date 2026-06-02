<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'pesantren_id' => null,
            'name'         => fake()->name(),
            'email'        => fake()->unique()->safeEmail(),
            'phone_number' => null,
            'password'     => static::$password ??= Hash::make('password'),
            'role'         => 'wali_santri',
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(['role' => 'super_admin', 'pesantren_id' => null]);
    }

    public function adminPesantren(): static
    {
        return $this->state(['role' => 'admin_pesantren']);
    }

    public function ustadz(): static
    {
        return $this->state(['role' => 'ustadz']);
    }

    public function waliSantri(): static
    {
        return $this->state(['role' => 'wali_santri']);
    }
}
