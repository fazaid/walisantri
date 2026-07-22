<?php

namespace Tests\Feature;

use App\Enums\JenisUangSaku;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\UangSakuSantri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaliDashboardUangSakuTest extends TestCase
{
    use RefreshDatabase;

    private function santriDenganWali(): Santri
    {
        $pesantren = Pesantren::factory()->create();
        $wali      = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);

        return Santri::factory()->create([
            'pesantren_id'   => $pesantren->id,
            'wali_santri_id' => $wali->id,
            'status_aktif'   => true,
        ]);
    }

    private function catatUangSaku(Santri $santri, JenisUangSaku $jenis, int $nominal): void
    {
        UangSakuSantri::create([
            'pesantren_id' => $santri->pesantren_id,
            'santri_id'    => $santri->id,
            'jenis'        => $jenis,
            'nominal'      => $nominal,
            'tanggal'      => '2026-07-01',
        ]);
    }

    public function test_dashboard_menampilkan_card_uang_saku_dengan_saldo(): void
    {
        $santri = $this->santriDenganWali();

        // Saldo = setoran − pengambilan = 100.000 − 30.000 = 70.000
        $this->catatUangSaku($santri, JenisUangSaku::Setoran, 100000);
        $this->catatUangSaku($santri, JenisUangSaku::Pengambilan, 30000);

        $this->actingAs($santri->wali)
            ->get('/wali/dashboard')
            ->assertOk()
            ->assertSee('Uang Saku')
            ->assertSee('Rp 70.000')
            ->assertSee(route('wali.uang-saku'));
    }

    public function test_sesi_magic_link_tidak_melihat_uang_saku_di_report(): void
    {
        $santri = $this->santriDenganWali();
        $this->catatUangSaku($santri, JenisUangSaku::Setoran, 100000);

        // Uang saku adalah data finansial yang sengaja login-only — tidak boleh
        // bocor lewat magic link yang bisa diteruskan.
        $this->get("/report/{$santri->uuid}")
            ->assertOk()
            ->assertDontSee('Uang Saku');
    }
}
