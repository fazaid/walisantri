<?php

namespace Tests\Unit\Services;

use App\Services\TrendBulanan;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Penjaga luapan bulan Carbon.
 *
 * `subMonths()` meluap saat tanggal acuan tidak ada di bulan tujuan: 31 Agustus
 * dikurangi dua bulan menjadi 31 Juni, yang tidak ada, lalu meluap ke 1 Juli.
 * Akibatnya deret "12 bulan terakhir" memuat bulan yang sama berkali-kali dan
 * melewatkan bulan lain sepenuhnya — grafik dasbor berbohong tanpa satu pun error.
 *
 * ⚠️ Bug ini hanya muncul pada tanggal 29-31, jadi 28 hari dalam sebulan tes yang
 * memakai `now()` apa adanya akan lulus dan menyembunyikannya. Waktu di sini
 * DIBEKUKAN ke tanggal-tanggal berbahaya; jangan diganti ke tanggal "aman".
 */
class TrendBulananTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** @return array<string, array{0: string}> */
    public static function tanggalRawanLuapan(): array
    {
        return [
            '31 Agustus (Juni & September lebih pendek)' => ['2026-08-31'],
            '31 Mei (April & Februari lebih pendek)' => ['2026-05-31'],
            '31 Maret (Februari lebih pendek)' => ['2026-03-31'],
            '30 Maret (Februari lebih pendek)' => ['2026-03-30'],
            '29 Maret (Februari lebih pendek)' => ['2026-03-29'],
            '31 Desember' => ['2026-12-31'],
            '29 Februari tahun kabisat' => ['2028-02-29'],
            '15 Agustus (tanggal aman, pembanding)' => ['2026-08-15'],
        ];
    }

    #[DataProvider('tanggalRawanLuapan')]
    public function test_selalu_menghasilkan_dua_belas_bulan_berbeda_dan_berurutan(string $hariIni): void
    {
        Carbon::setTestNow(Carbon::parse($hariIni.' 10:00:00'));

        $keys = array_column(TrendBulanan::duaBelasBulanTerakhir(), 'key');

        $this->assertCount(12, $keys);
        $this->assertSame(
            12,
            count(array_unique($keys)),
            "Ada bulan yang tercetak lebih dari sekali pada {$hariIni}: ".implode(' ', $keys),
        );

        // Berurutan naik, tepat satu bulan per langkah, dan berakhir di bulan ini.
        $harusnya = [];
        $awal = Carbon::parse($hariIni)->startOfMonth();
        for ($i = 11; $i >= 0; $i--) {
            $harusnya[] = $awal->copy()->subMonths($i)->format('Y-m');
        }

        $this->assertSame($harusnya, $keys);
    }

    #[DataProvider('tanggalRawanLuapan')]
    public function test_label_sejalan_dengan_kuncinya(string $hariIni): void
    {
        Carbon::setTestNow(Carbon::parse($hariIni.' 10:00:00'));

        foreach (TrendBulanan::duaBelasBulanTerakhir() as $bulan) {
            // '-01' eksplisit, BUKAN createFromFormat('Y-m', ...): format tanpa hari
            // mengambil tanggal dari hari ini, sehingga '2025-09' pada tanggal 31
            // menjadi 31 September lalu meluap ke Oktober — jebakan yang sama persis
            // dengan yang sedang diuji di sini, dan versi pertama tes ini kena.
            $this->assertSame(
                Carbon::createFromFormat('Y-m-d', $bulan['key'].'-01')->translatedFormat('M Y'),
                $bulan['label'],
            );
        }
    }
}
