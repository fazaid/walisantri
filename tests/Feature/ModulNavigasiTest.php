<?php

namespace Tests\Feature;

use App\Enums\Modul;
use App\Filament\Clusters;
use App\Filament\Pages\ModulPengaturanPage;
use App\Filament\Resources\Kamars\KamarResource;
use App\Filament\Resources\Kelas\KelasResource;
use App\Filament\Resources\NilaiAkademiks\NilaiAkademikResource;
use App\Filament\Resources\PrestasiSantris\PrestasiSantriResource;
use App\Filament\Resources\Santris\SantriResource;
use App\Models\ModulPengaturan;
use App\Models\Pesantren;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Modul yang dimatikan harus benar-benar lenyap, bukan sekadar bernilai false.
 *
 * Berkas ini mengikuti tuntutan yang sama dengan EkskulMasterNavigasiTest dan
 * MutabaahNavigasiTest: menguji flag-nya saja tidak cukup, karena yang dijanjikan
 * ke pengguna adalah menu yang tidak ada di layar. Jadi sebagian tes di sini
 * memeriksa HALAMAN YANG DIRENDER, bukan nilai kembalian method.
 *
 * Dua tes di berkas ini menjaga hal yang tidak akan menimbulkan galat apa pun bila
 * rusak, dan karena itu tidak boleh dihapus tanpa penggantinya:
 *
 * - test_cluster_santri_tidak_pernah_bisa_dimatikan — HasAdminOnlyAccess dipakai
 *   KelasResource & KamarResource yang inti. Yang menjaganya cuma fakta bahwa
 *   ModulKomponen menurunkan modul dari CLUSTER, dan Cluster Santri tidak punya modul.
 * - test_setiap_komponen_cluster_ikut_dimatikan — penjaga kelengkapan. PHP tidak
 *   punya mekanisme level-kelas yang anti-override (method di kelas mengalahkan
 *   trait tanpa peringatan), jadi kelengkapan hanya bisa ditegakkan dari luar
 *   hierarki kelas. Resource baru yang lupa digating ketahuan di sini, bukan di produksi.
 */
class ModulNavigasiTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    /** Cluster yang punya tuas, beserta modulnya. */
    private const CLUSTER_MODUL = [
        Clusters\Akademik::class => Modul::Akademik,
        Clusters\Tahfidz::class => Modul::Tahfidz,
        Clusters\Presensi::class => Modul::Presensi,
        Clusters\Kesantrian::class => Modul::Kesantrian,
        Clusters\Keuangan::class => Modul::Keuangan,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        Filament::setCurrentPanel('admin');
    }

    private function admin(): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);
    }

    private function matikan(Modul ...$modul): void
    {
        ModulPengaturan::untuk($this->pesantren->id)->update(
            collect($modul)->mapWithKeys(fn (Modul $m) => [$m->kolom() => false])->all()
        );
    }

    public function test_cluster_hilang_dari_sidebar_saat_modulnya_dimatikan(): void
    {
        $this->actingAs($this->admin());

        $this->assertTrue(Clusters\Akademik::canAccessClusteredComponents());
        $this->assertTrue(Clusters\Akademik::shouldRegisterNavigation());

        $this->matikan(Modul::Akademik);

        $this->assertFalse(Clusters\Akademik::canAccessClusteredComponents());
        $this->assertFalse(Clusters\Akademik::shouldRegisterNavigation());
    }

    /**
     * Kedua tes di bawah memeriksa HALAMAN YANG BENAR-BENAR DIRENDER, bukan nilai flag.
     *
     * ⚠️ Sengaja dipecah jadi dua tes yang masing-masing hanya sekali GET. Filament
     * membangun navigasi panel satu kali lalu menyimpannya di instance Panel, dan
     * instance itu bertahan antar-request di dalam SATU test method — jadi mematikan
     * modul di tengah tes lalu GET lagi akan tetap memperlihatkan menu lama, dan
     * tesnya gagal karena alasan yang sama sekali bukan bug produk.
     */
    public function test_tautan_cluster_tampil_saat_modulnya_menyala(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk()
            ->assertSee(Clusters\Kesantrian::getUrl(), escape: false);
    }

    /**
     * Bottom-nav HP disuntikkan lewat render hook BODY_END ke response yang sama,
     * jadi satu asersi ini menutup sidebar desktop DAN bottom-nav sekaligus.
     */
    public function test_tautan_cluster_lenyap_dari_halaman_yang_dirender(): void
    {
        $this->matikan(Modul::Kesantrian);

        $this->actingAs($this->admin())->get('/admin')->assertOk()
            ->assertDontSee(Clusters\Kesantrian::getUrl(), escape: false);
    }

    public function test_cluster_lain_tidak_ikut_hilang(): void
    {
        $admin = $this->admin();
        $this->matikan(Modul::Keuangan);

        $this->actingAs($admin);

        $this->assertFalse(Clusters\Keuangan::canAccessClusteredComponents());

        foreach ([Clusters\Akademik::class, Clusters\Tahfidz::class,
            Clusters\Presensi::class, Clusters\Kesantrian::class] as $cluster) {
            $this->assertTrue(
                $cluster::canAccessClusteredComponents(),
                "{$cluster} ikut hilang padahal modulnya masih menyala."
            );
        }
    }

    public function test_akses_url_langsung_ditolak(): void
    {
        $admin = $this->admin();
        $this->matikan(Modul::Akademik);

        $this->actingAs($admin)
            ->get(NilaiAkademikResource::getUrl('index'))
            ->assertForbidden();
    }

    /**
     * Menu yang hilang tidak boleh menyisakan lubang di tab yang terlanjur terbuka.
     *
     * Filament menjaga hidrasi Livewire lewat hydrateCanAuthorizeAccess(), bukan
     * hanya mount() — tanpa itu, ustadz yang membuka halaman sebelum modul dimatikan
     * masih bisa menyimpan data lewat request Livewire berikutnya.
     */
    public function test_request_livewire_berikutnya_ikut_ditolak(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(NilaiAkademikResource::getUrl('index'))
            ->assertOk();

        $this->matikan(Modul::Akademik);

        $this->actingAs($admin)
            ->get(NilaiAkademikResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_cluster_santri_tidak_pernah_bisa_dimatikan(): void
    {
        $this->matikan(...Modul::cases());

        $this->actingAs($this->admin());

        foreach ([SantriResource::class, KelasResource::class,
            KamarResource::class, PrestasiSantriResource::class] as $resource) {
            $this->assertTrue(
                $resource::canViewAny(),
                "{$resource} ikut dimatikan — Cluster Santri adalah inti sistem dan tidak punya tuas."
            );
        }

        $this->assertTrue(Clusters\Santri::canAccessClusteredComponents());
    }

    /**
     * Asap paling dasar: panel tetap berdiri saat keenam modul dimatikan, dan jalan
     * kembalinya terlihat. Tanpa ini, satu kesalahan gating bisa membuat admin
     * mendarat di halaman rusak tanpa satu pun tautan menuju Pengaturan Modul.
     */
    public function test_panel_tetap_utuh_saat_seluruh_modul_dimatikan(): void
    {
        $this->matikan(...Modul::cases());

        $this->actingAs($this->admin())->get('/admin')->assertOk()
            ->assertSee(ModulPengaturanPage::getUrl(), escape: false)
            ->assertSee(Clusters\Santri::getUrl(), escape: false);
    }

    public function test_super_admin_tidak_terpengaruh(): void
    {
        $this->matikan(...Modul::cases());

        $this->actingAs(User::factory()->superAdmin()->create());

        // pesantren_id null → tidak ada konteks tenant → modul selalu dianggap menyala.
        $this->assertTrue(Modul::Akademik->aktif());
        $this->assertTrue(Modul::Keuangan->aktif());
    }

    /**
     * Penjaga kelengkapan: SETIAP komponen di dalam cluster ikut mati, tanpa kecuali.
     *
     * Resource yang ditambahkan ke sebuah cluster tahun depan tercakup sejak hari ia
     * ditulis — inilah yang menggantikan middleware. Bila tes ini merah, yang salah
     * ada di salah satu dari tiga tempat: HasAdminUstadzAccess, HasAdminOnlyAccess,
     * atau ModulKomponen::modul().
     */
    public function test_setiap_komponen_cluster_ikut_dimatikan(): void
    {
        $admin = $this->admin();

        foreach (self::CLUSTER_MODUL as $cluster => $modul) {
            $komponen = $cluster::getClusteredComponents();

            $this->assertNotEmpty($komponen, "{$cluster} tidak punya komponen terdaftar.");

            ModulPengaturan::untuk($this->pesantren->id)->update(
                collect(Modul::cases())
                    ->mapWithKeys(fn (Modul $m) => [$m->kolom() => $m !== $modul])
                    ->all()
            );

            $this->actingAs($admin);

            foreach ($komponen as $kelas) {
                $this->assertFalse(
                    $kelas::canAccess(),
                    "{$kelas} masih terbuka padahal modul {$modul->value} dimatikan — "
                    .'periksa HasAdminUstadzAccess, HasAdminOnlyAccess, atau ModulKomponen::modul().'
                );
            }
        }
    }
}
