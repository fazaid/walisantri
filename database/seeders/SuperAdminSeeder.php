<?php

// File: database/seeders/SuperAdminSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@walisantri.com'],
            [
                'pesantren_id' => null,
                'name'         => 'Super Admin',
                'phone_number' => '081200000000',
                'password'     => 'superadmin123',  // di-hash otomatis via cast 'hashed'
                'role'         => 'super_admin',
            ]
        );
    }
}
