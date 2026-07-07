<?php

namespace Tests\Feature;

use App\Filament\Pages\WhatsAppSettingsPage;
use App\Models\User;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
