<?php

namespace Tests\Feature;

use App\Filament\Resources\EkskulMasters\EkskulMasterResource;
use App\Filament\Resources\NilaiAkademiks\NilaiAkademikResource;
use App\Filament\Resources\SantriEkskuls\Pages\ListSantriEkskuls;
use App\Filament\Resources\SantriEkskuls\SantriEkskulResource;
use App\Models\Pesantren;
use App\Models\User;
use Filament\Navigation\NavigationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Master ekskul dipindah dari entri menu cluster menjadi tombol di header
 * daftar Ekskul Santri. Hak aksesnya lebih sempit daripada halaman tempat
 * tombolnya dipasang (admin saja vs admin+ustadz), jadi visibilitas tombol
 * itulah yang mencegah ustadz mendarat di halaman 403.
 */
class EkskulMasterNavigasiTest extends TestCase
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
     * Kumpulkan sub-navigasi cluster sebagai pasangan label => url. Pemeriksaan
     * dilakukan lewat URL, bukan label, karena setelah menu Ekskul Santri
     * diringkas menjadi "Ekskul" labelnya tidak lagi membedakan kedua resource.
     *
     * @return array<string, ?string>
     */
    private function subNavigasi(User $user): array
    {
        $komponen = Livewire::actingAs($user)->test(ListSantriEkskuls::class);

        $item = [];

        // getSubNavigation() bisa mengembalikan NavigationItem datar atau
        // NavigationGroup berisi item, tergantung penataan cluster-nya.
        foreach ($komponen->instance()->getSubNavigation() as $entri) {
            foreach ($entri instanceof NavigationGroup ? $entri->getItems() : [$entri] as $nav) {
                $item[$nav->getLabel()] = $nav->getUrl();
            }
        }

        return $item;
    }

    public function test_ekskul_master_tidak_didaftarkan_di_navigasi(): void
    {
        $this->assertFalse(EkskulMasterResource::shouldRegisterNavigation());
    }

    public function test_tombol_kelola_ekskul_tampil_untuk_admin(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ListSantriEkskuls::class)
            ->assertActionExists('kelolaEkskul');
    }

    public function test_tombol_kelola_ekskul_disembunyikan_dari_ustadz(): void
    {
        Livewire::actingAs($this->ustadz())
            ->test(ListSantriEkskuls::class)
            ->assertActionHidden('kelolaEkskul');
    }

    /**
     * Menguji sub-navigasi yang benar-benar dirender, bukan sekadar nilai
     * flag-nya.
     */
    public function test_sub_navigasi_akademik_tidak_lagi_memuat_ekskul_master(): void
    {
        $url = array_values($this->subNavigasi($this->admin()));

        $this->assertNotContains(EkskulMasterResource::getUrl('index'), $url);
        $this->assertContains(SantriEkskulResource::getUrl('index'), $url);
        $this->assertContains(NilaiAkademikResource::getUrl('index'), $url);
    }

    public function test_menu_ekskul_santri_memakai_label_ringkas(): void
    {
        $nav = $this->subNavigasi($this->admin());

        $this->assertSame(
            SantriEkskulResource::getUrl('index'),
            $nav['Ekskul'] ?? null,
            'Entri sub-navigasi berlabel "Ekskul" harus mengarah ke Ekskul Santri, bukan ke master ekskul.'
        );
    }

    public function test_sub_navigasi_akademik_tidak_memuat_input_nilai_massal(): void
    {
        $this->assertArrayNotHasKey('Input Nilai Massal', $this->subNavigasi($this->admin()));
    }
}
