<?php

namespace App\Console\Commands;

use App\Enums\OnboardingStep;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\MasterPengumuman;
use App\Models\Pesantren;
use Illuminate\Console\Command;

class BackfillOnboardingSteps extends Command
{
    protected $signature = 'onboarding:backfill {--dry-run : Tampilkan perubahan tanpa mengeksekusi}';

    protected $description = 'Tandai step onboarding_completed_steps untuk tenant existing berdasarkan data yang sudah ada (dijalankan sekali setelah fitur checklist onboarding dirilis)';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $pesantrens = Pesantren::all();
        $rows       = [];

        foreach ($pesantrens as $pesantren) {
            $before = $pesantren->onboarding_completed_steps ?? [];
            $steps  = $this->detectCompletedSteps($pesantren);
            $baru   = array_values(array_diff($steps, $before));

            if (empty($baru)) {
                continue;
            }

            $rows[] = [
                $pesantren->id,
                $pesantren->nama_pesantren,
                implode(', ', $baru),
            ];

            if (! $isDryRun) {
                foreach ($baru as $stepValue) {
                    $pesantren->completeOnboardingStep(OnboardingStep::from($stepValue));
                }
            }
        }

        if (empty($rows)) {
            $this->info('Tidak ada tenant yang perlu di-backfill.');

            return self::SUCCESS;
        }

        $this->warn('Ditemukan ' . count($rows) . ' tenant dengan step onboarding yang perlu di-backfill:');
        $this->newLine();
        $this->table(['ID', 'Nama Pesantren', 'Step Baru'], $rows);

        if ($isDryRun) {
            $this->newLine();
            $this->warn('Mode dry-run: tidak ada perubahan yang disimpan.');
        } else {
            $this->newLine();
            $this->info('Selesai: ' . count($rows) . ' tenant berhasil di-backfill.');
        }

        return self::SUCCESS;
    }

    /** @return string[] */
    private function detectCompletedSteps(Pesantren $pesantren): array
    {
        $steps  = [];
        $profil = $pesantren->profil ?? [];

        if (! empty($profil['alamat']) && ! empty($profil['logo'])) {
            $steps[] = OnboardingStep::Profil->value;
        }

        if ($pesantren->users()->where('role', UserRole::Ustadz->value)->exists()) {
            $steps[] = OnboardingStep::Ustadz->value;
        }

        if ($pesantren->santri()->exists()) {
            $steps[] = OnboardingStep::Santri->value;
        }

        if (ActivityLog::where('pesantren_id', $pesantren->id)->where('event', 'magic_link.viewed')->exists()) {
            $steps[] = OnboardingStep::MagicLink->value;
        }

        if (MasterPengumuman::where('pesantren_id', $pesantren->id)->exists()) {
            $steps[] = OnboardingStep::Pengumuman->value;
        }

        return $steps;
    }
}
