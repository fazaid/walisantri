<?php

namespace App\Filament\Support;

use App\Models\MataPelajaran;
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

    /**
     * Santri aktif untuk halaman rapor.
     *
     * Cakupan ustadz sengaja lebih luas daripada aktifUntukPengguna(): rapor
     * menggabungkan modul akademik (relevan bagi ustadz mapel, yang terhubung
     * ke santri lewat kelas yang diajar) dengan modul tahfidz/mutabaah/karakter
     * (relevan bagi ustadz pembimbing). Kalau dibatasi salah satunya saja, ada
     * ustadz yang kehilangan akses yang selama ini dia punya.
     */
    public static function untukRapor(): array
    {
        $query = Santri::where('status_aktif', true);

        if (auth()->user()?->role === 'ustadz') {
            $kelasIds = MataPelajaran::where('ustadz_id', auth()->id())->pluck('kelas_id');

            $query->where(fn ($q) => $q
                ->where('pembimbing_ustadz_id', auth()->id())
                ->orWhereIn('kelas_id', $kelasIds));
        }

        return $query->orderBy('nama_lengkap')->pluck('nama_lengkap', 'id')->toArray();
    }
}
