<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\TahfidzProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kartu "Setoran Tahfidz" di beranda wali sengaja dipotong 5 baris: di layar
 * ponsel daftar 10 mendorong kartu Kesehatan/SPP/Uang Saku jauh ke bawah
 * lipatan. Yang 10 terakhir tetap tersedia satu ketukan jauhnya, di halaman
 * Statistik Tahfidz.
 */
class WaliDashboardSetoranTest extends TestCase
{
    use RefreshDatabase;

    private function santriDenganWali(): Santri
    {
        $pesantren = Pesantren::factory()->create();
        // §1.8 Fase 1: permukaan wali hidup di host pesantren.
        $this->pakaiHostTenant($pesantren);
        $wali = User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);
        $ustadz = User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        return Santri::factory()->create([
            'pesantren_id' => $pesantren->id,
            'wali_santri_id' => $wali->id,
            'pembimbing_ustadz_id' => $ustadz->id,
            'status_aktif' => true,
        ]);
    }

    /** Bikin $jumlah setoran; indeks 1 paling baru, indeks $jumlah paling lama. */
    private function catatSetoran(Santri $santri, int $jumlah): void
    {
        for ($i = 1; $i <= $jumlah; $i++) {
            TahfidzProgress::create([
                'pesantren_id' => $santri->pesantren_id,
                'santri_id' => $santri->id,
                'ustadz_id' => $santri->pembimbing_ustadz_id,
                'tanggal' => now()->subDays($i)->toDateString(),
                'tipe_setoran' => 'Sabaq',
                'nama_surah' => sprintf('PenandaSetoran%02d', $i),
                'halaman_mulai' => $i,
                'halaman_selesai' => $i,
                'nilai_kelancaran' => 'Mumtaz',
            ]);
        }
    }

    public function test_dashboard_hanya_menampilkan_lima_setoran_terbaru(): void
    {
        $santri = $this->santriDenganWali();
        $this->catatSetoran($santri, 8);

        $response = $this->actingAs($santri->wali)
            ->get(route('wali.dashboard'))
            ->assertOk()
            ->assertSee('5 terakhir');

        foreach (['01', '02', '03', '04', '05'] as $terbaru) {
            $response->assertSee('PenandaSetoran'.$terbaru);
        }

        foreach (['06', '07', '08'] as $terlama) {
            $response->assertDontSee('PenandaSetoran'.$terlama);
        }
    }

    public function test_dashboard_menautkan_ke_sepuluh_setoran_terakhir_saat_daftar_penuh(): void
    {
        $santri = $this->santriDenganWali();
        $this->catatSetoran($santri, 8);

        $this->actingAs($santri->wali)
            ->get(route('wali.dashboard'))
            ->assertOk()
            ->assertSee('Lihat 10 setoran terakhir')
            ->assertSee(route('wali.santri.tahfidz', $santri->id));
    }

    public function test_tautan_penutup_diam_saat_setoran_belum_sampai_lima(): void
    {
        $santri = $this->santriDenganWali();
        $this->catatSetoran($santri, 3);

        $this->actingAs($santri->wali)
            ->get(route('wali.dashboard'))
            ->assertOk()
            ->assertSee('PenandaSetoran03')
            ->assertDontSee('Lihat 10 setoran terakhir');
    }

    public function test_halaman_statistik_tetap_menampilkan_sepuluh_terakhir(): void
    {
        // TahfidzStatsController meng-agregasi per bulan pakai TO_CHAR — fungsi
        // Postgres yang tak dikenal SQLite in-memory (phpunit.xml). Jalankan
        // lewat phpunit.pgsql.xml untuk mendapat pertanggungan penuh.
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('Halaman statistik tahfidz butuh PostgreSQL (TO_CHAR).');
        }

        $santri = $this->santriDenganWali();
        $this->catatSetoran($santri, 12);

        $response = $this->actingAs($santri->wali)
            ->get(route('wali.santri.tahfidz', $santri->id))
            ->assertOk()
            ->assertSee('10 terakhir');

        foreach (range(1, 10) as $i) {
            $response->assertSee(sprintf('PenandaSetoran%02d', $i));
        }

        $response->assertDontSee('PenandaSetoran11')
            ->assertDontSee('PenandaSetoran12');
    }
}
