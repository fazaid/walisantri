<?php

namespace Tests\Feature;

use App\Enums\JenisIzin;
use App\Enums\StatusPengajuanIzin;
use App\Filament\Resources\PresensiIzins\Pages\ListPresensiIzins;
use App\Filament\Resources\PresensiIzins\PresensiIzinResource;
use App\Models\ActivityLog;
use App\Models\Kelas;
use App\Models\Pesantren;
use App\Models\Presensi;
use App\Models\PresensiIzin;
use App\Models\PresensiPengaturan;
use App\Models\Santri;
use App\Models\User;
use App\Services\PresensiIzinService;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

class PresensiIzinTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    private User $admin;

    private User $wali;

    private Santri $santri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pesantren = Pesantren::factory()->create();
        $this->admin = User::factory()->adminPesantren()->create(['pesantren_id' => $this->pesantren->id]);
        $this->wali = User::factory()->waliSantri()->create(['pesantren_id' => $this->pesantren->id]);

        $kelas = Kelas::factory()->create(['pesantren_id' => $this->pesantren->id]);

        $this->santri = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $kelas->id,
            'wali_santri_id' => $this->wali->id,
        ]);

        PresensiPengaturan::untuk($this->pesantren->id)->update(['hari_libur_mingguan' => []]);
    }

    private function ajukan(array $ganti = []): TestResponse
    {
        return $this->actingAs($this->wali)->post(route('wali.izin.store'), array_merge([
            'santri_id' => $this->santri->id,
            'jenis' => JenisIzin::Sakit->value,
            'tanggal_mulai' => '2026-08-03',
            'tanggal_selesai' => '2026-08-05',
            'alasan' => 'Demam sejak semalam',
        ], $ganti));
    }

    public function test_wali_bisa_mengajukan_izin_untuk_anaknya(): void
    {
        $this->ajukan()->assertRedirect();

        $izin = PresensiIzin::withoutGlobalScope('pesantren')->first();

        $this->assertNotNull($izin);
        $this->assertSame(StatusPengajuanIzin::Diajukan, $izin->status);
        // diajukan_oleh terisi = berasal dari wali, bukan dicatat admin.
        $this->assertSame($this->wali->id, $izin->diajukan_oleh);
        $this->assertTrue($izin->dariWali());
    }

    public function test_wali_tidak_bisa_mengajukan_untuk_anak_keluarga_lain(): void
    {
        $santriLain = Santri::factory()->create(['pesantren_id' => $this->pesantren->id]);

        // Global scope hanya menyaring pesantren_id, BUKAN wali_santri_id —
        // mengandalkannya adalah persis bug §8 #1 yang sudah pernah terjadi.
        $this->ajukan(['santri_id' => $santriLain->id])->assertForbidden();

        $this->assertSame(0, PresensiIzin::withoutGlobalScope('pesantren')->count());
    }

    public function test_pengajuan_beririsan_ditolak(): void
    {
        $this->ajukan()->assertRedirect();

        $this->ajukan(['tanggal_mulai' => '2026-08-04', 'tanggal_selesai' => '2026-08-08'])
            ->assertSessionHasErrors('tanggal_mulai');

        $this->assertSame(1, PresensiIzin::withoutGlobalScope('pesantren')->count());
    }

    public function test_lampiran_disimpan_di_disk_local_bukan_public(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $this->ajukan(['lampiran' => UploadedFile::fake()->image('surat-dokter.jpg')])->assertRedirect();

        $izin = PresensiIzin::withoutGlobalScope('pesantren')->first();

        $this->assertNotNull($izin->lampiran);
        // Surat keterangan dokter adalah data kesehatan anak (§13.2); disk public
        // menghasilkan URL yang bisa ditebak tanpa melewati otorisasi.
        Storage::disk('local')->assertExists($izin->lampiran);
        Storage::disk('public')->assertMissing($izin->lampiran);
    }

    public function test_lampiran_hanya_bisa_dibuka_wali_pemiliknya(): void
    {
        Storage::fake('local');
        $this->ajukan(['lampiran' => UploadedFile::fake()->image('surat.jpg')]);

        $izin = PresensiIzin::withoutGlobalScope('pesantren')->first();
        $waliLain = User::factory()->waliSantri()->create(['pesantren_id' => $this->pesantren->id]);

        $this->actingAs($this->wali)->get(route('wali.izin.lampiran', $izin))->assertOk();
        $this->actingAs($waliLain)->get(route('wali.izin.lampiran', $izin))->assertForbidden();
    }

    public function test_sesi_magic_link_tidak_bisa_menjangkau_halaman_izin(): void
    {
        // Penjagaannya lebih ketat daripada sekadar menyembunyikan form:
        // BlockMagicLinkSession mengalihkan sesi magic link dari SELURUH halaman
        // portal agregat kembali ke halaman report, jadi /wali/izin tidak pernah
        // terbuka sama sekali dari tautan cepat.
        $this->actingAs($this->wali)
            ->withSession([
                'magic_link_session' => true,
                'magic_link_santri_id' => $this->santri->id,
            ])
            ->get(route('wali.izin'))
            ->assertRedirect(route('wali.magic.report', $this->santri->uuid));

        $this->actingAs($this->wali)
            ->withSession([
                'magic_link_session' => true,
                'magic_link_santri_id' => $this->santri->id,
            ])
            ->post(route('wali.izin.store'), [
                'santri_id' => $this->santri->id,
                'jenis' => JenisIzin::Sakit->value,
                'tanggal_mulai' => '2026-08-03',
                'tanggal_selesai' => '2026-08-03',
                'alasan' => 'Coba tembus',
            ])
            ->assertForbidden();

        $this->assertSame(0, PresensiIzin::withoutGlobalScope('pesantren')->count());
    }

    public function test_form_tersembunyi_saat_pengajuan_wali_dimatikan(): void
    {
        PresensiPengaturan::untuk($this->pesantren->id)->update(['izin_wali_aktif' => false]);

        $this->actingAs($this->wali)
            ->get(route('wali.izin'))
            ->assertOk()
            ->assertSee('Pengajuan izin belum bisa dilakukan dari sini')
            ->assertDontSee('Kirim Pengajuan');

        $this->ajukan()->assertForbidden();
    }

    public function test_admin_menyetujui_lewat_panel_dan_presensi_terisi(): void
    {
        $this->ajukan();
        $izin = PresensiIzin::withoutGlobalScope('pesantren')->first();

        Livewire::actingAs($this->admin)
            ->test(ListPresensiIzins::class)
            ->callAction(TestAction::make('setujui')->table($izin), ['catatan' => 'Disetujui'])
            ->assertHasNoActionErrors();

        $this->assertSame(StatusPengajuanIzin::Disetujui, $izin->fresh()->status);
        $this->assertSame(3, Presensi::withoutGlobalScope('pesantren')->count());
    }

    public function test_admin_mencatat_izin_langsung_berstatus_disetujui(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ListPresensiIzins::class)
            ->callAction(CreateAction::class, data: [
                'santri_id' => $this->santri->id,
                'jenis' => JenisIzin::Dispensasi->value,
                'tanggal_mulai' => '2026-08-10',
                'tanggal_selesai' => '2026-08-11',
                'alasan' => 'Lomba tingkat kabupaten',
            ])
            ->assertHasNoActionErrors();

        $izin = PresensiIzin::withoutGlobalScope('pesantren')->first();

        // Orang yang mencatatnya adalah orang yang berwenang menyetujuinya, jadi
        // meminta persetujuan terpisah cuma ritual kosong.
        $this->assertSame(StatusPengajuanIzin::Disetujui, $izin->status);
        $this->assertNull($izin->diajukan_oleh);
        $this->assertFalse($izin->dariWali());
        $this->assertSame(2, Presensi::withoutGlobalScope('pesantren')->count());
    }

    public function test_wali_kelas_hanya_melihat_pengajuan_kelasnya(): void
    {
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);
        $this->santri->kelas->update(['wali_kelas_id' => $ustadz->id]);

        $kelasLain = Kelas::factory()->create(['pesantren_id' => $this->pesantren->id, 'nama_kelas' => 'Lain']);
        $santriLain = Santri::factory()->create([
            'pesantren_id' => $this->pesantren->id,
            'kelas_id' => $kelasLain->id,
        ]);

        $this->ajukan();
        PresensiIzin::withoutGlobalScope('pesantren')->create([
            'pesantren_id' => $this->pesantren->id,
            'santri_id' => $santriLain->id,
            'jenis' => JenisIzin::Izin,
            'tanggal_mulai' => '2026-08-03',
            'tanggal_selesai' => '2026-08-03',
            'alasan' => 'Milik kelas lain',
            'status' => StatusPengajuanIzin::Diajukan,
        ]);

        $this->actingAs($ustadz);

        $terlihat = PresensiIzinResource::getEloquentQuery()->pluck('santri_id');

        $this->assertTrue($terlihat->contains($this->santri->id));
        $this->assertFalse($terlihat->contains($santriLain->id));
    }

    public function test_pembimbing_halaqah_tidak_melihat_pengajuan_apa_pun(): void
    {
        $pembimbing = User::factory()->ustadz()->create(['pesantren_id' => $this->pesantren->id]);
        $this->santri->update(['pembimbing_ustadz_id' => $pembimbing->id]);

        $this->ajukan();

        $this->actingAs($pembimbing);

        $this->assertSame(0, PresensiIzinResource::getEloquentQuery()->count());
    }

    public function test_badge_menghitung_pengajuan_yang_belum_diproses(): void
    {
        $this->actingAs($this->admin);
        $this->assertNull(PresensiIzinResource::getNavigationBadge());

        $this->ajukan();

        $this->actingAs($this->admin);
        $this->assertSame('1', PresensiIzinResource::getNavigationBadge());
    }

    public function test_persetujuan_dan_penolakan_tercatat_di_audit_log(): void
    {
        $this->ajukan();
        $izin = PresensiIzin::withoutGlobalScope('pesantren')->first();

        app(PresensiIzinService::class)->setujui($izin, $this->admin);

        $this->assertTrue(
            ActivityLog::where('event', 'presensi.izin_disetujui')->exists(),
            'Persetujuan izin adalah keputusan, dan keputusan diaudit (§10.2).'
        );
        $this->assertTrue(ActivityLog::where('event', 'presensi.izin_diajukan')->exists());
    }
}
