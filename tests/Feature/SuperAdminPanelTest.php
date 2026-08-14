<?php

namespace Tests\Feature;

use App\Enums\StatusBerlangganan;
use App\Enums\UserRole;
use App\Filament\Resources\MasterPengumumen\Pages\ListMasterPengumumen;
use App\Filament\Resources\Pesantrens\Pages\ListPesantrens;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\ExpiringTenantsWidget;
use App\Filament\Widgets\TenantListWidget;
use App\Jobs\CheckExpiredTenants;
use App\Models\ActivityLog;
use App\Models\Kelas;
use App\Models\MasterPengumuman;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use App\Support\PenugasanUstadz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Permukaan super admin sebelumnya tidak punya cakupan tes sama sekali — tidak satu
 * pun tes me-render dashboard atau tabelnya sebagai super admin. Itulah sebabnya
 * beberapa bug (pengumuman global yang mati, tombol Aktifkan yang tidak berefek,
 * hitungan santri yang ikut menghitung yang sudah dihapus) bisa lolos sekian lama.
 * Berkas ini menutup celah itu.
 */
class SuperAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->superAdmin()->create(['pesantren_id' => null]);
    }

    // ---------- Hapus pesantren (butuh konfirmasi ketik nama) ----------

    public function test_hapus_pesantren_ditolak_bila_nama_konfirmasi_tidak_cocok(): void
    {
        $this->actingAs($this->superAdmin());
        $pesantren = Pesantren::factory()->create(['nama_pesantren' => 'Pesantren Al Hikmah']);

        Livewire::test(ListPesantrens::class)
            ->callTableAction('delete', $pesantren, ['konfirmasi_nama' => 'salah ketik'])
            ->assertHasTableActionErrors(['konfirmasi_nama']);

        $this->assertDatabaseHas('pesantrens', ['id' => $pesantren->id]);
    }

    public function test_hapus_pesantren_berhasil_dan_tercatat_di_audit(): void
    {
        $this->actingAs($this->superAdmin());
        $pesantren = Pesantren::factory()->create(['nama_pesantren' => 'Pesantren Al Hikmah']);

        Livewire::test(ListPesantrens::class)
            ->callTableAction('delete', $pesantren, ['konfirmasi_nama' => 'Pesantren Al Hikmah'])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('pesantrens', ['id' => $pesantren->id]);

        // pesantren_id di activity_logs ber-FK nullOnDelete sehingga pasti jadi NULL
        // begitu barisnya terhapus — identitas tenant dicek lewat auditable_id &
        // old_values yang tidak terikat FK.
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'pesantren.deleted',
            'auditable_id' => $pesantren->id,
        ]);
        $this->assertSame(
            'Pesantren Al Hikmah',
            ActivityLog::where('event', 'pesantren.deleted')->value('old_values')['nama_pesantren'],
        );
    }

    public function test_hapus_massal_pesantren_tidak_tersedia(): void
    {
        $this->actingAs($this->superAdmin());
        Pesantren::factory()->count(2)->create();

        $table = Livewire::test(ListPesantrens::class)->instance()->getTable();

        $this->assertSame([], $table->getBulkActions(), 'Tabel Pesantren tidak boleh punya aksi massal apa pun.');
    }

    // ---------- Pesantren baru harus lengkap apa pun jalurnya ----------

    public function test_pesantren_yang_dibuat_dari_panel_dapat_subdomain_dan_amalan_bawaan(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ListPesantrens::class)
            ->callAction('create', [
                'nama_pesantren' => 'Pesantren Nurul Huda',
                'slug' => 'nurul-huda',
                'paket_langganan' => 'rintisan',
                'max_santri_kuota' => 100,
                'status_berlangganan' => 'trial',
                'expired_at' => now()->addDays(14),
            ])
            ->assertHasNoActionErrors();

        $pesantren = Pesantren::where('slug', 'nurul-huda')->sole();

        // Tanpa baris ini subdomain tenant 404 selamanya (PublicTenantResolver).
        $this->assertDatabaseHas('tenant_domains', [
            'pesantren_id' => $pesantren->id,
            'hostname' => 'nurul-huda.'.config('app.base_domain'),
            'type' => 'subdomain',
            'is_primary' => true,
        ]);

        // Tanpa amalan bawaan, modul Mutaba'ah lumpuh diam-diam (skor selalu 0%).
        $this->assertDatabaseHas('kesantrian_amal_master', [
            'pesantren_id' => $pesantren->id,
            'kode' => 'jamaah_5_waktu',
        ]);
    }

    // ---------- Aktifkan tenant (B4) ----------

    public function test_aktifkan_tenant_expired_menetapkan_masa_aktif_baru_dan_bertahan(): void
    {
        $this->actingAs($this->superAdmin());

        $pesantren = Pesantren::factory()->create([
            'status_berlangganan' => 'expired',
            'expired_at' => now()->subDays(3),
        ]);

        Livewire::test(TenantListWidget::class)
            ->callTableAction('aktifkan', $pesantren, ['expired_at' => now()->addMonth()->toDateString()])
            ->assertHasNoTableActionErrors();

        $pesantren->refresh();
        $this->assertSame('active', $pesantren->status_berlangganan);
        $this->assertTrue($pesantren->expired_at->isFuture(), 'expired_at harus dimajukan, bukan dibiarkan di masa lalu.');

        // Inti bugnya: dulu job malam membalik status ke expired lagi SEKALIGUS
        // mengirim ulang notifikasi "masa aktif habis" ke admin yang baru bayar.
        (new CheckExpiredTenants)->handle();

        $pesantren->refresh();
        $this->assertSame('active', $pesantren->status_berlangganan);
    }

    public function test_aktifkan_menolak_tanggal_yang_sudah_lewat(): void
    {
        $this->actingAs($this->superAdmin());

        $pesantren = Pesantren::factory()->create([
            'status_berlangganan' => 'expired',
            'expired_at' => now()->subDays(3),
        ]);

        Livewire::test(TenantListWidget::class)
            ->callTableAction('aktifkan', $pesantren, ['expired_at' => now()->subDay()->toDateString()])
            ->assertHasTableActionErrors(['expired_at']);

        $this->assertSame('expired', $pesantren->refresh()->status_berlangganan);
    }

    public function test_aktivasi_tercatat_di_audit_dengan_identitas_tenant(): void
    {
        $this->actingAs($this->superAdmin());

        $pesantren = Pesantren::factory()->create([
            'status_berlangganan' => 'expired',
            'expired_at' => now()->subDays(3),
        ]);

        Livewire::test(TenantListWidget::class)
            ->callTableAction('aktifkan', $pesantren, ['expired_at' => now()->addMonth()->toDateString()]);

        // Sebelumnya pesantren_id diambil dari pelaku (super admin = NULL), sehingga
        // tindakan super admin tidak pernah muncul di query audit per tenant.
        $this->assertDatabaseHas('activity_logs', [
            'event' => 'pesantren.activated',
            'pesantren_id' => $pesantren->id,
        ]);
    }

    // ---------- Hapus pengguna (B5 & B6) ----------

    public function test_hapus_wali_yang_masih_punya_santri_ditolak_dengan_penjelasan(): void
    {
        $this->actingAs($this->superAdmin());

        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);
        $wali = User::findOrFail($santri->wali_santri_id);

        // Pastikan skenarionya benar-benar terbentuk, supaya tes tidak lulus kebetulan.
        $this->assertSame(1, Santri::withoutGlobalScopes()->where('wali_santri_id', $wali->id)->count());
        $this->assertNotNull(UserResource::alasanTidakBisaDihapus($wali));

        // Sebelum diperbaiki ini melempar SQLSTATE 23503 (FK restrictOnDelete) dan
        // muncul sebagai error 500, bukan pesan yang bisa dipahami.
        Livewire::test(ListUsers::class)
            ->callTableAction('delete', $wali);

        $this->assertDatabaseHas('users', ['id' => $wali->id]);
    }

    public function test_tombol_hapus_disembunyikan_untuk_akun_sendiri(): void
    {
        $su = $this->superAdmin();
        $this->actingAs($su);

        // Kasus struktural: tidak ada gunanya menampilkan tombol yang selalu ditolak.
        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('delete', $su);

        $this->assertDatabaseHas('users', ['id' => $su->id]);
    }

    /**
     * Diuji langsung, bukan lewat tabel: super admin terakhir mustahil dilihat orang lain
     * di panel. Admin pesantren tidak pernah melihat baris super admin sama sekali
     * (UserResource::getEloquentQuery memfilter ke pesantren_id miliknya, sedangkan super
     * admin ber-pesantren_id NULL), dan super admin yang melihat "super admin terakhir"
     * berarti sedang melihat dirinya sendiri — sudah dijaga aturan akun-sendiri.
     * Penjagaan ini murni lapis pertahanan kedua, dan tetap dikunci di sini supaya tidak
     * diam-diam hilang bila aturan akun-sendiri kelak berubah.
     */
    public function test_super_admin_terakhir_dijaga_meski_bukan_akun_sendiri(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $satuSatunya = $this->superAdmin();

        $this->actingAs($admin);

        $this->assertSame(1, User::where('role', UserRole::SuperAdmin->value)->count());
        $this->assertNotNull(UserResource::alasanSembunyikanHapus($satuSatunya));

        // Begitu ada super admin kedua, penjagaan ini melepas.
        User::factory()->superAdmin()->create(['pesantren_id' => null]);
        $this->assertNull(UserResource::alasanSembunyikanHapus($satuSatunya));
    }

    public function test_tombol_hapus_tetap_tampil_untuk_pengguna_yang_masih_punya_santri(): void
    {
        $this->actingAs($this->superAdmin());

        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);
        $wali = User::findOrFail($santri->wali_santri_id);

        // Keterkaitan santri BISA diselesaikan pengguna, jadi tombolnya tetap ada —
        // yang penting alasannya dijelaskan saat diklik, bukan error 500.
        Livewire::test(ListUsers::class)
            ->assertTableActionVisible('delete', $wali);
    }

    public function test_super_admin_terakhir_tidak_bisa_dihapus(): void
    {
        $this->actingAs($this->superAdmin());
        $korban = User::factory()->superAdmin()->create(['pesantren_id' => null]);

        // Masih ada dua super admin -> boleh dihapus.
        Livewire::test(ListUsers::class)->callTableAction('delete', $korban);
        $this->assertDatabaseMissing('users', ['id' => $korban->id]);

        // Kini tersisa satu (pelakunya sendiri) — dijaga oleh aturan "akun sendiri".
        $this->assertSame(1, User::where('role', UserRole::SuperAdmin->value)->count());
        $this->assertNotNull(UserResource::alasanSembunyikanHapus(auth()->user()));
    }

    public function test_pengguna_tanpa_keterkaitan_tetap_bisa_dihapus(): void
    {
        $this->actingAs($this->superAdmin());
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        Livewire::test(ListUsers::class)
            ->callTableAction('delete', $ustadz)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('users', ['id' => $ustadz->id]);
    }

    // ---------- Statistik dashboard (B7 & B8) ----------

    public function test_kartu_akan_expired_cocok_dengan_tabel_di_bawahnya(): void
    {
        $this->actingAs($this->superAdmin());

        // Pendaftar baru berstatus trial — dulu justru inilah yang dilewatkan kartu.
        Pesantren::factory()->create(['status_berlangganan' => 'trial', 'expired_at' => now()->addDays(3)]);
        Pesantren::factory()->create(['status_berlangganan' => 'active', 'expired_at' => now()->addDays(5)]);
        // Di luar jendela 7 hari.
        Pesantren::factory()->create(['status_berlangganan' => 'active', 'expired_at' => now()->addDays(30)]);
        // Sudah tidak berjalan — tidak boleh dihitung maupun ditampilkan.
        Pesantren::factory()->create(['status_berlangganan' => 'suspended', 'expired_at' => now()->addDays(2)]);

        $jumlahKartu = Pesantren::withoutGlobalScope('pesantren')
            ->whereIn('status_berlangganan', StatusBerlangganan::berjalan())
            ->whereBetween('expired_at', [now(), now()->addDays(7)])
            ->count();

        $barisTabel = Livewire::test(ExpiringTenantsWidget::class)
            ->instance()->getTable()->getQuery()->count();

        $this->assertSame(2, $jumlahKartu);
        $this->assertSame($jumlahKartu, $barisTabel, 'Angka kartu "Akan Expired" harus sama dengan jumlah baris tabelnya.');
    }

    public function test_total_santri_tidak_menghitung_santri_yang_sudah_dihapus(): void
    {
        $pesantren = Pesantren::factory()->create();
        Santri::factory()->count(3)->create(['pesantren_id' => $pesantren->id, 'status_aktif' => true]);

        $hitung = fn (): int => Santri::withoutGlobalScope('pesantren')->where('status_aktif', true)->count();

        $this->assertSame(3, $hitung());

        // SantriObserver tidak mengubah status_aktif saat soft-delete, jadi dulu
        // withoutGlobalScopes() (yang ikut mencopot SoftDeletingScope) tetap
        // menghitungnya dan angka platform selalu lebih besar dari kenyataan.
        Santri::withoutGlobalScope('pesantren')->first()->delete();

        $this->assertSame(2, $hitung());
    }

    // ---------- Validasi slug panel (B12) ----------

    public static function slugTidakValid(): array
    {
        return [
            'kata dicadangkan' => ['admin'],
            'ada spasi' => ['Al Hikmah'],
            'huruf besar' => ['NurulHuda'],
            'terlalu pendek' => ['ab'],
        ];
    }

    #[DataProvider('slugTidakValid')]
    public function test_slug_pesantren_dari_panel_divalidasi_seketat_jalur_pendaftaran(string $slug): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ListPesantrens::class)
            ->callAction('create', [
                'nama_pesantren' => 'Pesantren Uji',
                'slug' => $slug,
                'paket_langganan' => 'rintisan',
                'max_santri_kuota' => 100,
                'status_berlangganan' => 'trial',
                'expired_at' => now()->addDays(14),
            ])
            ->assertHasActionErrors(['slug']);

        $this->assertDatabaseMissing('pesantrens', ['slug' => $slug]);
    }

    // ---------- Kolom Penugasan tidak lagi query per baris untuk semua role (B11) ----------

    public function test_ringkasan_penugasan_dilewati_untuk_role_yang_tidak_mungkin_punya_penugasan(): void
    {
        $pesantren = Pesantren::factory()->create();
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);

        $query = 0;
        DB::listen(function () use (&$query) {
            $query++;
        });

        $this->assertSame([], PenugasanUstadz::ringkasan($wali));
        $this->assertSame(0, $query, 'Wali santri — baris terbanyak di tabel Pengguna — tidak boleh memicu query sama sekali.');
    }

    // ---------- Pengumuman global (B1) ----------

    public function test_super_admin_bisa_membuat_pengumuman_global(): void
    {
        $this->actingAs($this->superAdmin());

        // Sebelum diperbaiki, guard di Multitenantable melempar ValidationException ke
        // key pesantren_id — field yang tidak ada di form — sehingga modal gagal simpan
        // tanpa pesan yang terlihat, dan fitur pengumuman lintas-tenant mati total.
        Livewire::test(ListMasterPengumumen::class)
            ->callAction('create', [
                'judul_maklumat' => 'Libur Nasional',
                'isi_maklumat' => '<p>Seluruh pesantren libur.</p>',
                'target_audience' => 'semua',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('master_pengumuman', [
            'judul_maklumat' => 'Libur Nasional',
            'pesantren_id' => null,
        ]);
    }

    public function test_pengumuman_global_terlihat_oleh_admin_pesantren(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ListMasterPengumumen::class)
            ->callAction('create', [
                'judul_maklumat' => 'Libur Nasional',
                'isi_maklumat' => '<p>Seluruh pesantren libur.</p>',
                'target_audience' => 'semua',
            ]);

        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $this->actingAs($admin);

        $terlihat = MasterPengumuman::withoutGlobalScope('pesantren')
            ->where(fn ($q) => $q->where('pesantren_id', $pesantren->id)->orWhereNull('pesantren_id'))
            ->forAdmin()
            ->pluck('judul_maklumat');

        $this->assertContains('Libur Nasional', $terlihat->all());
    }

    public function test_guard_tenant_tetap_menyala_untuk_model_yang_pesantren_id_nya_wajib(): void
    {
        $this->actingAs($this->superAdmin());

        // Pelonggaran untuk pengumuman global tidak boleh bocor ke model lain.
        $this->expectException(ValidationException::class);

        Kelas::create(['nama_kelas' => 'Uji Guard']);
    }

    public function test_pesantren_ber_slug_lama_yang_panjang_tetap_bisa_diedit(): void
    {
        $this->actingAs($this->superAdmin());

        // Panel dulu mengizinkan slug sampai 255 karakter, jadi data seperti ini nyata
        // mungkin ada. Aturan slug yang baru tidak boleh menyanderanya.
        $slugPanjang = 'pesantren-dengan-nama-yang-sangat-panjang-sekali';
        $pesantren = Pesantren::factory()->create([
            'slug' => $slugPanjang,
            'paket_langganan' => 'rintisan',
        ]);

        Livewire::test(ListPesantrens::class)
            ->callTableAction('edit', $pesantren, [
                'nama_pesantren' => $pesantren->nama_pesantren,
                'slug' => $slugPanjang,
                'paket_langganan' => 'berkembang',
                'max_santri_kuota' => 250,
                'status_berlangganan' => 'active',
                'expired_at' => now()->addYear(),
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame('berkembang', $pesantren->refresh()->paket_langganan);
    }

    public function test_mengganti_slug_tetap_divalidasi_ketat(): void
    {
        $this->actingAs($this->superAdmin());

        $pesantren = Pesantren::factory()->create(['slug' => 'nurul-huda']);

        Livewire::test(ListPesantrens::class)
            ->callTableAction('edit', $pesantren, [
                'nama_pesantren' => $pesantren->nama_pesantren,
                'slug' => 'admin',
                'paket_langganan' => $pesantren->paket_langganan,
                'max_santri_kuota' => $pesantren->max_santri_kuota,
                'status_berlangganan' => $pesantren->status_berlangganan,
                'expired_at' => $pesantren->expired_at,
            ])
            ->assertHasTableActionErrors(['slug']);

        $this->assertSame('nurul-huda', $pesantren->refresh()->slug);
    }
}
