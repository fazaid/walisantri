<?php

namespace Tests\Unit\Services;

use App\Models\Pesantren;
use App\Services\BillingCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillingCalculatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingCalculatorService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new BillingCalculatorService();
    }

    public function test_paket_gratis(): void
    {
        $p = new Pesantren(['paket_langganan' => 'gratis', 'max_santri_kuota' => 10]);
        $r = $this->svc->hitung($p);

        $this->assertEquals(0, $r['total_biaya']);
        $this->assertEquals(10, $r['kuota_maksimal']);
    }

    public function test_paket_rintisan(): void
    {
        $p = new Pesantren(['paket_langganan' => 'rintisan', 'max_santri_kuota' => 100]);
        $r = $this->svc->hitung($p);

        $this->assertEquals(150_000, $r['total_biaya']);
        $this->assertEquals(100, $r['kuota_maksimal']);
    }

    public function test_paket_berkembang(): void
    {
        $p = new Pesantren(['paket_langganan' => 'berkembang', 'max_santri_kuota' => 500]);
        $r = $this->svc->hitung($p);

        $this->assertEquals(450_000, $r['total_biaya']);
        $this->assertEquals(500, $r['kuota_maksimal']);
    }

    #[DataProvider('maju_provider')]
    public function test_paket_maju_formula(int $quota, int $expectedBiaya, int $expectedKuota): void
    {
        $r = $this->svc->paketMaju($quota);

        $this->assertEquals($expectedBiaya, $r['total_biaya'],
            "Quota={$quota}: biaya salah");
        $this->assertEquals($expectedKuota, $r['kuota_maksimal'],
            "Quota={$quota}: kuota salah");
    }

    public static function maju_provider(): array
    {
        // PRD §5.2: X=CEIL((N-1000)/100), Total=750k+(X*100k), Kuota=1000+(X*100)
        return [
            'base 1001'  => [1001, 850_000, 1100],
            'base 1100'  => [1100, 850_000, 1100],
            'base 1200'  => [1200, 950_000, 1200], // contoh di PRD
            'base 1500'  => [1500, 1_250_000, 1500],
        ];
    }
}
