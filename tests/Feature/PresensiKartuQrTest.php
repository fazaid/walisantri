<?php

namespace Tests\Feature;

use App\Filament\Resources\Santris\Pages\ViewSantri;
use App\Models\ActivityLog;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use App\Services\KartuPresensiPdf;
use App\Support\KodePresensi;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PresensiKartuQrTest extends TestCase
{
    use RefreshDatabase;

    public function test_setiap_santri_baru_langsung_punya_kode(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        $this->assertNotNull($santri->kode_presensi);
        $this->assertSame(12, strlen($santri->kode_presensi));
    }

    public function test_santri_non_aktif_juga_kebagian_kode(): void
    {
        $pesantren = Pesantren::factory()->create();

        // Generasi kode diletakkan SEBELUM early-return status_aktif di
        // SantriObserver::creating(). Tanpa itu, santri non-aktif lahir tanpa kode
        // dan tidak akan pernah bisa dicetak kartunya saat diaktifkan kembali.
        $santri = Santri::factory()->nonAktif()->create(['pesantren_id' => $pesantren->id]);

        $this->assertNotNull($santri->kode_presensi);
    }

    public function test_kode_tidak_memakai_huruf_yang_mudah_tertukar(): void
    {
        // I/L/1 dan O/0 mudah tertukar saat petugas mengetik ulang kode dari kartu
        // yang QR-nya rusak; U dibuang mengikuti skema Crockford aslinya.
        for ($i = 0; $i < 200; $i++) {
            $kode = KodePresensi::acak();

            $this->assertSame(12, strlen($kode));
            $this->assertDoesNotMatchRegularExpression('/[ILOU]/', $kode);
            $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]+$/', $kode);
        }
    }

    public function test_kode_unik_antar_santri(): void
    {
        $pesantren = Pesantren::factory()->create();

        // Santri non-aktif melewati pemeriksaan kuota paket, jadi bisa dibuat
        // sebanyak yang dibutuhkan tanpa menyentuh SantriObserver::creating() —
        // dan mereka tetap kebagian kode (lihat kasus di atas).
        $kode = collect(range(1, 25))
            ->map(fn () => Santri::factory()->nonAktif()->create(['pesantren_id' => $pesantren->id])->kode_presensi);

        $this->assertCount(25, $kode->unique());
    }

    public function test_generator_menolak_kode_yang_sudah_terpakai(): void
    {
        $pesantren = Pesantren::factory()->create();
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        // buat() mengulang sampai menemukan yang belum ada di tabel — dicek lewat
        // query builder, bukan Eloquent, karena ia juga dipanggil dari migrasi
        // backfill yang berjalan tanpa sesi auth.
        $kode = collect(range(1, 50))->map(fn () => KodePresensi::buat());

        $this->assertFalse($kode->contains($santri->kode_presensi));
        $this->assertCount(50, $kode->unique());
    }

    public function test_payload_qr_berupa_string_opaque_bukan_url(): void
    {
        $payload = KodePresensi::payload('ABCD1234WXYZ');

        // Kamera bawaan ponsel tidak menawarkan "buka tautan" untuk teks biasa,
        // jadi kartunya tidak mengundang eksperimen.
        $this->assertSame('WSP1.ABCD1234WXYZ', $payload);
        $this->assertStringNotContainsString('http', $payload);
        $this->assertStringNotContainsString('/', $payload);
    }

    public function test_pembacaan_payload_menerima_bentuk_lengkap_maupun_kode_saja(): void
    {
        $this->assertSame('ABCD1234WXYZ', KodePresensi::bacaPayload('WSP1.ABCD1234WXYZ'));
        $this->assertSame('ABCD1234WXYZ', KodePresensi::bacaPayload('ABCD1234WXYZ'));
        $this->assertSame('ABCD1234WXYZ', KodePresensi::bacaPayload('  WSP1.ABCD1234WXYZ  '));
    }

    public function test_pdf_kartu_tidak_pernah_memuat_uuid_magic_link(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $kelas = Kelas::factory()->create(['pesantren_id' => $pesantren->id]);

        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'kelas_id' => $kelas->id,
            'nama_lengkap' => 'Ahmad Fauzi',
        ]);

        $this->actingAs($admin);

        $response = app(KartuPresensiPdf::class)->untukKelas($kelas);
        ob_start();
        $response->sendContent();
        $pdf = ob_get_clean();

        $this->assertNotEmpty($pdf);

        // ⚠️ PENJAGA TEMUAN §13.2. santri.uuid adalah token bearer Magic Link:
        // VerifyMagicToken menukarnya jadi Auth::login($wali), sesi wali yang utuh
        // mencakup semua anaknya, SPP, uang saku, dan rapor. Kartu presensi
        // berpindah tangan dan dipotret — kalau suatu saat seseorang
        // "menyederhanakan" kartu QR kembali ke uuid, tes inilah yang menolaknya.
        $this->assertStringNotContainsString($santri->uuid, $pdf);
    }

    public function test_admin_bisa_mengganti_kode_kartu_yang_hilang(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        $kodeLama = $santri->kode_presensi;

        Livewire::actingAs($admin)
            ->test(ViewSantri::class, ['record' => $santri->getKey()])
            ->callAction(TestAction::make('regenerasi_kode_presensi'))
            ->assertHasNoActionErrors();

        $santri->refresh();

        $this->assertNotSame($kodeLama, $santri->kode_presensi);
        $this->assertNotNull($santri->kode_presensi_diperbarui_at);
        $this->assertTrue(ActivityLog::where('event', 'presensi.kode_diregenerasi')->exists());
    }

    public function test_ustadz_tidak_bisa_mengganti_kode(): void
    {
        $pesantren = Pesantren::factory()->create();
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'pembimbing_ustadz_id' => $ustadz->id,
        ]);

        Livewire::actingAs($ustadz)
            ->test(ViewSantri::class, ['record' => $santri->getKey()])
            ->assertActionHidden(TestAction::make('regenerasi_kode_presensi'));
    }
}
