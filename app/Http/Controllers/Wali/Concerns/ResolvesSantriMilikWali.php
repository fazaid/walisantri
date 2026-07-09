<?php

namespace App\Http\Controllers\Wali\Concerns;

use App\Models\Santri;

trait ResolvesSantriMilikWali
{
    protected function santriMilikWali(int $santriId, array $with = ['kelas', 'kamar']): Santri
    {
        return auth()->user()->anakSantri()->with($with)->findOrFail($santriId);
    }

    protected function pastikanSantriMilikWali(int $santriId): void
    {
        $milikWali = auth()->user()->anakSantri()->pluck('id');
        abort_unless($milikWali->contains($santriId), 403);
    }
}
