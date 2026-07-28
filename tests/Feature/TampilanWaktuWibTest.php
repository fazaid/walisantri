<?php

namespace Tests\Feature;

use App\Filament\Resources\DemoRequests\Pages\ListDemoRequests;
use App\Models\DemoRequest;
use App\Models\User;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Waktu disimpan UTC tapi ditampilkan WIB. Tes ini menjaga agar tidak ada lagi
 * jam yang tampil mundur 7 jam ke pengguna.
 */
class TampilanWaktuWibTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // DemoRequestObserver::created() men-dispatch WA; tes ini bukan soal itu.
        Queue::fake();
    }

    public function test_penyimpanan_tetap_utc(): void
    {
        // Storage UTC adalah fondasi seluruh perbaikan ini — kalau ini berubah,
        // data lama yang sudah tersimpan sebagai UTC ikut salah arti.
        $this->assertSame('UTC', config('app.timezone'));
    }

    public function test_filament_menampilkan_waktu_dalam_wib(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.display_timezone'));
        $this->assertSame('Asia/Jakarta', FilamentTimezone::get());
    }

    public function test_antrean_demo_tampil_jam_wib_bukan_utc(): void
    {
        // Diisi 08:00 WIB → tersimpan 01:00 UTC. Sebelum perbaikan, superadmin
        // melihat "01:00" alias jam 1 malam.
        $record = new DemoRequest([
            'nama_pesantren' => 'Pesantren Uji WIB',
            'nama_kontak' => 'PIC Uji',
            'email' => 'wib@example.com',
            'no_hp' => '08123456789',
        ]);
        $record->created_at = SupportCarbon::parse('2026-07-28 01:00:00', 'UTC');
        $record->save();

        $this->assertSame(
            '2026-07-28 01:00:00',
            $record->fresh()->created_at->utc()->format('Y-m-d H:i:s'),
            'Penyimpanan harus tetap UTC.',
        );

        Livewire::actingAs(User::factory()->superAdmin()->create())
            ->test(ListDemoRequests::class)
            ->assertSee('28 Jul 2026, 08:00')
            ->assertDontSee('28 Jul 2026, 01:00');
    }

    public function test_scheduled_task_harian_berjalan_pada_jam_wib(): void
    {
        $harian = collect(app(Schedule::class)->events())
            ->filter(fn ($event): bool => ! str_starts_with($event->expression, '*/'));

        $this->assertNotEmpty($harian, 'Tidak ada scheduled task harian yang ditemukan.');

        foreach ($harian as $event) {
            $this->assertSame(
                'Asia/Jakarta',
                $event->timezone,
                "Task {$event->getSummaryForDisplay()} masih memakai jam UTC.",
            );
        }
    }
}
