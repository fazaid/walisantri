<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MenyemaiWilayah;
use Tests\TestCase;

/**
 * Endpoint kaskade wilayah yang memberi makan dropdown di /register (§4.1).
 */
class WilayahEndpointTest extends TestCase
{
    use MenyemaiWilayah, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->semaiWilayahContoh();
    }

    private function wilayahUrl(string $parent = ''): string
    {
        return 'http://'.config('app.base_domain').'/wilayah'.($parent === '' ? '' : '/'.$parent);
    }

    public function test_tanpa_induk_mengembalikan_daftar_provinsi(): void
    {
        $this->get($this->wilayahUrl())
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['kode' => '32', 'nama' => 'Jawa Barat'])
            ->assertJsonFragment(['kode' => '33', 'nama' => 'Jawa Tengah']);
    }

    public function test_mengembalikan_anak_dari_kode_kecamatan(): void
    {
        $this->get($this->wilayahUrl('32.01.01'))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['kode' => '32.01.01.1011', 'nama' => 'Cirimekar'])
            // Cabang provinsi lain tidak boleh ikut bocor.
            ->assertJsonMissing(['kode' => '33.01.01.2001']);
    }

    /**
     * Desa adalah daun — ia tidak punya anak. Ditolak router lewat regexnya, jadi
     * penyisir kode tidak pernah sampai ke database.
     */
    public function test_kode_desa_ditolak_router_karena_tidak_punya_anak(): void
    {
        $this->get($this->wilayahUrl('32.01.01.1006'))->assertNotFound();
    }

    public function test_kode_berformat_aneh_ditolak_router(): void
    {
        $this->get($this->wilayahUrl('abc'))->assertNotFound();
        $this->get($this->wilayahUrl('3'))->assertNotFound();
        $this->get($this->wilayahUrl('32.'))->assertNotFound();
    }

    /**
     * Induk yang tidak dikenal mengembalikan daftar kosong, bukan 404: klien tinggal
     * menampilkan select kosong, tanpa perlu cabang penanganan galat sendiri.
     */
    public function test_induk_tak_dikenal_mengembalikan_daftar_kosong(): void
    {
        $this->get($this->wilayahUrl('99'))->assertOk()->assertExactJson([]);
    }

    public function test_respons_membawa_cache_control_publik(): void
    {
        $respons = $this->get($this->wilayahUrl('32'));

        $this->assertStringContainsString('public', $respons->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=86400', $respons->headers->get('Cache-Control'));
    }

    public function test_endpoint_dibatasi_rate_limit(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $this->get($this->wilayahUrl('32'))->assertOk();
        }

        $this->get($this->wilayahUrl('32'))->assertStatus(429);
    }
}
