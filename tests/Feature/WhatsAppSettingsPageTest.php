<?php

namespace Tests\Feature;

use App\Filament\Pages\WhatsAppSettingsPage;
use App\Models\PlatformContactSetting;
use App\Models\User;
use App\Models\WhatsAppGatewaySetting;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_bisa_akses_halaman(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(WhatsAppSettingsPage::getUrl())
            ->assertOk();
    }

    public function test_admin_pesantren_tidak_bisa_akses_halaman(): void
    {
        $admin = User::factory()->adminPesantren()->create();

        $this->actingAs($admin)
            ->get(WhatsAppSettingsPage::getUrl())
            ->assertForbidden();
    }

    public function test_toggle_off_tersimpan_ke_database(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(WhatsAppSettingsPage::class)
            ->fillForm(['reminder_expired_enabled' => false])
            ->call('save');

        $this->assertFalse(WhatsAppSetting::get('reminder_expired_enabled'));
    }

    public function test_template_kustom_tersimpan_ke_database(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(WhatsAppSettingsPage::class)
            ->fillForm(['reminder_expired_template' => 'Template kustom {nama_pesantren}'])
            ->call('save');

        $this->assertSame('Template kustom {nama_pesantren}', WhatsAppMessageTemplate::get('reminder_expired'));
    }

    public function test_template_kosong_ditolak_validasi(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(WhatsAppSettingsPage::class)
            ->fillForm(['reminder_expired_template' => ''])
            ->call('save')
            ->assertHasFormErrors(['reminder_expired_template' => 'required']);
    }

    public function test_token_fonnte_baru_tersimpan_terenkripsi(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(WhatsAppSettingsPage::class)
            ->fillForm(['fonnte_token' => 'rahasia-token-123'])
            ->call('save');

        $this->assertSame('rahasia-token-123', WhatsAppGatewaySetting::get('fonnte_token'));

        $raw = DB::table('whatsapp_gateway_settings')->where('key', 'fonnte_token')->value('value');
        $this->assertNotSame('rahasia-token-123', $raw);
    }

    public function test_token_fonnte_kosong_tidak_menimpa_token_lama(): void
    {
        WhatsAppGatewaySetting::set('fonnte_token', 'token-lama-456');

        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(WhatsAppSettingsPage::class)
            ->fillForm(['fonnte_token' => ''])
            ->call('save');

        $this->assertSame('token-lama-456', WhatsAppGatewaySetting::get('fonnte_token'));
    }

    public function test_nomor_admin_platform_tersimpan_ternormalisasi(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(WhatsAppSettingsPage::class)
            ->fillForm(['admin_whatsapp' => '081399096658'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('6281399096658', PlatformContactSetting::adminWhatsapp());
    }

    public function test_nomor_admin_platform_tidak_valid_ditolak(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(WhatsAppSettingsPage::class)
            ->fillForm(['admin_whatsapp' => '123'])
            ->call('save')
            ->assertHasFormErrors(['admin_whatsapp']);
    }

    public function test_nomor_admin_platform_boleh_dikosongkan(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        PlatformContactSetting::set('admin_whatsapp', '6281399096658');

        Livewire::actingAs($superAdmin)
            ->test(WhatsAppSettingsPage::class)
            ->fillForm(['admin_whatsapp' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(PlatformContactSetting::adminWhatsapp());
    }

    public function test_kill_switch_alert_admin_default_mati_dan_bisa_dinyalakan(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->assertFalse(WhatsAppSetting::get('notif_admin_platform_enabled', false));

        Livewire::actingAs($superAdmin)
            ->test(WhatsAppSettingsPage::class)
            ->fillForm(['notif_admin_platform_enabled' => true])
            ->call('save');

        $this->assertTrue(WhatsAppSetting::get('notif_admin_platform_enabled', false));
    }

    public function test_template_alert_admin_tersimpan(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(WhatsAppSettingsPage::class)
            ->fillForm(['notif_admin_order_bukti_template' => 'Bukti masuk {nomor_invoice}'])
            ->call('save');

        $this->assertSame('Bukti masuk {nomor_invoice}', WhatsAppMessageTemplate::get('notif_admin_order_bukti'));
    }
}
