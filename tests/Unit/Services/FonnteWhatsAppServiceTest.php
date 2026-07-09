<?php

namespace Tests\Unit\Services;

use App\Models\WhatsAppGatewaySetting;
use App\Services\FonnteWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FonnteWhatsAppServiceTest extends TestCase
{
    use RefreshDatabase;

    private FonnteWhatsAppService $svc;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fonnte.token' => 'dummy-token',
            'services.fonnte.url' => 'https://api.fonnte.com/send',
            'services.fonnte.default_country_code' => '62',
        ]);

        $this->svc = new FonnteWhatsAppService;
    }

    public function test_normalisasi_leading_nol_menjadi_62(): void
    {
        $this->assertEquals('6281234567890', $this->svc->normalizePhoneNumber('081234567890'));
    }

    public function test_normalisasi_membuang_karakter_non_digit(): void
    {
        $this->assertEquals('6281234567890', $this->svc->normalizePhoneNumber('+62 812-3456-7890'));
    }

    public function test_normalisasi_nomor_terlalu_pendek_mengembalikan_null(): void
    {
        $this->assertNull($this->svc->normalizePhoneNumber('12'));
    }

    public function test_send_sukses(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true, 'detail' => 'success! message in queue'], 200),
        ]);

        $this->svc->send('081234567890', 'Test pesan');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.fonnte.com/send'
            && $request['target'] === '6281234567890'
            && $request->hasHeader('Authorization', 'dummy-token')
        );
    }

    public function test_pakai_token_dari_database_jika_ada_mengalahkan_env(): void
    {
        WhatsAppGatewaySetting::set('fonnte_token', 'token-dari-db');

        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => true, 'detail' => 'success! message in queue'], 200),
        ]);

        $this->svc->send('081234567890', 'Test pesan');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'token-dari-db'));
    }

    public function test_send_gagal_status_false_melempar_exception(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response(['status' => false, 'reason' => 'token invalid'], 200),
        ]);

        $this->expectException(\RuntimeException::class);

        $this->svc->send('081234567890', 'Test pesan');
    }

    public function test_send_5xx_melempar_exception(): void
    {
        Http::fake([
            'api.fonnte.com/*' => Http::response('Internal Server Error', 500),
        ]);

        $this->expectException(RequestException::class);

        $this->svc->send('081234567890', 'Test pesan');
    }

    public function test_send_nomor_tidak_valid_melempar_exception_tanpa_http_call(): void
    {
        Http::fake();

        try {
            $this->svc->send('abc', 'Test pesan');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (\RuntimeException $e) {
            // expected
        }

        Http::assertNothingSent();
    }
}
