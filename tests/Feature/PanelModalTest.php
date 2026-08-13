<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\MasterPengumumen\MasterPengumumanResource;
use App\Filament\Resources\MasterPengumumen\Pages\ListMasterPengumumen;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Halaman Create/Edit terpisah sudah dihapus — tambah/ubah kini lewat modal di
 * halaman daftar. Logika yang dulu menempel di CreateUser/EditUser dan
 * CreateMasterPengumuman ikut pindah ke aksi modalnya; tes ini mengunci
 * perpindahan itu supaya tidak diam-diam hilang.
 */
class PanelModalTest extends TestCase
{
    use RefreshDatabase;

    private function adminPesantren(Pesantren $pesantren): User
    {
        return User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
    }

    // ---------- Pengguna ----------

    public function test_admin_bisa_tambah_pengguna_lewat_modal_dan_tenant_terkunci(): void
    {
        $pesantren = Pesantren::factory()->create();
        $lain = Pesantren::factory()->create();

        $this->actingAs($this->adminPesantren($pesantren));

        Livewire::test(ListUsers::class)
            ->callAction('create', [
                'name' => 'Ustadz Baru',
                'email' => 'ustadz.baru@contoh.test',
                'role' => UserRole::Ustadz->value,
                'pesantren_id' => $lain->id,
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'ustadz.baru@contoh.test',
            'role' => UserRole::Ustadz->value,
            // pesantren_id dari form diabaikan, dipaksa ke milik admin.
            'pesantren_id' => $pesantren->id,
        ]);
    }

    public function test_admin_ditolak_saat_mencoba_mengangkat_super_admin_lewat_modal(): void
    {
        // Pertahanan lapis pertama: opsi Super Admin memang tidak ditawarkan ke admin
        // pesantren (UserForm), jadi nilai selundupan gugur di validasi Select.
        $pesantren = Pesantren::factory()->create();

        $this->actingAs($this->adminPesantren($pesantren));

        Livewire::test(ListUsers::class)
            ->callAction('create', [
                'name' => 'Penyusup',
                'email' => 'penyusup@contoh.test',
                'role' => UserRole::SuperAdmin->value,
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertHasActionErrors(['role']);

        $this->assertDatabaseMissing('users', ['email' => 'penyusup@contoh.test']);
    }

    public function test_penjaga_role_dan_tenant_ikut_pindah_ke_aksi_modal(): void
    {
        // Pertahanan lapis kedua: logika yang dulu ada di CreateUser/EditUser sekarang
        // hidup sebagai closure di UserResource. Diuji langsung karena validasi form
        // sudah menyaring lebih dulu, sehingga jalur ini tidak terjangkau lewat modal.
        $pesantren = Pesantren::factory()->create();
        $lain = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($this->adminPesantren($pesantren));

        $dibuat = (UserResource::paksaTenantSaatBuat())([
            'role' => UserRole::SuperAdmin->value,
            'pesantren_id' => $lain->id,
        ]);

        $this->assertSame(UserRole::WaliSantri->value, $dibuat['role']);
        $this->assertSame($pesantren->id, $dibuat['pesantren_id']);

        $diubah = (UserResource::paksaTenantSaatUbah())([
            'role' => UserRole::SuperAdmin->value,
            'pesantren_id' => $lain->id,
        ], $ustadz);

        // Saat mengubah, role selundupan dikembalikan ke role lama record —
        // bukan didegradasi jadi wali santri.
        $this->assertSame(UserRole::Ustadz->value, $diubah['role']);
        $this->assertSame($pesantren->id, $diubah['pesantren_id']);
    }

    public function test_super_admin_tidak_terkena_penjaga_tenant(): void
    {
        $lain = Pesantren::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create());

        $dibuat = (UserResource::paksaTenantSaatBuat())([
            'role' => UserRole::SuperAdmin->value,
            'pesantren_id' => $lain->id,
        ]);

        $this->assertSame(UserRole::SuperAdmin->value, $dibuat['role']);
        $this->assertSame($lain->id, $dibuat['pesantren_id']);
    }

    // ---------- Pengumuman ----------

    public function test_admin_bisa_tambah_pengumuman_lewat_modal_dengan_pemilik_otomatis(): void
    {
        $pesantren = Pesantren::factory()->create();

        $this->actingAs($this->adminPesantren($pesantren));

        Livewire::test(ListMasterPengumumen::class)
            ->callAction('create', [
                'judul_maklumat' => 'Libur Awal Tahun',
                'isi_maklumat' => 'Kegiatan diliburkan sepekan.',
                'target_audience' => 'semua',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('master_pengumuman', [
            'judul_maklumat' => 'Libur Awal Tahun',
            'pesantren_id' => $pesantren->id,
        ]);
    }

    // ---------- Tautan checklist onboarding ----------

    public function test_tautan_checklist_onboarding_membuka_modal_bukan_halaman_mati(): void
    {
        // Halaman 'create' kedua resource ini sudah tidak ada; widget harus menunjuk
        // ke halaman daftar dengan ?action=create supaya modalnya langsung terbuka.
        $pesantren = Pesantren::factory()->create();
        $admin = $this->adminPesantren($pesantren);

        $urlUstadz = UserResource::getUrl('index', ['action' => 'create', 'role' => UserRole::Ustadz->value]);
        $urlPengumuman = MasterPengumumanResource::getUrl('index', ['action' => 'create']);

        $this->assertStringContainsString('action=create', $urlUstadz);
        $this->assertStringContainsString('role=ustadz', $urlUstadz);
        $this->assertStringContainsString('action=create', $urlPengumuman);

        $this->actingAs($admin);
        $this->get($urlUstadz)->assertOk();
        $this->get($urlPengumuman)->assertOk();
    }
}
