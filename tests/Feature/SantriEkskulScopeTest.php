<?php

namespace Tests\Feature;

use App\Filament\Resources\SantriEkskuls\Pages\ListSantriEkskuls;
use App\Filament\Resources\SantriEkskuls\SantriEkskulResource;
use App\Models\EkskulMaster;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\SantriEkskul;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Ekskul Santri sempat menjadi satu-satunya resource ber-HasAdminUstadzAccess
 * tanpa pembatasan query, sehingga seorang ustadz melihat data ekskul seluruh
 * santri se-pesantren.
 */
class SantriEkskulScopeTest extends TestCase
{
    use RefreshDatabase;

    private function buatEkskulUntukSantriBimbingan(Pesantren $pesantren, ?User $pembimbing): SantriEkskul
    {
        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'pembimbing_ustadz_id' => $pembimbing?->id,
            'status_aktif' => true,
        ]);

        $ekskul = EkskulMaster::create([
            'pesantren_id' => $pesantren->id,
            'nama' => 'Panahan '.$santri->id,
            'aktif' => true,
        ]);

        return SantriEkskul::create([
            'pesantren_id' => $pesantren->id,
            'santri_id' => $santri->id,
            'ekskul_id' => $ekskul->id,
            'level' => 'pemula',
            'tanggal_mulai' => now()->subMonth(),
            'aktif' => true,
        ]);
    }

    public function test_ustadz_hanya_melihat_ekskul_santri_bimbingannya(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadzA = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $ustadzB = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $milikA = $this->buatEkskulUntukSantriBimbingan($pesantren, $ustadzA);
        $milikB = $this->buatEkskulUntukSantriBimbingan($pesantren, $ustadzB);

        $this->actingAs($ustadzA);

        $hasil = SantriEkskulResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($hasil->contains($milikA->id));
        $this->assertFalse($hasil->contains($milikB->id));
    }

    public function test_ustadz_tidak_bisa_membuka_record_ustadz_lain_lewat_url(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadzA = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $ustadzB = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $milikA = $this->buatEkskulUntukSantriBimbingan($pesantren, $ustadzA);
        $milikB = $this->buatEkskulUntukSantriBimbingan($pesantren, $ustadzB);

        $this->actingAs($ustadzA);

        // Inilah jalur yang dipakai Filament saat URL /{record}/edit dibuka.
        $this->assertNotNull(SantriEkskulResource::resolveRecordRouteBinding($milikA->id));
        $this->assertNull(SantriEkskulResource::resolveRecordRouteBinding($milikB->id));
    }

    public function test_admin_pesantren_melihat_seluruh_ekskul_di_pesantrennya(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $milikUstadz = $this->buatEkskulUntukSantriBimbingan($pesantren, $ustadz);
        $tanpaPembimbing = $this->buatEkskulUntukSantriBimbingan($pesantren, null);

        $this->actingAs($admin);

        $hasil = SantriEkskulResource::getEloquentQuery()->pluck('id');

        $this->assertTrue($hasil->contains($milikUstadz->id));
        $this->assertTrue($hasil->contains($tanpaPembimbing->id));
    }

    /**
     * Opsi dropdown Santri di modal "Tambah Ekskul", persis seperti yang dilihat
     * pengguna.
     *
     * Diambil dari action yang benar-benar di-mount, bukan dari
     * SantriEkskulForm::configure() langsung: schema Filament butuh komponen
     * Livewire induk, dan merakitnya sendiri hanya menguji closure-nya — bukan
     * bahwa formnya benar-benar sampai ke layar dengan isi itu.
     */
    private function opsiSantri(User $sebagai): array
    {
        $opsi = [];

        Livewire::actingAs($sebagai)
            ->test(ListSantriEkskuls::class)
            ->mountAction('create')
            ->assertFormFieldExists('santri_id', function (Select $field) use (&$opsi): bool {
                $opsi = $field->getOptions();

                return true;
            });

        return $opsi;
    }

    public function test_daftar_santri_langsung_terisi_tanpa_harus_diketik(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'nama_lengkap' => 'Ahmad Fauzi',
            'status_aktif' => true,
        ]);

        // Dulu select ini satu-satunya di panel yang memakai
        // relationship()->searchable() tanpa preload(), sehingga dropdown-nya kosong
        // sampai admin mengetik — tidak ada cara menebak itu dari layar. Yang diuji:
        // opsinya sudah ada SEBELUM ada pencarian apa pun.
        $this->assertSame(
            [$santri->id => 'Ahmad Fauzi'],
            $this->opsiSantri($admin),
        );
    }

    public function test_santri_non_aktif_tidak_ikut_di_dropdown(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'nama_lengkap' => 'Aktif',
            'status_aktif' => true,
        ]);
        Santri::factory()->nonAktif()->create([
            'pesantren_id' => $pesantren->id,
            'nama_lengkap' => 'Sudah Keluar',
        ]);

        $this->assertSame(['Aktif'], array_values($this->opsiSantri($admin)));
    }

    public function test_ustadz_hanya_melihat_santri_bimbingannya_di_dropdown(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'pembimbing_ustadz_id' => $ustadz->id,
            'nama_lengkap' => 'Bimbingan Saya',
            'status_aktif' => true,
        ]);
        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'pembimbing_ustadz_id' => User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id])->id,
            'nama_lengkap' => 'Bimbingan Ustadz Lain',
            'status_aktif' => true,
        ]);

        // Batasan ini SEBELUMNYA ditulis inline sebagai where('pembimbing_ustadz_id'),
        // sekarang lewat SantriOptions/PenugasanUstadz. Definisinya sama persis —
        // tes ini yang membuktikan penggantian itu tidak melebarkan cakupan.
        $this->assertSame(['Bimbingan Saya'], array_values($this->opsiSantri($ustadz)));
    }

    public function test_dropdown_tidak_bocor_ke_santri_pesantren_lain(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'nama_lengkap' => 'Santri Sendiri',
            'status_aktif' => true,
        ]);
        Santri::factory()->create([
            'pesantren_id' => Pesantren::factory()->create()->id,
            'nama_lengkap' => 'Santri Tetangga',
            'status_aktif' => true,
        ]);

        // relationship() dulu yang menegakkan ini lewat global scope; SantriOptions
        // memakai Santri::query() yang lewat scope yang sama. Diuji supaya
        // penggantiannya tidak diam-diam membuka daftar santri tenant lain.
        $this->assertSame(['Santri Sendiri'], array_values($this->opsiSantri($admin)));
    }
}
