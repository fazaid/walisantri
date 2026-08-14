<?php

// File: app/Traits/Multitenantable.php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

trait Multitenantable
{
    public static function bootMultitenantable(): void
    {
        // Global Scope: auto-inject WHERE pesantren_id pada Select/Update/Delete
        static::addGlobalScope('pesantren', function (Builder $query) {
            if (auth()->check() && auth()->user()->role !== 'super_admin') {
                $query->where(
                    (new static)->getTable().'.pesantren_id',
                    auth()->user()->pesantren_id
                );
            }
        });

        // Auto-assign pesantren_id saat creating
        static::creating(function ($model) {
            if (auth()->check()
                && auth()->user()->role !== 'super_admin'
                && empty($model->pesantren_id)
            ) {
                $model->pesantren_id = auth()->user()->pesantren_id;
            }

            // super_admin tidak terikat pesantren manapun. Bila ia mencoba membuat
            // data tenant tanpa pesantren_id eksplisit, insert akan melanggar NOT NULL
            // (SQLSTATE 23502) dan tampil sebagai error mentah. Cegah dengan pesan
            // yang bisa dipahami — ValidationException ditampilkan rapi oleh Filament.
            //
            // Kecuali untuk model yang pesantren_id-nya memang nullable dan punya arti
            // "milik platform, berlaku lintas tenant" (lihat bolehTanpaPesantren()).
            if (auth()->check()
                && auth()->user()->role === 'super_admin'
                && empty($model->pesantren_id)
                && ! $model::bolehTanpaPesantren()
            ) {
                throw ValidationException::withMessages([
                    'pesantren_id' => 'Akun super admin tidak terkait dengan pesantren manapun, sehingga tidak bisa menambah data pesantren di sini. Silakan masuk sebagai admin pesantren yang bersangkutan.',
                ]);
            }
        });
    }

    /**
     * Bolehkah super admin menyimpan record ini tanpa pesantren_id (pesantren_id = NULL)?
     *
     * Default false: mayoritas model tenant punya kolom pesantren_id NOT NULL, sehingga
     * menyimpan tanpa tenant hanya akan berujung error SQL mentah.
     *
     * Model yang kolomnya nullable DAN memaknai NULL sebagai "milik platform" —
     * mis. MasterPengumuman untuk pengumuman global — meng-override ini jadi true.
     */
    public static function bolehTanpaPesantren(): bool
    {
        return false;
    }

    // Helper: bypass Global Scope untuk query lintas tenant (super_admin only)
    public static function allTenants(): Builder
    {
        return static::withoutGlobalScope('pesantren');
    }
}
