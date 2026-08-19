<?php

namespace Tests\Feature;

use App\Filament\Resources\EkskulMasters\Pages\ListEkskulMasters;
use App\Filament\Resources\Kamars\KamarResource;
use App\Filament\Resources\Kamars\Pages\ListKamars;
use App\Filament\Resources\Kelas\Pages\ListKelas;
use App\Filament\Resources\KesantrianMutabaahs\Pages\ListKesantrianMutabaahs;
use App\Models\EkskulMaster;
use App\Models\Kamar;
use App\Models\Kelas;
use App\Models\KesantrianMutabaah;
use App\Models\MataPelajaran;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\SantriEkskul;
use App\Models\User;
use App\Support\PenugasanUstadz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Jenis ustadz (pembimbing, pengampu, penguji, pembina, wali kelas, musyrif) adalah
 * PENUGASAN, bukan role — disimpan sebagai FK di entitas yang ditugaskan supaya
 * satu orang bisa merangkap. Tes ini mengunci dua hal: penugasan baru tersimpan
 * & tampil, dan cakupan data tetap TERPISAH PER MODUL (tidak melebar).
 */
class PenugasanUstadzTest extends TestCase
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

    // ---------- Wali kelas ----------

    public function test_admin_bisa_menetapkan_wali_kelas_lewat_modal(): void
    {
        $ustadz = $this->ustadz();
        $this->actingAs($this->admin());

        Livewire::test(ListKelas::class)
            ->callAction('create', [
                'nama_kelas' => 'Ulya 1',
                'wali_kelas_id' => $ustadz->id,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('kelas', [
            'nama_kelas' => 'Ulya 1',
            'wali_kelas_id' => $ustadz->id,
        ]);
    }

    public function test_nama_wali_kelas_tampil_di_daftar_kelas(): void
    {
        $ustadz = $this->ustadz();
        Kelas::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'nama_kelas' => 'Wustha 2',
            'wali_kelas_id' => $ustadz->id,
        ]);

        $this->actingAs($this->admin());

        Livewire::test(ListKelas::class)->assertSee($ustadz->name);
    }

    public function test_kelas_boleh_tanpa_wali_dan_wali_boleh_memegang_beberapa_kelas(): void
    {
        $ustadz = $this->ustadz();

        Kelas::factory()->create(['pesantren_id' => $this->pesantren->id, 'nama_kelas' => 'A']);
        Kelas::factory()->create([
            'pesantren_id' => $this->pesantren->id, 'nama_kelas' => 'B', 'wali_kelas_id' => $ustadz->id,
        ]);
        Kelas::factory()->create([
            'pesantren_id' => $this->pesantren->id, 'nama_kelas' => 'C', 'wali_kelas_id' => $ustadz->id,
        ]);

        $this->assertCount(2, PenugasanUstadz::kelasIdsPerwalian($ustadz->id));
    }

    // ---------- Musyrif kamar ----------

    public function test_admin_bisa_menetapkan_musyrif_kamar_lewat_modal(): void
    {
        $ustadz = $this->ustadz();
        $this->actingAs($this->admin());

        Livewire::test(ListKamars::class)
            ->callAction('create', [
                'nama_kamar' => 'Kamar Abu Bakar',
                'musyrif_id' => $ustadz->id,
                'kapasitas' => 8,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('kamar', [
            'nama_kamar' => 'Kamar Abu Bakar',
            'musyrif_id' => $ustadz->id,
        ]);
    }

    public function test_nama_musyrif_tampil_di_daftar_kamar(): void
    {
        $ustadz = $this->ustadz();
        Kamar::create([
            'pesantren_id' => $this->pesantren->id,
            'nama_kamar' => 'Kamar Umar',
            'musyrif_id' => $ustadz->id,
            'kapasitas' => 8,
        ]);

        $this->actingAs($this->admin());

        Livewire::test(ListKamars::class)->assertSee($ustadz->name);
    }

    public function test_kamar_boleh_tanpa_musyrif_dan_musyrif_boleh_memegang_beberapa_kamar(): void
    {
        $ustadz = $this->ustadz();

        Kamar::create(['pesantren_id' => $this->pesantren->id, 'nama_kamar' => 'A']);
        Kamar::create([
            'pesantren_id' => $this->pesantren->id, 'nama_kamar' => 'B', 'musyrif_id' => $ustadz->id,
        ]);
        Kamar::create([
            'pesantren_id' => $this->pesantren->id, 'nama_kamar' => 'C', 'musyrif_id' => $ustadz->id,
        ]);

        // Sengaja lewat query langsung, bukan lewat method PenugasanUstadz: musyrif
        // adalah penugasan LABEL SAJA, jadi method jalurnya tidak dibuat sama sekali.
        $this->assertSame(2, Kamar::where('musyrif_id', $ustadz->id)->count());
    }

    public function test_musyrif_kamar_tidak_membuka_akses_data_apa_pun(): void
    {
        // Keputusan sadar (§5.4): musyrif adalah label, bukan hak akses. Kalau suatu
        // saat cakupannya sengaja dibuka, tes INI yang harus diubah lebih dulu — bukan
        // diam-diam melebar karena refactor. Sekelas dengan tes pengampu di bawah.
        $musyrif = $this->ustadz();
        $pembimbing = $this->ustadz();

        $kamar = Kamar::create([
            'pesantren_id' => $this->pesantren->id,
            'nama_kamar' => 'Kamar Utsman',
            'musyrif_id' => $musyrif->id,
        ]);

        $santri = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kamar_id' => $kamar->id,
            'pembimbing_ustadz_id' => $pembimbing->id,
            'nama_lengkap' => 'Santri Kamar Utsman',
        ]);

        KesantrianMutabaah::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santri->id,
            'tanggal' => now()->toDateString(),
            'amalan' => [],
        ]);

        Livewire::actingAs($musyrif)
            ->test(ListKesantrianMutabaahs::class)
            ->assertDontSee('Santri Kamar Utsman');

        // Ia bahkan tidak bisa membuka menu Kamar-nya sendiri — KamarResource tetap
        // admin-only, tidak disentuh perubahan ini.
        $this->actingAs($musyrif);
        $this->assertFalse(KamarResource::canViewAny());
    }

    // ---------- Pembina ekskul ----------

    public function test_pembina_ekskul_memakai_nama_akun_bila_tertaut(): void
    {
        $ustadz = $this->ustadz();
        $ekskul = EkskulMaster::create([
            'pesantren_id' => $this->pesantren->id,
            'nama' => 'Kaligrafi',
            'pembina_id' => $ustadz->id,
        ]);

        $this->assertSame($ustadz->name, $ekskul->namaPembina());
    }

    public function test_pembina_ekskul_jatuh_ke_teks_bebas_bila_tanpa_akun(): void
    {
        // Pelatih luar (silat, pramuka) tidak punya akun — jalur `pengajar` harus
        // tetap terbaca, termasuk untuk data lama sebelum kolom pembina_id ada.
        $ekskul = EkskulMaster::create([
            'pesantren_id' => $this->pesantren->id,
            'nama' => 'Pencak Silat',
            'pengajar' => 'Kang Asep',
        ]);

        $this->assertSame('Kang Asep', $ekskul->namaPembina());

        $kosong = EkskulMaster::create([
            'pesantren_id' => $this->pesantren->id,
            'nama' => 'Panahan',
        ]);

        $this->assertNull($kosong->namaPembina());
    }

    public function test_admin_bisa_menetapkan_pembina_ekskul_lewat_modal(): void
    {
        $ustadz = $this->ustadz();
        $this->actingAs($this->admin());

        Livewire::test(ListEkskulMasters::class)
            ->callAction('create', [
                'nama' => 'Tahsin',
                'pembina_id' => $ustadz->id,
                'aktif' => true,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('ekskul_masters', [
            'nama' => 'Tahsin',
            'pembina_id' => $ustadz->id,
        ]);
    }

    public function test_jumlah_peserta_tampil_di_daftar_ekskul(): void
    {
        // Regresi: kolom ini pernah dinamai 'santriEkskuls_count' (camelCase),
        // padahal withCount menulis atribut 'santri_ekskuls_count' — akibatnya
        // kolom Peserta selalu kosong tanpa error apa pun.
        $ekskul = EkskulMaster::create([
            'pesantren_id' => $this->pesantren->id,
            'nama' => 'Silat',
        ]);

        foreach (Santri::factory()->count(3)->create(['pesantren_id' => $this->pesantren->id]) as $santri) {
            SantriEkskul::create([
                'pesantren_id' => $this->pesantren->id,
                'santri_id' => $santri->id,
                'ekskul_id' => $ekskul->id,
                'tanggal_mulai' => now()->toDateString(),
            ]);
        }

        $this->actingAs($this->admin());

        Livewire::test(ListEkskulMasters::class)
            ->assertTableColumnStateSet('santri_ekskuls_count', 3, $ekskul);
    }

    // ---------- Ringkasan penugasan ----------

    public function test_ringkasan_menyebut_semua_penugasan_yang_dirangkap(): void
    {
        // Justru inilah alasan role tidak dipecah: satu orang memegang lima
        // penugasan sekaligus, mustahil diwakili satu nilai users.role.
        $ustadz = $this->ustadz();

        $kelas = Kelas::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'nama_kelas' => '3A',
            'wali_kelas_id' => $ustadz->id,
        ]);

        Santri::factory()->count(2)->create([
            'pesantren_id' => $this->pesantren->id,
            'pembimbing_ustadz_id' => $ustadz->id,
            'status_aktif' => true,
        ]);

        MataPelajaran::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $kelas->id,
            'ustadz_id' => $ustadz->id,
            'nama_mapel' => 'Fiqih',
        ]);

        EkskulMaster::create([
            'pesantren_id' => $this->pesantren->id,
            'nama' => 'Kaligrafi',
            'pembina_id' => $ustadz->id,
        ]);

        Kamar::create([
            'pesantren_id' => $this->pesantren->id,
            'nama_kamar' => 'Kamar Abu Bakar',
            'musyrif_id' => $ustadz->id,
        ]);

        $ringkasan = PenugasanUstadz::ringkasan($ustadz);

        $this->assertContains('Pembimbing 2 santri', $ringkasan);
        $this->assertContains('Wali Kelas 3A', $ringkasan);
        // Kamar lazim dinamai "Kamar X", jadi "Musyrif {nama_kamar}" polos sudah
        // berbunyi benar tanpa menyisipkan kata "Kamar" sendiri.
        $this->assertContains('Musyrif Kamar Abu Bakar', $ringkasan);
        $this->assertContains('Pengampu Fiqih 3A', $ringkasan);
        $this->assertContains('Pembina Kaligrafi', $ringkasan);
    }

    public function test_ringkasan_kosong_untuk_ustadz_tanpa_penugasan(): void
    {
        $this->assertSame([], PenugasanUstadz::ringkasan($this->ustadz()));
    }

    // ---------- Cakupan tetap terpisah per modul ----------

    public function test_pengampu_mapel_tidak_bisa_melihat_mutabaah_santri_di_kelasnya(): void
    {
        // Keputusan sadar: penugasan mengampu mapel TIDAK membuka modul lain.
        // Kalau suatu saat cakupan sengaja dilebarkan, tes ini yang harus diubah
        // lebih dulu — bukan diam-diam melebar karena refactor.
        $pengampu = $this->ustadz();
        $pembimbing = $this->ustadz();

        $kelas = Kelas::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'nama_kelas' => '3A',
        ]);

        MataPelajaran::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $kelas->id,
            'ustadz_id' => $pengampu->id,
        ]);

        $santri = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $kelas->id,
            'pembimbing_ustadz_id' => $pembimbing->id,
            'nama_lengkap' => 'Santri Kelas Tiga A',
        ]);

        KesantrianMutabaah::create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santri->id,
            'tanggal' => now()->toDateString(),
            'amalan' => [],
        ]);

        Livewire::actingAs($pengampu)
            ->test(ListKesantrianMutabaahs::class)
            ->assertDontSee('Santri Kelas Tiga A');

        Livewire::actingAs($pembimbing)
            ->test(ListKesantrianMutabaahs::class)
            ->assertSee('Santri Kelas Tiga A');
    }
}
