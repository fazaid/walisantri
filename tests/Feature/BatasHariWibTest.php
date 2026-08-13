<?php

namespace Tests\Feature;

use App\Filament\Pages\MutabaahHarianPage;
use App\Jobs\WarnExpiringTenants;
use App\Mail\ExpiringTenantWarning;
use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use App\Models\Order;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use App\Services\UpgradeOrderService;
use App\Support\Waktu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Waktu disimpan UTC, tapi batas HARI mengikuti kalender WIB. Tes di sini
 * membekukan waktu ke 05.00 WIB — jam subuh, ketika now() mentah masih
 * menunjuk tanggal kemarin — karena persis di situlah bug-nya muncul.
 */
class BatasHariWibTest extends TestCase
{
    use RefreshDatabase;

    private Pesantren $pesantren;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Mail::fake();

        $this->travelTo(Carbon::parse('2026-07-28 05:00:00', 'Asia/Jakarta')->utc());

        $this->pesantren = $this->makePesantren();
    }

    private function makePesantren(array $override = []): Pesantren
    {
        return Pesantren::create(array_merge([
            'nama_pesantren' => 'Pesantren Batas Hari',
            'slug' => 'pesantren-batas-hari-'.uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addMonth(),
        ], $override));
    }

    public function test_prakondisi_now_mentah_memang_masih_tanggal_kemarin(): void
    {
        // Kalau asumsi ini gugur, seluruh tes di bawah kehilangan maknanya.
        $this->assertSame('2026-07-27', now()->toDateString());
        $this->assertSame('2026-07-28', Waktu::hariIni());
    }

    private function makeUstadz(): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'pesantren_id' => $this->pesantren->id,
            'name' => "Ustadz Subuh {$counter}",
            'email' => "ustadz.subuh.{$counter}@wa.test",
            'password' => bcrypt('password'),
            'role' => 'ustadz',
        ]);
    }

    private function makeAmalMaster(): KesantrianAmalMaster
    {
        return KesantrianAmalMaster::create([
            'pesantren_id' => $this->pesantren->id,
            'kode' => 'subuh_berjamaah',
            'label' => 'Sholat Subuh Berjamaah',
            'tipe' => 'boolean',
            'aktif' => true,
        ]);
    }

    /** Halaman mutaba'ah hanya menampilkan santri bimbingan ustadz yang login. */
    private function makeSantri(User $pembimbing): Santri
    {
        return Santri::create([
            'pesantren_id' => $this->pesantren->id,
            'nama_lengkap' => 'Santri Subuh',
            'nis' => 'NIS-'.uniqid(),
            'jenis_kelamin' => 'laki_laki',
            'status_aktif' => true,
            'pembimbing_ustadz_id' => $pembimbing->id,
        ]);
    }

    public function test_mutabaah_subuh_memakai_tanggal_hari_ini_wib(): void
    {
        $ustadz = $this->makeUstadz();
        $this->makeAmalMaster();

        $tanggal = Livewire::actingAs($ustadz)
            ->test(MutabaahHarianPage::class)
            ->get('tanggal');

        // Dibandingkan bagian tanggalnya saja: dengan Carbon::setTestNow aktif,
        // Carbon::parse() mengisi komponen jam dari waktu beku — artefak tes,
        // bukan perilaku produksi.
        $this->assertStringStartsWith('2026-07-28', $tanggal);
    }

    public function test_mutabaah_hari_ini_lolos_validasi_maxdate(): void
    {
        // State DatePicker selalu membawa komponen jam — PHP mengisi bagian
        // yang hilang dengan jam saat ini ketika mem-parse 'Y-m-d'. Kalau
        // maxDate hanya berisi tanggal (00.00), tanggal hari ini justru ditolak
        // dengan "harus sebelum atau sama dengan <hari ini>".
        $ustadz = $this->makeUstadz();
        $this->makeAmalMaster();
        $this->makeSantri($ustadz);

        Livewire::actingAs($ustadz)
            ->test(MutabaahHarianPage::class)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('2026-07-28', KesantrianMutabaah::sole()->tanggal->toDateString());
    }

    public function test_mutabaah_tanggal_besok_tetap_ditolak(): void
    {
        $ustadz = $this->makeUstadz();
        $this->makeAmalMaster();
        $this->makeSantri($ustadz);

        Livewire::actingAs($ustadz)
            ->test(MutabaahHarianPage::class)
            ->set('tanggal', '2026-07-29')
            ->call('save')
            ->assertHasErrors('tanggal');
    }

    public function test_nomor_order_dan_invoice_memakai_tanggal_wib(): void
    {
        $hasil = app(UpgradeOrderService::class)
            ->createOrder($this->pesantren, 'berkembang', 12, 500);

        $this->assertStringContainsString('20260728', $hasil['order']->nomor_order);
        $this->assertStringNotContainsString('20260727', $hasil['order']->nomor_order);
        $this->assertStringContainsString('20260728', $hasil['invoice']->nomor_invoice);
    }

    public function test_urutan_nomor_harian_dihitung_per_hari_wib(): void
    {
        // Order ini dibuat 22.00 WIB kemarin — hari WIB yang berbeda, tapi
        // tanggal UTC-nya sama dengan "hari ini" versi UTC. Kalau counter masih
        // memakai whereDate UTC, order ini ikut terhitung dan urutannya meleset.
        $kemarin = Order::create([
            'pesantren_id' => $this->pesantren->id,
            'nomor_order' => 'WS-20260727-0001',
            'paket_target' => 'berkembang',
            'durasi_bulan' => 12,
            'max_santri_kuota_target' => 500,
            'harga_per_bulan' => 100000,
            'harga_total_sebelum_diskon' => 1200000,
            'harga_total' => 1200000,
            'durasi_total_bulan' => 12,
            'status' => 'pending_payment',
        ]);
        $kemarin->created_at = Carbon::parse('2026-07-27 22:00:00', 'Asia/Jakarta')->utc();
        $kemarin->save();

        $hasil = app(UpgradeOrderService::class)
            ->createOrder($this->pesantren, 'berkembang', 12, 500);

        $this->assertSame('WS-20260728-0001', $hasil['order']->nomor_order);
    }

    public function test_peringatan_expired_memakai_batas_hari_wib(): void
    {
        // Expired 31 Juli 20.00 WIB = 31 Juli 13.00 UTC. Menurut kalender WIB
        // itu tepat H-3 dari hari ini (28 Juli). Jendela versi UTC menutup
        // bucket H-3 di 30 Juli 23.59 UTC, sehingga pesantren ini terlewat.
        $pesantren = $this->makePesantren([
            'expired_at' => Carbon::parse('2026-07-31 20:00:00', 'Asia/Jakarta')->utc(),
        ]);

        User::create([
            'pesantren_id' => $pesantren->id,
            'name' => 'Admin H-3',
            'email' => 'admin.h3@wa.test',
            'password' => bcrypt('password'),
            'role' => 'admin_pesantren',
        ]);

        (new WarnExpiringTenants)->handle();

        Mail::assertQueued(
            ExpiringTenantWarning::class,
            fn ($mail): bool => $mail->hasTo('admin.h3@wa.test'),
        );
    }

    public function test_sla_konfirmasi_menghitung_akhir_pekan_menurut_wib(): void
    {
        // Sabtu 03.00 WIB masih Jumat 20.00 UTC — kalau akhir pekan dievaluasi
        // di UTC, hari itu salah dihitung sebagai hari kerja.
        $this->travelTo(Carbon::parse('2026-08-01 03:00:00', 'Asia/Jakarta')->utc());

        $this->assertTrue(Waktu::sekarang()->isWeekend(), 'Prakondisi: 1 Agustus 2026 memang Sabtu.');
        $this->assertFalse(now()->isWeekend(), 'Prakondisi: di UTC jam itu masih Jumat.');

        $cutoff = Order::slaCutoff();

        $this->assertSame('UTC', $cutoff->timezone->getName(), 'Hasil harus UTC agar aman dipakai di query.');
        $this->assertSame('2026-07-31', $cutoff->copy()->timezone('Asia/Jakarta')->toDateString());
    }
}
