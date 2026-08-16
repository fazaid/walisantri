<?php

namespace Tests\Feature;

use App\Models\DemoRequest;
use App\Models\PlatformSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemoPageTest extends TestCase
{
    use RefreshDatabase;

    private function demoUrl(): string
    {
        return 'http://'.config('app.base_domain').'/demo';
    }

    private function payloadValid(): array
    {
        return [
            'nama_pesantren' => 'Pesantren Uji Coba',
            'nama_kontak' => 'Pengurus Uji',
            'email' => 'pengurus@contoh.test',
            'no_hp' => '081234567890',
            'form_token' => Crypt::encryptString((string) now()->subMinute()->timestamp),
        ];
    }

    public function test_halaman_demo_terbuka_secara_bawaan(): void
    {
        $this->withoutVite();

        $this->get($this->demoUrl())->assertOk();
    }

    public function test_halaman_demo_404_saat_kill_switch_dimatikan(): void
    {
        $this->withoutVite();
        PlatformSetting::set('demo_open', false);

        $this->get($this->demoUrl())->assertNotFound();
    }

    /**
     * Menyembunyikan formulir tidak menutup endpoint-nya — POST tetap bisa
     * dirakit tangan, jadi store() dijaga terpisah dari show().
     */
    public function test_submit_demo_ditolak_saat_kill_switch_dimatikan(): void
    {
        Queue::fake();
        PlatformSetting::set('demo_open', false);

        $this->post($this->demoUrl(), $this->payloadValid())->assertNotFound();

        $this->assertDatabaseCount(DemoRequest::class, 0);
    }

    public function test_submit_demo_tetap_diterima_saat_kill_switch_menyala(): void
    {
        Queue::fake();
        PlatformSetting::set('demo_open', true);

        $this->post($this->demoUrl(), $this->payloadValid())->assertRedirect();

        $this->assertDatabaseCount(DemoRequest::class, 1);
    }
}
