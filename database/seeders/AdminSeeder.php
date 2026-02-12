<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'fazaweb@gmail.com'], // Cek email ini dulu
            ['name' => 'Faza', 'password' => bcrypt('celotehjari@123')] // Jika tidak ada, buat user baru dengan password ini
        );
    }
}
