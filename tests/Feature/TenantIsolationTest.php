<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(string $tag): Pesantren
    {
        return Pesantren::create([
            'nama_pesantren'      => "Pesantren {$tag}",
            'slug'                => "pesantren-" . strtolower($tag),
            'paket_langganan'     => 'rintisan',
            'max_santri_kuota'    => 100,
            'status_berlangganan' => 'active',
            'expired_at'          => now()->addYear(),
        ]);
    }

    private function makeUser(Pesantren $pesantren, string $role, string $tag): User
    {
        return User::create([
            'pesantren_id' => $pesantren->id,
            'name'         => "{$role} {$tag}",
            'email'        => strtolower(str_replace('_', '', $role)) . ".{$tag}@test.com",
            'password'     => bcrypt('password'),
            'role'         => $role,
        ]);
    }

    private function makeSantri(Pesantren $pesantren, User $wali, User $ustadz, string $nis): Santri
    {
        // Provide pesantren_id explicitly so the Multitenantable creating-hook
        // (which only overwrites when pesantren_id is empty) leaves it untouched,
        // regardless of any stale auth state from a previous test.
        return Santri::create([
            'pesantren_id'         => $pesantren->id,
            'wali_santri_id'       => $wali->id,
            'pembimbing_ustadz_id' => $ustadz->id,
            'nis'                  => $nis,
            'nama_lengkap'         => "Santri {$nis}",
            'kelas'                => '1A',
            'kamar'                => 'A',
        ]);
    }

    public function test_admin_pesantren_a_hanya_melihat_santri_pesantren_a(): void
    {
        $pesantrenA = $this->makePesantren('A');
        $pesantrenB = $this->makePesantren('B');

        $adminA  = $this->makeUser($pesantrenA, 'admin_pesantren', 'A');
        $waliA   = $this->makeUser($pesantrenA, 'wali_santri', 'A');
        $ustadzA = $this->makeUser($pesantrenA, 'ustadz', 'A');
        $waliB   = $this->makeUser($pesantrenB, 'wali_santri', 'B');
        $ustadzB = $this->makeUser($pesantrenB, 'ustadz', 'B');

        // Insert 2 santri per pesantren without auth so INSERT is not scoped.
        $this->makeSantri($pesantrenA, $waliA, $ustadzA, 'A001');
        $this->makeSantri($pesantrenA, $waliA, $ustadzA, 'A002');
        $this->makeSantri($pesantrenB, $waliB, $ustadzB, 'B001');
        $this->makeSantri($pesantrenB, $waliB, $ustadzB, 'B002');

        // actingAs sets auth()->user() → Multitenantable global scope activates.
        $this->actingAs($adminA);

        $santri = Santri::all();

        $this->assertCount(2, $santri);
        $santri->each(fn($s) => $this->assertEquals($pesantrenA->id, $s->pesantren_id));
    }

    public function test_admin_pesantren_b_hanya_melihat_santri_pesantren_b(): void
    {
        $pesantrenA = $this->makePesantren('A');
        $pesantrenB = $this->makePesantren('B');

        $adminB  = $this->makeUser($pesantrenB, 'admin_pesantren', 'B');
        $waliA   = $this->makeUser($pesantrenA, 'wali_santri', 'A');
        $ustadzA = $this->makeUser($pesantrenA, 'ustadz', 'A');
        $waliB   = $this->makeUser($pesantrenB, 'wali_santri', 'B');
        $ustadzB = $this->makeUser($pesantrenB, 'ustadz', 'B');

        $this->makeSantri($pesantrenA, $waliA, $ustadzA, 'A001');
        $this->makeSantri($pesantrenA, $waliA, $ustadzA, 'A002');
        $this->makeSantri($pesantrenB, $waliB, $ustadzB, 'B001');
        $this->makeSantri($pesantrenB, $waliB, $ustadzB, 'B002');

        $this->actingAs($adminB);

        $santri = Santri::all();

        $this->assertCount(2, $santri);
        $santri->each(fn($s) => $this->assertEquals($pesantrenB->id, $s->pesantren_id));
    }
}
