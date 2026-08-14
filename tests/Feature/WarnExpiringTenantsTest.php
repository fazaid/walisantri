<?php

namespace Tests\Feature;

use App\Jobs\WarnExpiringTenants;
use App\Mail\ExpiringTenantWarning;
use App\Models\EmailSetting;
use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Job ini sudah ada sejak lama tapi tidak pernah punya tes sendiri — dan tiga
 * cacatnya (tanpa kill-switch, tanpa penjagaan email null, tanpa anti-duplikasi)
 * bertahan begitu lama justru karena `MAIL_MAILER=log` menelan semua akibatnya.
 */
class WarnExpiringTenantsTest extends TestCase
{
    use RefreshDatabase;

    private function makePesantren(int $hariLagi): Pesantren
    {
        return Pesantren::create([
            'nama_pesantren' => 'Pesantren Peringatan',
            'slug' => 'pesantren-warn-'.uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'trial',
            'expired_at' => now()->addDays($hariLagi),
        ]);
    }

    private function makeAdmin(Pesantren $pesantren, bool $tanpaEmail = false): User
    {
        static $counter = 0;
        $counter++;

        return User::create([
            'pesantren_id' => $pesantren->id,
            'name' => "Admin Warn {$counter}",
            'email' => $tanpaEmail ? null : "admin.warn.{$counter}@contoh.test",
            'password' => bcrypt('password'),
            'role' => 'admin_pesantren',
        ]);
    }

    public function test_mengirim_peringatan_h7(): void
    {
        Mail::fake();
        $pesantren = $this->makePesantren(7);
        $admin = $this->makeAdmin($pesantren);

        (new WarnExpiringTenants)->handle();

        Mail::assertQueued(
            ExpiringTenantWarning::class,
            fn (ExpiringTenantWarning $mail) => $mail->hasTo($admin->email) && $mail->daysLeft === 7
        );
    }

    public function test_tidak_mengirim_saat_kill_switch_dimatikan(): void
    {
        Mail::fake();
        EmailSetting::set('email_reminder_expired_enabled', false);
        $this->makeAdmin($this->makePesantren(7));

        (new WarnExpiringTenants)->handle();

        Mail::assertNothingQueued();
    }

    /**
     * Regresi: sebelum v4.23 job ini memanggil Mail::to(null) tanpa pemeriksaan
     * apa pun, padahal users.email nullable sejak central/2026_07_09_100001.
     */
    public function test_melewati_admin_tanpa_alamat_email(): void
    {
        Mail::fake();
        $this->makeAdmin($this->makePesantren(7), tanpaEmail: true);

        (new WarnExpiringTenants)->handle();

        Mail::assertNothingQueued();
    }

    public function test_melewati_pesantren_tanpa_admin(): void
    {
        Mail::fake();
        $this->makePesantren(7);

        (new WarnExpiringTenants)->handle();

        Mail::assertNothingQueued();
    }

    /**
     * withoutOverlapping() hanya mencegah dua eksekusi yang bertumpang tindih,
     * bukan dua eksekusi berurutan — schedule:run yang terpicu ulang saat deploy
     * dulu berarti admin menerima email kedua yang identik.
     */
    public function test_tidak_mengirim_dua_kali_di_hari_yang_sama(): void
    {
        Mail::fake();
        $this->makeAdmin($this->makePesantren(3));

        (new WarnExpiringTenants)->handle();
        (new WarnExpiringTenants)->handle();

        Mail::assertQueuedCount(1);
    }

    public function test_pesantren_yang_belum_mendekati_jatuh_tempo_dilewati(): void
    {
        Mail::fake();
        $this->makeAdmin($this->makePesantren(20));

        (new WarnExpiringTenants)->handle();

        Mail::assertNothingQueued();
    }
}
