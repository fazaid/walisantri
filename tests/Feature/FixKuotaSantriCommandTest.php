<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Perintah `billing:fix-kuota` dulu memakai daftar paket tulis-tangan yang hanya
 * memuat rintisan & berkembang. Karena daftar itu sekaligus jadi filter `whereIn`,
 * pesantren paket Tumbuh tidak pernah ikut diperiksa — dan tidak ada pesan apa pun
 * yang memberi tahu bahwa mereka dilewati.
 */
class FixKuotaSantriCommandTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(string $paket, int $kuota): Pesantren
    {
        return Pesantren::create([
            'nama_pesantren' => "Pesantren {$paket}",
            'slug' => 'pesantren-kuota-'.uniqid(),
            'paket_langganan' => $paket,
            'max_santri_kuota' => $kuota,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addMonth(),
        ]);
    }

    public function test_pesantren_paket_tumbuh_ikut_diperbaiki(): void
    {
        $pesantren = $this->makePesantren('tumbuh', 100);

        $this->artisan('billing:fix-kuota')
            ->expectsConfirmation('Lanjutkan perbaikan untuk 1 tenant?', 'yes')
            ->assertSuccessful();

        $this->assertSame(250, $pesantren->refresh()->max_santri_kuota);
    }

    /**
     * Kuota paket Maju dihitung per-tenant lewat formula §5.3, jadi angka yang
     * berbeda dari 1.000 adalah kapasitas yang memang dibeli. Menyeragamkannya
     * akan menghapus add-on yang sudah dibayar.
     */
    public function test_kuota_kustom_paket_maju_tidak_diseragamkan(): void
    {
        $pesantren = $this->makePesantren('maju', 3000);

        $this->artisan('billing:fix-kuota')->assertSuccessful();

        $this->assertSame(3000, $pesantren->refresh()->max_santri_kuota);
    }
}
