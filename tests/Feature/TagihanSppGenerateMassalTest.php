<?php

namespace Tests\Feature;

use App\Filament\Resources\TagihanSpps\Pages\ListTagihanSpps;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\TagihanSpp;
use App\Models\TarifSpp;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * "Generate Massal" dulu memakai check-then-act (exists() lalu create()) tanpa
 * transaksi: dua klik bersamaan menghasilkan pelanggaran unique yang mentah ke
 * layar, dengan sebagian tagihan sudah terlanjur tersimpan. Sekarang satu
 * insertOrIgnore, sehingga keunikan dijamin DB dan mengulang aksi selalu aman.
 */
class TagihanSppGenerateMassalTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Pesantren, 1: User} */
    private function siapkan(int $jumlahSantri = 3): array
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        TarifSpp::create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nominal' => 150000,
        ]);

        for ($i = 0; $i < $jumlahSantri; $i++) {
            Santri::factory()->create([
                'pesantren_id' => $pesantren->id,
                'kelas_id' => $kelas->id,
            ]);
        }

        return [$pesantren, $admin];
    }

    private function generate(User $admin): void
    {
        Livewire::actingAs($admin)
            ->test(ListTagihanSpps::class)
            ->callAction(TestAction::make('generate_massal')->table(), [
                'bulan' => 9,
                'tahun' => 2026,
                'keterangan' => 'SPP Bulanan',
            ])
            ->assertHasNoActionErrors();
    }

    public function test_generate_massal_membuat_tagihan_untuk_santri_bertarif(): void
    {
        [, $admin] = $this->siapkan(3);

        $this->generate($admin);

        $this->assertSame(3, TagihanSpp::count());
        $this->assertSame(150000, TagihanSpp::first()->nominal);
    }

    public function test_generate_massal_dua_kali_tidak_menduplikasi_dan_tidak_error(): void
    {
        [, $admin] = $this->siapkan(3);

        $this->generate($admin);
        $this->generate($admin);

        // ON CONFLICT DO NOTHING: yang sudah ada dilewati, bukan melempar 23505.
        $this->assertSame(3, TagihanSpp::count());
    }

    public function test_santri_tanpa_tarif_kelas_dilewati(): void
    {
        [$pesantren, $admin] = $this->siapkan(2);

        // Santri tanpa kelas -> tidak punya tarif -> tidak boleh ditagih.
        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => null,
        ]);

        $this->generate($admin);

        $this->assertSame(2, TagihanSpp::count());
    }
}
