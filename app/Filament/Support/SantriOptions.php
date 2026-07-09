<?php

namespace App\Filament\Support;

use App\Models\Santri;
use Illuminate\Support\Collection;

class SantriOptions
{
    /**
     * Santri aktif untuk dropdown, dibatasi ke bimbingan ustadz yang login.
     */
    public static function aktifUntukPengguna(): Collection
    {
        $query = Santri::where('status_aktif', true);

        if (auth()->user()?->role === 'ustadz') {
            $query->where('pembimbing_ustadz_id', auth()->id());
        }

        return $query->orderBy('nama_lengkap')->pluck('nama_lengkap', 'id');
    }
}
