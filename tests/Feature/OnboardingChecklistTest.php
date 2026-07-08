<?php

namespace Tests\Feature;

use App\Enums\OnboardingStep;
use App\Filament\Widgets\OnboardingChecklistWidget;
use App\Models\ActivityLog;
use App\Models\MasterPengumuman;
use App\Models\Pesantren;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OnboardingChecklistTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_onboarding_step_idempotent(): void
    {
        $pesantren = Pesantren::factory()->create();

        $pesantren->completeOnboardingStep(OnboardingStep::Santri);
        $pesantren->completeOnboardingStep(OnboardingStep::Santri);

        $this->assertSame(['santri'], $pesantren->fresh()->onboarding_completed_steps);
    }

    public function test_is_onboarding_complete_hanya_butuh_step_wajib(): void
    {
        $pesantren = Pesantren::factory()->create();

        $this->assertFalse($pesantren->isOnboardingComplete());

        $pesantren->completeOnboardingStep(OnboardingStep::Profil);
        $pesantren->completeOnboardingStep(OnboardingStep::Ustadz);
        $pesantren->completeOnboardingStep(OnboardingStep::Santri);
        $this->assertFalse($pesantren->fresh()->isOnboardingComplete());

        $pesantren->completeOnboardingStep(OnboardingStep::MagicLink);
        $this->assertTrue($pesantren->fresh()->isOnboardingComplete());

        // Pengumuman opsional — tidak wajib untuk status "complete", dan menambahkannya
        // tidak mengubah status yang sudah true.
        $pesantren->completeOnboardingStep(OnboardingStep::Pengumuman);
        $this->assertTrue($pesantren->fresh()->isOnboardingComplete());
    }

    public function test_update_profil_lengkap_menandai_step_profil(): void
    {
        $pesantren = Pesantren::factory()->create();

        $pesantren->update(['profil' => ['alamat' => 'Jl. Contoh No. 1', 'logo' => 'logos/x.png']]);

        $this->assertTrue($pesantren->fresh()->hasCompletedOnboardingStep(OnboardingStep::Profil));
    }

    public function test_update_profil_tidak_lengkap_tidak_menandai_step_profil(): void
    {
        $pesantren = Pesantren::factory()->create();

        // Cuma alamat, logo masih kosong — belum lengkap.
        $pesantren->update(['profil' => ['alamat' => 'Jl. Contoh No. 1']]);

        $this->assertFalse($pesantren->fresh()->hasCompletedOnboardingStep(OnboardingStep::Profil));
    }

    public function test_santri_pertama_menandai_step_santri(): void
    {
        $pesantren = Pesantren::factory()->create();

        Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        $this->assertTrue($pesantren->fresh()->hasCompletedOnboardingStep(OnboardingStep::Santri));
    }

    public function test_ustadz_pertama_menandai_step_ustadz(): void
    {
        $pesantren = Pesantren::factory()->create();

        User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);

        $this->assertTrue($pesantren->fresh()->hasCompletedOnboardingStep(OnboardingStep::Ustadz));
    }

    public function test_wali_santri_baru_tidak_menandai_step_ustadz(): void
    {
        $pesantren = Pesantren::factory()->create();

        User::factory()->waliSantri()->create(['pesantren_id' => $pesantren->id]);

        $this->assertFalse($pesantren->fresh()->hasCompletedOnboardingStep(OnboardingStep::Ustadz));
    }

    public function test_pengumuman_tenant_menandai_step_pengumuman(): void
    {
        $pesantren = Pesantren::factory()->create();

        MasterPengumuman::create([
            'pesantren_id'   => $pesantren->id,
            'judul_maklumat' => 'Pengumuman Perdana',
            'isi_maklumat'   => 'Selamat datang.',
        ]);

        $this->assertTrue($pesantren->fresh()->hasCompletedOnboardingStep(OnboardingStep::Pengumuman));
    }

    public function test_pengumuman_global_tidak_menandai_step_pengumuman_tenant_manapun(): void
    {
        $pesantren = Pesantren::factory()->create();

        MasterPengumuman::create([
            'pesantren_id'   => null,
            'judul_maklumat' => 'Pengumuman Platform',
            'isi_maklumat'   => 'Broadcast global.',
        ]);

        $this->assertFalse($pesantren->fresh()->hasCompletedOnboardingStep(OnboardingStep::Pengumuman));
    }

    public function test_widget_tampil_untuk_admin_pesantren_yang_belum_selesai_onboarding(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin     = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $this->actingAs($admin);

        $this->assertTrue(OnboardingChecklistWidget::canView());
    }

    public function test_widget_hilang_setelah_4_step_wajib_selesai_meski_pengumuman_belum(): void
    {
        $pesantren = Pesantren::factory()->create();
        $admin     = User::factory()->adminPesantren()->create(['pesantren_id' => $pesantren->id]);

        $pesantren->completeOnboardingStep(OnboardingStep::Profil);
        $pesantren->completeOnboardingStep(OnboardingStep::Ustadz);
        $pesantren->completeOnboardingStep(OnboardingStep::Santri);
        $pesantren->completeOnboardingStep(OnboardingStep::MagicLink);

        $this->actingAs($admin);

        $this->assertFalse(OnboardingChecklistWidget::canView());
    }

    public function test_widget_tidak_tampil_untuk_role_selain_admin_pesantren(): void
    {
        $pesantren = Pesantren::factory()->create();

        foreach (['ustadz', 'wali_santri'] as $role) {
            $user = User::factory()->create(['pesantren_id' => $pesantren->id, 'role' => $role]);
            $this->actingAs($user);
            $this->assertFalse(OnboardingChecklistWidget::canView(), "Role {$role} tidak boleh lihat widget onboarding");
        }

        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin);
        $this->assertFalse(OnboardingChecklistWidget::canView());
    }

    public function test_backfill_command_menandai_step_dari_data_existing(): void
    {
        $pesantren = Pesantren::factory()->create([
            'profil' => ['alamat' => 'Jl. Contoh No. 1', 'logo' => 'logos/x.png'],
        ]);

        User::factory()->ustadz()->create(['pesantren_id' => $pesantren->id]);
        $santri = Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        ActivityLog::create([
            'pesantren_id'   => $pesantren->id,
            'event'          => 'magic_link.viewed',
            'auditable_type' => Santri::class,
            'auditable_id'   => $santri->id,
            'created_at'     => now(),
        ]);

        // Create santri/ustadz di atas sudah men-trigger Observer secara live. Reset ke []
        // supaya benar-benar mensimulasikan tenant lama yang datanya sudah lengkap TAPI
        // step-nya belum pernah tercatat (kondisi sebelum fitur checklist ini di-deploy).
        $pesantren->refresh()->forceFill(['onboarding_completed_steps' => []])->saveQuietly();

        Artisan::call('onboarding:backfill');

        $pesantren->refresh();
        $this->assertTrue($pesantren->hasCompletedOnboardingStep(OnboardingStep::Profil));
        $this->assertTrue($pesantren->hasCompletedOnboardingStep(OnboardingStep::Ustadz));
        $this->assertTrue($pesantren->hasCompletedOnboardingStep(OnboardingStep::Santri));
        $this->assertTrue($pesantren->hasCompletedOnboardingStep(OnboardingStep::MagicLink));
        $this->assertTrue($pesantren->isOnboardingComplete());
    }

    public function test_backfill_command_dry_run_tidak_mengubah_data(): void
    {
        $pesantren = Pesantren::factory()->create();
        Santri::factory()->create(['pesantren_id' => $pesantren->id]);

        // Santri::create() di atas sudah men-trigger SantriObserver secara live (step 'santri'
        // langsung tercatat). Reset ke [] supaya skenario ini benar-benar mensimulasikan tenant
        // lama yang datanya sudah ada TAPI step-nya belum pernah tercatat (kondisi sebelum fitur
        // checklist ini di-deploy) — itulah yang mau divalidasi oleh flag --dry-run.
        $pesantren->refresh()->forceFill(['onboarding_completed_steps' => []])->saveQuietly();

        Artisan::call('onboarding:backfill', ['--dry-run' => true]);
        $this->assertSame([], $pesantren->fresh()->onboarding_completed_steps);

        Artisan::call('onboarding:backfill');
        $this->assertSame(['santri'], $pesantren->fresh()->onboarding_completed_steps);
    }
}
