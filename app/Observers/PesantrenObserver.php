<?php

namespace App\Observers;

use App\Enums\OnboardingStep;
use App\Models\Pesantren;
use App\Models\SlugRelease;
use App\Models\TenantDomain;

class PesantrenObserver
{
    public function updated(Pesantren $pesantren): void
    {
        if ($pesantren->wasChanged('status_berlangganan')) {
            $old = $pesantren->getOriginal('status_berlangganan');
            $new = $pesantren->status_berlangganan;

            if ($new === 'suspended') {
                ActivityLogger::log('pesantren.suspended', $pesantren,
                    ['status' => $old], ['status' => $new],
                );
            } elseif ($new === 'active' && in_array($old, ['suspended', 'expired'], true)) {
                // Dulu hanya suspended -> active yang tercatat, padahal tombol "Aktifkan"
                // di dashboard super admin juga menyasar tenant expired.
                ActivityLogger::log('pesantren.activated', $pesantren,
                    ['status' => $old, 'expired_at' => $pesantren->getOriginal('expired_at')?->toDateTimeString()],
                    ['status' => $new, 'expired_at' => $pesantren->expired_at?->toDateTimeString()],
                );
            }
        }

        if ($pesantren->wasChanged('paket_langganan')) {
            ActivityLogger::log('pesantren.paket_changed', $pesantren,
                ['paket' => $pesantren->getOriginal('paket_langganan')],
                ['paket' => $pesantren->paket_langganan],
            );
        }

        if ($pesantren->wasChanged('slug')) {
            $oldSlug = $pesantren->getOriginal('slug');

            // Slug lama masuk cooldown 90 hari (§1.4)
            SlugRelease::updateOrCreate(
                ['slug' => $oldSlug],
                ['released_at' => now()],
            );

            // Update hostname di tenant_domains
            TenantDomain::where('pesantren_id', $pesantren->id)
                ->where('type', 'subdomain')
                ->where('is_primary', true)
                ->update(['hostname' => "{$pesantren->slug}.".config('app.base_domain', 'walisantri.com')]);

            ActivityLogger::log('pesantren.slug_changed', $pesantren,
                ['slug' => $oldSlug],
                ['slug' => $pesantren->slug],
            );
        }

        if ($pesantren->wasChanged('profil')) {
            $profil = $pesantren->profil ?? [];

            if (! empty($profil['alamat']) && ! empty($profil['logo'])) {
                $pesantren->completeOnboardingStep(OnboardingStep::Profil);
            }
        }
    }

    // santri_count_cache: di-update setelah santri created/deleted (via SantriObserver opsional)
    // Di sini update cache jika dibutuhkan langsung
}
