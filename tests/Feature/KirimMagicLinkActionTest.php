<?php

namespace Tests\Feature;

use App\Filament\Resources\Santris\Pages\ViewSantri;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KirimMagicLinkActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tombol_link_wali_nonaktif_kalau_santri_belum_punya_wali(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin     = User::factory()->create(['role' => 'admin_pesantren', 'pesantren_id' => $pesantren->id]);
        $santri    = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'wali_santri_id' => null]);

        $this->actingAs($admin);

        Livewire::test(ViewSantri::class, ['record' => $santri->getRouteKey()])
            ->assertActionDisabled('kirim_magic_link');
    }

    public function test_tombol_link_wali_aktif_kalau_santri_sudah_punya_wali(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin     = User::factory()->create(['role' => 'admin_pesantren', 'pesantren_id' => $pesantren->id]);
        $wali      = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);
        $santri    = Santri::factory()->create(['pesantren_id' => $pesantren->id, 'wali_santri_id' => $wali->id]);

        $this->actingAs($admin);

        Livewire::test(ViewSantri::class, ['record' => $santri->getRouteKey()])
            ->assertActionEnabled('kirim_magic_link');
    }
}
