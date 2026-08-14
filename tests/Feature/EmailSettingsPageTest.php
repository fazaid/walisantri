<?php

namespace Tests\Feature;

use App\Filament\Pages\EmailSettingsPage;
use App\Mail\EmailUji;
use App\Models\EmailGatewaySetting;
use App\Models\EmailSetting;
use App\Models\User;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class EmailSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_bisa_akses_halaman(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(EmailSettingsPage::getUrl())
            ->assertOk();
    }

    public function test_admin_pesantren_tidak_bisa_akses_halaman(): void
    {
        $this->actingAs(User::factory()->adminPesantren()->create())
            ->get(EmailSettingsPage::getUrl())
            ->assertForbidden();
    }

    public function test_toggle_off_tersimpan_ke_database(): void
    {
        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test(EmailSettingsPage::class)
            ->fillForm(['email_invoice_enabled' => false])
            ->call('save');

        $this->assertFalse(EmailSetting::get('email_invoice_enabled'));
    }

    public function test_kredensial_smtp_tersimpan(): void
    {
        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test(EmailSettingsPage::class)
            ->fillForm([
                'smtp_host' => 'smtp-relay.brevo.com',
                'smtp_port' => '587',
                'smtp_username' => 'login@brevo.test',
                'from_address' => 'noreply@walisantri.com',
                'from_name' => 'Walisantri.com',
            ])
            ->call('save');

        $this->assertSame('smtp-relay.brevo.com', EmailGatewaySetting::get('smtp_host'));
        $this->assertSame('587', EmailGatewaySetting::get('smtp_port'));
        $this->assertSame('noreply@walisantri.com', EmailGatewaySetting::get('from_address'));
    }

    public function test_kunci_smtp_tersimpan_terenkripsi(): void
    {
        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test(EmailSettingsPage::class)
            ->fillForm(['smtp_password' => 'kunci-rahasia-123'])
            ->call('save');

        $this->assertSame('kunci-rahasia-123', EmailGatewaySetting::get('smtp_password'));

        // Yang tersimpan di kolom harus ciphertext, bukan teks apa adanya.
        $mentah = DB::table('email_gateway_settings')->where('key', 'smtp_password')->value('value');
        $this->assertNotSame('kunci-rahasia-123', $mentah);
    }

    public function test_kunci_smtp_kosong_tidak_menimpa_yang_lama(): void
    {
        EmailGatewaySetting::set('smtp_password', 'kunci-lama');

        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test(EmailSettingsPage::class)
            ->fillForm(['smtp_host' => 'smtp-relay.brevo.com'])
            ->call('save');

        $this->assertSame('kunci-lama', EmailGatewaySetting::get('smtp_password'));
    }

    public function test_kirim_email_uji_ke_super_admin_yang_login(): void
    {
        Mail::fake();
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(EmailSettingsPage::class)
            ->call('kirimEmailUji');

        Mail::assertSent(EmailUji::class, fn (EmailUji $mail) => $mail->hasTo($superAdmin->email));
    }

    /**
     * Kredensial di database harus mengalahkan .env — itulah seluruh alasan
     * tabel ini ada. Tanpa penegasan ini, halaman pengaturannya bisa saja
     * menyimpan nilai yang tidak pernah dipakai siapa pun.
     */
    public function test_kredensial_database_menimpa_config_saat_boot(): void
    {
        EmailGatewaySetting::set('smtp_host', 'smtp-relay.brevo.com');
        EmailGatewaySetting::set('smtp_port', '587');
        EmailGatewaySetting::set('from_address', 'noreply@walisantri.com');

        EmailGatewaySetting::applyToConfig();

        $this->assertSame('smtp-relay.brevo.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('noreply@walisantri.com', config('mail.from.address'));
        $this->assertSame('smtp', config('mail.default'));
    }

    /**
     * Tabel kosong berarti "belum diatur", bukan "kosongkan konfigurasi" — inilah
     * yang membuat CI dan tes lokal tetap memakai mailer bawaan tanpa perlu
     * menyiapkan kredensial apa pun.
     */
    public function test_tabel_kosong_tidak_menimpa_config(): void
    {
        $sebelum = config('mail.default');

        EmailGatewaySetting::applyToConfig();

        $this->assertSame($sebelum, config('mail.default'));
    }

    /**
     * Nama pengirim tanpa host bukan konfigurasi yang bisa dipakai mengirim.
     * Memaksa mailer default ke smtp dalam keadaan itu justru mematikan email.
     */
    public function test_tanpa_host_mailer_default_tidak_diambil_alih(): void
    {
        EmailGatewaySetting::set('from_name', 'Walisantri.com');
        $sebelum = config('mail.default');

        EmailGatewaySetting::applyToConfig();

        $this->assertSame($sebelum, config('mail.default'));
        $this->assertSame('Walisantri.com', config('mail.from.name'));
    }

    /**
     * Domain pengirim terverifikasi di Brevo tapi tidak punya MX, jadi balasan ke
     * `from_address` lenyap tanpa jejak. Reply-To yang menyelamatkannya — dan
     * Laravel menempelkannya sendiri ke setiap email lewat
     * MailManager::setGlobalAddresses, jadi tidak ada Mailable yang perlu diubah.
     */
    public function test_reply_to_disuntikkan_ke_config_secara_global(): void
    {
        EmailGatewaySetting::set('reply_to_address', 'cs@contoh.test');
        EmailGatewaySetting::set('reply_to_name', 'Tim Dukungan');

        EmailGatewaySetting::applyToConfig();

        $this->assertSame('cs@contoh.test', config('mail.reply_to.address'));
        $this->assertSame('Tim Dukungan', config('mail.reply_to.name'));
    }

    /**
     * Sengaja lewat transport `array`, bukan Mail::fake(): alamat global
     * (from/reply_to) ditempelkan MailManager saat mailer di-resolve, dan
     * MailFake melewati jalur itu sama sekali — asersi terhadap Mailable palsu
     * akan lulus/gagal karena alasan yang tidak ada hubungannya dengan produksi.
     */
    public function test_reply_to_benar_benar_menempel_di_email_yang_dikirim(): void
    {
        EmailGatewaySetting::set('reply_to_address', 'cs@contoh.test');
        EmailGatewaySetting::set('reply_to_name', 'Tim Dukungan');
        EmailGatewaySetting::applyToConfig();

        // Mailer yang sudah terlanjur di-resolve memegang alamat global lama.
        Mail::forgetMailers();

        Mail::mailer('array')->to('penerima@contoh.test')->send(new EmailUji);

        $pesan = Mail::mailer('array')->getSymfonyTransport()->messages()[0]->getOriginalMessage();
        $replyTo = $pesan->getReplyTo()[0];

        $this->assertSame('cs@contoh.test', $replyTo->getAddress());
        $this->assertSame('Tim Dukungan', $replyTo->getName());
    }

    public function test_tanpa_reply_to_config_tidak_disentuh(): void
    {
        EmailGatewaySetting::set('from_address', 'noreply@contoh.test');
        $sebelum = config('mail.reply_to');

        EmailGatewaySetting::applyToConfig();

        $this->assertSame($sebelum, config('mail.reply_to'));
    }

    /**
     * Regresi 2026-08-14: super admin mengubah alamat pengirim, tombol "Kirim
     * Email Uji" langsung memakai nilai baru (sinkron, di dalam request), tapi
     * email sambutan tetap keluar dengan alamat LAMA tanpa Reply-To.
     *
     * Sebabnya AppServiceProvider::boot() hanya jalan sekali per proses. Worker
     * Supervisor hidup berhari-hari dan memegang config sejak ia dinyalakan —
     * di production waktu itu worker mulai 7 menit sebelum pengaturan diubah.
     *
     * Penyembuhnya Queue::before yang menyegarkan config sebelum tiap job. Tes ini
     * meniru worker basi: config sengaja diisi nilai lama, lalu event pemrosesan
     * job dipicu, dan config harus sudah ikut nilai terbaru.
     */
    public function test_config_email_disegarkan_sebelum_tiap_job_antrean(): void
    {
        EmailGatewaySetting::set('from_address', 'lama@contoh.test');
        EmailGatewaySetting::applyToConfig();

        // Super admin mengubah pengaturan SETELAH worker menyala.
        EmailGatewaySetting::set('from_address', 'baru@contoh.test');
        EmailGatewaySetting::set('reply_to_address', 'cs@contoh.test');

        // Worker yang naif akan tetap memakai nilai lama.
        $this->assertSame('lama@contoh.test', config('mail.from.address'));

        $this->jalankanHookSebelumJob();

        $this->assertSame('baru@contoh.test', config('mail.from.address'));
        $this->assertSame('cs@contoh.test', config('mail.reply_to.address'));
    }

    /** Memicu event yang sama seperti saat worker mengambil satu job. */
    private function jalankanHookSebelumJob(): void
    {
        app('events')->dispatch(new JobProcessing('database', Mockery::mock(Job::class)->shouldIgnoreMissing()));
    }
}
