<?php

namespace Tests\TenantIsolation;

use App\Enums\OnboardingStep;
use App\Enums\UserRole;
use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PRD §17: Test isolasi tenant wajib pakai PostgreSQL (bukan SQLite).
 * Jalankan via: php artisan test --configuration=phpunit.tenant.xml
 */
class OnboardingIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped(
                'OnboardingIsolationTest wajib pakai PostgreSQL. ' .
                'Jalankan dengan DB_CONNECTION=pgsql.'
            );
        }
    }

    public function test_step_onboarding_selesai_di_tenant_a_tidak_mempengaruhi_tenant_b(): void
    {
        $pesantrenA = Pesantren::factory()->create(['slug' => 'onboarding-a']);
        $pesantrenB = Pesantren::factory()->create(['slug' => 'onboarding-b']);

        // Tambah ustadz pertama untuk tenant A saja — trigger UserObserver::created().
        User::factory()->create([
            'pesantren_id' => $pesantrenA->id,
            'role'         => UserRole::Ustadz->value,
        ]);

        $this->assertTrue($pesantrenA->fresh()->hasCompletedOnboardingStep(OnboardingStep::Ustadz));
        $this->assertFalse($pesantrenB->fresh()->hasCompletedOnboardingStep(OnboardingStep::Ustadz));
        $this->assertSame([], $pesantrenB->fresh()->onboarding_completed_steps);
    }
}
