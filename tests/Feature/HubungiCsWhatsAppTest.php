<?php

namespace Tests\Feature;

use App\Enums\StatusOrder;
use App\Filament\Pages\OrderInvoicePage;
use App\Filament\Pages\WhatsAppSettingsPage;
use App\Models\Order;
use App\Models\Pesantren;
use App\Models\PlatformContactSetting;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Services\UpgradeOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HubungiCsWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private function adminPesantren(): User
    {
        static $n = 0;
        $n++;

        $pesantren = Pesantren::create([
            'nama_pesantren' => "Pesantren CS {$n}",
            'slug' => "pesantren-cs-{$n}-".uniqid(),
            'paket_langganan' => 'rintisan',
            'max_santri_kuota' => 100,
            'status_berlangganan' => 'active',
            'expired_at' => now()->addDays(30),
        ]);

        return User::create([
            'pesantren_id' => $pesantren->id,
            'name' => "Admin CS {$n}",
            'email' => "admin.cs.{$n}@walisantri.test",
            'password' => bcrypt('password'),
            'role' => 'admin_pesantren',
        ]);
    }

    private function superAdmin(): User
    {
        static $n = 0;
        $n++;

        return User::create([
            'pesantren_id' => null,
            'name' => "Super Admin {$n}",
            'email' => "super.{$n}@walisantri.test",
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
    }

    private function buatOrder(User $admin): Order
    {
        return app(UpgradeOrderService::class)->createOrder(
            pesantren: $admin->pesantren,
            paketTarget: 'berkembang',
            durasibulan: 12,
            maxSantriKuota: 2000,
            kodeKupon: null,
        )['order'];
    }

    public function test_nomor_default_ikut_terpasang_dari_migrasi(): void
    {
        $this->assertSame('6281399096658', PlatformContactSetting::csWhatsapp());
    }

    public function test_super_admin_menyimpan_nomor_lokal_tersimpan_ternormalisasi(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(WhatsAppSettingsPage::class)
            ->set('cs_whatsapp', '0813-9909-6658')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('6281399096658', PlatformContactSetting::csWhatsapp());
    }

    public function test_nomor_tidak_valid_ditolak_validasi(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(WhatsAppSettingsPage::class)
            ->set('cs_whatsapp', 'bukan-nomor')
            ->call('save')
            ->assertHasErrors('cs_whatsapp');
    }

    public function test_nomor_boleh_dikosongkan(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(WhatsAppSettingsPage::class)
            ->set('cs_whatsapp', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(PlatformContactSetting::csWhatsapp());
    }

    public function test_tombol_tampil_di_footer_form_saat_pending_payment(): void
    {
        $admin = $this->adminPesantren();
        $order = $this->buatOrder($admin);

        $this->assertTrue($order->isPendingPayment());

        $html = $this->actingAs($admin)
            ->get(OrderInvoicePage::getUrl(['order' => $order->id]))
            ->assertOk()->getContent();

        // Tepat satu tombol: yang di footer form, bukan blok fallback.
        $this->assertSame(1, substr_count($html, 'Hubungi CS'));
        $this->assertStringNotContainsString('Ada kendala dengan pembayaran ini?', $html);

        $this->assertStringContainsString('https://wa.me/6281399096658?text=', $html);
        $this->assertStringContainsString(rawurlencode($order->nomor_order), $html);

        // Tombol kirim bukti tetap ada, jadi keduanya benar-benar bersebelahan.
        $this->assertStringContainsString('Kirim Bukti Transfer', $html);
    }

    public function test_tombol_tampil_sebagai_blok_bantuan_di_status_lain(): void
    {
        foreach ([StatusOrder::AwaitingConfirmation, StatusOrder::Confirmed, StatusOrder::Rejected, StatusOrder::Expired] as $status) {
            $admin = $this->adminPesantren();
            $order = $this->buatOrder($admin);
            $order->update(['status' => $status]);

            $this->flushSession();
            $this->app['auth']->forgetGuards();

            $html = $this->actingAs($admin)
                ->get(OrderInvoicePage::getUrl(['order' => $order->id]))
                ->assertOk("gagal render untuk status {$status->value}")->getContent();

            $this->assertStringContainsString('Ada kendala dengan pembayaran ini?', $html, "blok bantuan hilang di {$status->value}");
            $this->assertSame(1, substr_count($html, 'Hubungi CS'), "tombol dobel/hilang di {$status->value}");
            $this->assertStringContainsString('https://wa.me/6281399096658?text=', $html);
            $this->assertStringNotContainsString('Kirim Bukti Transfer', $html);
        }
    }

    public function test_tombol_hilang_total_bila_nomor_belum_diatur(): void
    {
        PlatformContactSetting::set('cs_whatsapp', null);

        $admin = $this->adminPesantren();
        $order = $this->buatOrder($admin);

        // Pending payment: footer form tidak memunculkan tombol.
        $html = $this->actingAs($admin)
            ->get(OrderInvoicePage::getUrl(['order' => $order->id]))
            ->assertOk()->getContent();

        $this->assertStringNotContainsString('Hubungi CS', $html);
        $this->assertStringNotContainsString('wa.me', $html);

        // Status lain: blok fallback tetap ter-render tapi tanpa tombol.
        $order->update(['status' => StatusOrder::Rejected]);

        $this->flushSession();
        $this->app['auth']->forgetGuards();

        $html = $this->actingAs($admin)
            ->get(OrderInvoicePage::getUrl(['order' => $order->id]))
            ->assertOk()->getContent();

        $this->assertStringNotContainsString('Hubungi CS', $html);
        $this->assertStringNotContainsString('wa.me', $html);

        // Jangan sisakan ajakan tanpa tombol.
        $this->assertStringNotContainsString('Ada kendala dengan pembayaran ini?', $html);
    }

    public function test_template_pesan_default_ikut_terpasang_dari_migrasi(): void
    {
        $this->assertSame(
            OrderInvoicePage::DEFAULT_CS_BANTUAN_TEMPLATE,
            WhatsAppMessageTemplate::get('cs_invoice_bantuan'),
        );
    }

    public function test_template_pesan_bisa_diubah_dari_superadmin(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(WhatsAppSettingsPage::class)
            ->assertSet('cs_bantuan_template', OrderInvoicePage::DEFAULT_CS_BANTUAN_TEMPLATE)
            ->set('cs_bantuan_template', 'Assalamualaikum, {nama_pesantren} mau tanya invoice {nomor_invoice} sebesar {total} (status: {status_order}).')
            ->call('save')
            ->assertHasNoErrors();

        $this->flushSession();
        $this->app['auth']->forgetGuards();

        $admin = $this->adminPesantren();
        $order = $this->buatOrder($admin);

        $html = $this->actingAs($admin)
            ->get(OrderInvoicePage::getUrl(['order' => $order->id]))
            ->assertOk()->getContent();

        $pesan = "Assalamualaikum, {$admin->pesantren->nama_pesantren} mau tanya invoice "
            ."{$order->invoice->nomor_invoice} sebesar Rp ".number_format($order->harga_total, 0, ',', '.')
            .' (status: '.$order->status->label().').';

        $this->assertStringContainsString('https://wa.me/6281399096658?text='.rawurlencode($pesan), $html);

        // Semua placeholder tergantikan — jangan ada kurung kurawal tersisa.
        $this->assertStringNotContainsString(rawurlencode('{'), $html);
    }

    public function test_template_kosong_ditolak_validasi(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(WhatsAppSettingsPage::class)
            ->set('cs_bantuan_template', '')
            ->call('save')
            ->assertHasErrors('cs_bantuan_template');
    }
}
