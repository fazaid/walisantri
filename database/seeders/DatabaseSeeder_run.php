<?php

// File: database/seeders/DatabaseSeeder.php
// Ganti isi method run() default Laravel dengan kode di bawah ini.

public function run(): void
{
    $this->call([
        SuperAdminSeeder::class,
        TenantDummySeeder::class,
    ]);
}
