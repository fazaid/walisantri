<?php

namespace Tests\Feature;

use App\Filament\Pages\MutabaahHarianPage;
use App\Filament\Resources\KesantrianAmalMasters\KesantrianAmalMasterResource;
use App\Filament\Resources\KesantrianInventaris\KesantrianInventarisResource;
use App\Filament\Resources\KesantrianKarakterRapors\KesantrianKarakterRaporResource;
use App\Filament\Resources\KesantrianKesehatans\KesantrianKesehatanResource;
use App\Filament\Resources\KesantrianMutabaahs\KesantrianMutabaahResource;
use App\Filament\Resources\KesantrianMutabaahs\Pages\ListKesantrianMutabaahs;
use App\Models\Pesantren;
use App\Models\User;
use Filament\Navigation\NavigationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cluster Mutabaah dibubarkan; isinya menumpang cluster Kesantrian dengan
 * Mutabaah sebagai tab paling kiri. Yang diuji sub-navigasi yang benar-benar
 * dirender, bukan sekadar nilai $navigationSort-nya.
 */
class MutabaahNavigasiTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
    }

    private function admin(): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);
    }

    private function ustadz(): User
    {
        return User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);
    }

    /**
     * @return array<int, ?string>
     */
    private function urlSubNavigasi(User $user): array
    {
        $komponen = Livewire::actingAs($user)->test(ListKesantrianMutabaahs::class);

        $url = [];

        // getCachedSubNavigation(), bukan getSubNavigation(): urutan tab baru
        // diterapkan (sortBy getSort()) di versi ter-cache itu — yang mentah
        // masih memakai urutan penemuan kelas.
        foreach ($komponen->instance()->getCachedSubNavigation() as $entri) {
            foreach ($entri instanceof NavigationGroup ? $entri->getItems() : [$entri] as $nav) {
                $url[] = $nav->getUrl();
            }
        }

        return $url;
    }

    public function test_mutabaah_jadi_tab_pertama_cluster_kesantrian(): void
    {
        $this->assertSame([
            KesantrianMutabaahResource::getUrl('index'),
            KesantrianKarakterRaporResource::getUrl('index'),
            KesantrianKesehatanResource::getUrl('index'),
            KesantrianInventarisResource::getUrl('index'),
        ], $this->urlSubNavigasi($this->admin()));
    }

    public function test_urutan_tab_sama_untuk_ustadz(): void
    {
        $this->assertSame(
            $this->urlSubNavigasi($this->admin()),
            $this->urlSubNavigasi($this->ustadz()),
        );
    }

    public function test_isi_harian_dan_amal_tidak_muncul_sebagai_tab(): void
    {
        $url = $this->urlSubNavigasi($this->admin());

        $this->assertNotContains(MutabaahHarianPage::getUrl(), $url);
        $this->assertNotContains(KesantrianAmalMasterResource::getUrl('index'), $url);
    }

    public function test_url_mutabaah_pindah_ke_bawah_kesantrian(): void
    {
        $this->assertStringContainsString('/admin/kesantrian/mutabaah', KesantrianMutabaahResource::getUrl('index'));
        $this->assertStringContainsString('/admin/kesantrian/isi-harian', MutabaahHarianPage::getUrl());
        $this->assertStringContainsString('/admin/kesantrian/amal', KesantrianAmalMasterResource::getUrl('index'));
    }
}
