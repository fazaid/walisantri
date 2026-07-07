<?php

namespace Tests\Feature;

use App\Filament\Pages\RegistrationSettingsPage;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_bisa_akses_halaman(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(RegistrationSettingsPage::getUrl())
            ->assertOk();
    }

    public function test_admin_pesantren_tidak_bisa_akses_halaman(): void
    {
        $admin = User::factory()->adminPesantren()->create();

        $this->actingAs($admin)
            ->get(RegistrationSettingsPage::getUrl())
            ->assertForbidden();
    }

    public function test_toggle_off_tersimpan_ke_database(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(RegistrationSettingsPage::class)
            ->fillForm(['registration_open' => false])
            ->call('save');

        $this->assertFalse(PlatformSetting::get('registration_open'));
    }

    public function test_toggle_on_tersimpan_ke_database(): void
    {
        PlatformSetting::set('registration_open', false);

        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin)
            ->test(RegistrationSettingsPage::class)
            ->fillForm(['registration_open' => true])
            ->call('save');

        $this->assertTrue(PlatformSetting::get('registration_open'));
    }
}
