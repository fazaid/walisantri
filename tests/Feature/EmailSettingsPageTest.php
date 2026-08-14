<?php

namespace Tests\Feature;

use App\Filament\Pages\EmailSettingsPage;
use App\Mail\EmailUji;
use App\Models\EmailGatewaySetting;
use App\Models\EmailSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
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
}
