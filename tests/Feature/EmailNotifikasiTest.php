<?php

namespace Tests\Feature;

use App\Mail\ExpiringTenantWarning;
use App\Mail\InvoiceDibuat;
use App\Mail\PembayaranDiterima;
use App\Mail\SambutanPendaftaran;
use App\Models\EmailSetting;
use App\Models\Order;
use App\Models\Pesantren;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\InvoicePdf;
use App\Services\UpgradeOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\MenyemaiWilayah;
use Tests\TestCase;

/**
 * Empat serangkai per jenis email (§12.2), meniru pola tes notifikasi WhatsApp
 * di UpgradeOrderServiceTest: terkirim · penerima tanpa alamat · kill-switch
 * mati · pesantren tanpa admin.
 *
 * Penjagaan "penerima tanpa alamat" bukan kasus teoretis: `users.email` nullable
 * sejak central/2026_07_09_100001, dan sampai v4.22 kode memanggil Mail::to(null)
 * tanpa pemeriksaan apa pun.
 */
class EmailNotifikasiTest extends TestCase
{
    use MenyemaiWilayah, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // .env.testing menutup registrasi secara default; tes pendaftaran di repo
        // ini memang menyalakannya eksplisit (lihat RegisterControllerTest).
        PlatformSetting::set('registration_open', true);

        // Wajib: tabel wilayah kosong setelah migrate:fresh, dan /register menolak
        // kode desa yang tidak ada di sana.
        $this->semaiWilayahContoh();
    }

    private function makePesantren(array $override = []): Pesantren
    {
        return Pesantren::create(array_merge([
            'paket' => 'rintisan',
            'nama_pesantren' => 'Pesantren Email Test',
            'slug' => 'pesantren-email-'.uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addMonth(),
        ], $override));
    }

    private function makeAdmin(Pesantren $pesantren, bool $tanpaEmail = false): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'pesantren_id' => $pesantren->id,
            'name' => "Admin Email {$counter}",
            'email' => $tanpaEmail ? null : "admin.email.{$counter}@contoh.test",
            'password' => bcrypt('password'),
            'role' => 'admin_pesantren',
        ]);
    }

    private function makeSuperAdmin(): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'pesantren_id' => null,
            'name' => "Super Email {$counter}",
            'email' => "super.email.{$counter}@contoh.test",
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
    }

    private function buatOrder(Pesantren $pesantren): array
    {
        return app(UpgradeOrderService::class)->createOrder(
            pesantren: $pesantren,
            paketTarget: 'tumbuh',
            durasibulan: 12,
            maxSantriKuota: 250,
        );
    }

    // ---------------------------------------------------------------- sambutan

    public function test_email_sambutan_terkirim_saat_pesantren_mendaftar(): void
    {
        Mail::fake();

        $this->post(route('register.submit'), [
            'paket' => 'rintisan',
            'nama_pesantren' => 'Pesantren Sambutan',
            'slug' => 'pesantren-sambutan',
            'admin_name' => 'Admin Sambutan',
            'email' => 'admin.sambutan@contoh.test',
            'alamat_pesantren' => 'Jl. Raya Contoh No. 12, RT 03/RW 05',
            'admin_whatsapp' => '081234567890',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ] + $this->dataWilayahValid());

        Mail::assertQueued(
            SambutanPendaftaran::class,
            fn (SambutanPendaftaran $mail) => $mail->hasTo('admin.sambutan@contoh.test')
        );
    }

    public function test_email_sambutan_tidak_terkirim_saat_toggle_dimatikan(): void
    {
        Mail::fake();
        EmailSetting::set('email_sambutan_enabled', false);

        $this->post(route('register.submit'), [
            'paket' => 'rintisan',
            'nama_pesantren' => 'Pesantren Senyap',
            'slug' => 'pesantren-senyap',
            'admin_name' => 'Admin Senyap',
            'email' => 'admin.senyap@contoh.test',
            'alamat_pesantren' => 'Jl. Raya Contoh No. 12, RT 03/RW 05',
            'admin_whatsapp' => '081234567890',
            'password' => 'Rahasia123',
            'password_confirmation' => 'Rahasia123',
        ] + $this->dataWilayahValid());

        Mail::assertNotQueued(SambutanPendaftaran::class);
    }

    // ----------------------------------------------------------------- invoice

    public function test_email_invoice_terkirim_saat_order_dibuat(): void
    {
        Mail::fake();

        $pesantren = $this->makePesantren();
        $admin = $this->makeAdmin($pesantren);

        $this->buatOrder($pesantren);

        Mail::assertQueued(
            InvoiceDibuat::class,
            fn (InvoiceDibuat $mail) => $mail->hasTo($admin->email)
        );
    }

    public function test_email_invoice_tidak_terkirim_saat_admin_tanpa_email(): void
    {
        Mail::fake();

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren, tanpaEmail: true);

        $this->buatOrder($pesantren);

        Mail::assertNotQueued(InvoiceDibuat::class);
    }

    public function test_email_invoice_tidak_terkirim_saat_toggle_dimatikan(): void
    {
        Mail::fake();
        EmailSetting::set('email_invoice_enabled', false);

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren);

        $this->buatOrder($pesantren);

        Mail::assertNotQueued(InvoiceDibuat::class);
    }

    public function test_email_invoice_tidak_terkirim_saat_pesantren_tanpa_admin(): void
    {
        Mail::fake();

        $this->buatOrder($this->makePesantren());

        Mail::assertNotQueued(InvoiceDibuat::class);
    }

    // -------------------------------------------------------------- pembayaran

    public function test_email_pembayaran_terkirim_saat_order_dikonfirmasi(): void
    {
        Mail::fake();

        $pesantren = $this->makePesantren();
        $admin = $this->makeAdmin($pesantren);
        $order = $this->buatOrder($pesantren)['order'];

        app(UpgradeOrderService::class)->confirmOrder($order, $this->makeSuperAdmin());

        Mail::assertQueued(
            PembayaranDiterima::class,
            fn (PembayaranDiterima $mail) => $mail->hasTo($admin->email)
        );
    }

    public function test_email_pembayaran_tidak_terkirim_saat_toggle_dimatikan(): void
    {
        Mail::fake();

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren);
        $order = $this->buatOrder($pesantren)['order'];

        EmailSetting::set('email_pembayaran_enabled', false);

        app(UpgradeOrderService::class)->confirmOrder($order, $this->makeSuperAdmin());

        Mail::assertNotQueued(PembayaranDiterima::class);
    }

    public function test_email_pembayaran_tidak_terkirim_saat_admin_tanpa_email(): void
    {
        Mail::fake();

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren, tanpaEmail: true);
        $order = $this->buatOrder($pesantren)['order'];

        app(UpgradeOrderService::class)->confirmOrder($order, $this->makeSuperAdmin());

        Mail::assertNotQueued(PembayaranDiterima::class);
    }

    public function test_order_tetap_dibuat_walau_email_dimatikan(): void
    {
        Mail::fake();
        EmailSetting::set('email_invoice_enabled', false);

        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren);

        $hasil = $this->buatOrder($pesantren);

        // Pengiriman email adalah efek samping, bukan syarat: kegagalan atau
        // penonaktifannya tidak boleh menghalangi transaksi bisnisnya.
        $this->assertInstanceOf(Order::class, $hasil['order']);
        $this->assertDatabaseHas('orders', ['id' => $hasil['order']->id]);
    }

    // ----------------------------------------------------------- render & PDF

    /**
     * Mail::fake() mencatat pengiriman tanpa pernah merender view-nya, jadi galat
     * Blade di badan email tidak akan tertangkap tes mana pun di atas. Di sini
     * keempat email yang punya data dinamis benar-benar dirender.
     */
    public function test_semua_email_bisa_dirender(): void
    {
        $pesantren = $this->makePesantren();
        $admin = $this->makeAdmin($pesantren);
        $hasil = $this->buatOrder($pesantren);

        $email = [
            new SambutanPendaftaran($pesantren, $admin),
            new InvoiceDibuat($hasil['order'], $hasil['invoice']),
            new PembayaranDiterima($hasil['order'], now()->addYear()),
            new ExpiringTenantWarning($pesantren, 7),
        ];

        foreach ($email as $mailable) {
            $html = $mailable->render();

            $this->assertNotEmpty($mailable->envelope()->subject);
            $this->assertStringContainsString('Walisantri.com', $html);
        }
    }

    /**
     * Tautan verifikasi menumpang di email sambutan (§12.2). Kalau tombolnya
     * hilang, tidak ada satu pun pendaftar baru yang bisa mengonfirmasi
     * alamatnya — dan tidak ada tes lain yang akan menyadarinya, karena
     * Mail::fake() tidak pernah merender badan email.
     */
    public function test_email_sambutan_memuat_tautan_verifikasi(): void
    {
        $pesantren = $this->makePesantren();
        $admin = $this->makeAdmin($pesantren);

        $html = (new SambutanPendaftaran($pesantren, $admin))->render();

        $this->assertStringContainsString('/verifikasi-email/'.$admin->id.'/', $html);
        $this->assertStringContainsString('Konfirmasi Alamat Email', $html);
    }

    /**
     * Lampiran PDF dirakit lewat closure, jadi kegagalannya baru muncul saat
     * benar-benar dipanggil — bukan saat mailable dibuat.
     */
    public function test_lampiran_pdf_invoice_benar_benar_terbentuk(): void
    {
        $pesantren = $this->makePesantren();
        $this->makeAdmin($pesantren);
        $hasil = $this->buatOrder($pesantren);

        $lampiran = (new InvoiceDibuat($hasil['order'], $hasil['invoice']))->attachments();

        $this->assertCount(1, $lampiran);
        $this->assertSame("Invoice-{$hasil['invoice']->nomor_invoice}.pdf", $lampiran[0]->as);
        $this->assertSame('application/pdf', $lampiran[0]->mime);

        // Isi lampirannya dirakit InvoicePdf — diuji lewat service-nya langsung
        // karena closure di dalam Attachment tidak terjangkau dari luar.
        $isi = app(InvoicePdf::class)->untuk($hasil['order'], $hasil['invoice'])->output();

        $this->assertStringStartsWith('%PDF', $isi);
    }
}
