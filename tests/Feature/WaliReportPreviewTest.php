<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaliReportPreviewTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Santri, 1: User} [santri, admin pesantren yang sama] */
    private function santriDanAdmin(): array
    {
        $pesantren = Pesantren::factory()->create();
        $wali      = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);
        $admin     = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $santri = Santri::factory()->create([
            'pesantren_id'   => $pesantren->id,
            'wali_santri_id' => $wali->id,
            'status_aktif'   => true,
        ]);

        return [$santri, $admin];
    }

    public function test_preview_admin_menyembunyikan_link_detail_dan_statistik(): void
    {
        [$santri, $admin] = $this->santriDanAdmin();

        // Link ke sub-halaman wali.* hanya berfungsi untuk sesi wali pemilik santri
        // (ResolvesSantriMilikWali). Di preview admin link itu 404, jadi harus
        // disembunyikan agar tidak menyesatkan.
        $this->actingAs($admin)
            ->get(route('admin.preview.wali', $santri))
            ->assertOk()
            ->assertDontSee(route('wali.santri.tahfidz', $santri->id))
            ->assertDontSee(route('wali.santri.kesehatan', $santri->id))
            ->assertDontSee(route('wali.santri.mutabaah', $santri->id))
            ->assertDontSee(route('wali.santri.inventaris', $santri->id))
            // Kartu inventaris tetap tampil (menampilkan data), hanya tautannya yang hilang.
            ->assertSee('Inventaris Santri');
    }

    public function test_wali_login_normal_tetap_melihat_link_detail_dan_statistik(): void
    {
        [$santri, ] = $this->santriDanAdmin();

        $this->actingAs($santri->wali)
            ->get(route('wali.santri.show', $santri->id))
            ->assertOk()
            ->assertSee(route('wali.santri.tahfidz', $santri->id))
            ->assertSee(route('wali.santri.kesehatan', $santri->id))
            ->assertSee(route('wali.santri.mutabaah', $santri->id))
            ->assertSee(route('wali.santri.inventaris', $santri->id));
    }
}
