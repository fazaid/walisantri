<?php

namespace Tests\Feature;

use App\Models\Pesantren;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdminUser(): array
    {
        $pesantren = Pesantren::factory()->create();
        $admin = User::factory()->create([
            'role' => 'admin_pesantren',
            'pesantren_id' => $pesantren->id,
        ]);
        return [$pesantren, $admin];
    }

    public function test_santri_export_returns_xlsx(): void
    {
        [, $admin] = $this->makeAdminUser();

        $response = $this->actingAs($admin)
            ->withoutMiddleware(\App\Http\Middleware\ResolveTenantFromAccount::class)
            ->get('/admin-export/santri');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_mutabaah_export_returns_xlsx(): void
    {
        [, $admin] = $this->makeAdminUser();

        $response = $this->actingAs($admin)
            ->withoutMiddleware(\App\Http\Middleware\ResolveTenantFromAccount::class)
            ->get('/admin-export/mutabaah?bulan=6&tahun=2026');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_rekam_medis_export_returns_xlsx(): void
    {
        [, $admin] = $this->makeAdminUser();

        $response = $this->actingAs($admin)
            ->withoutMiddleware(\App\Http\Middleware\ResolveTenantFromAccount::class)
            ->get('/admin-export/rekam-medis');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_forbidden_for_wali(): void
    {
        $pesantren = Pesantren::factory()->create();
        $wali = User::factory()->create([
            'role' => 'wali_santri',
            'pesantren_id' => $pesantren->id,
        ]);

        $response = $this->actingAs($wali)
            ->withoutMiddleware(\App\Http\Middleware\ResolveTenantFromAccount::class)
            ->get('/admin-export/santri');

        $response->assertStatus(403);
    }
}
