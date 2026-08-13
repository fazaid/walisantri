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
            if (auth()->check()
                && auth()->user()->role === 'super_admin'
                && empty($model->pesantren_id)
            ) {
                throw ValidationException::withMessages([
                    'pesantren_id' => 'Akun super admin tidak terkait dengan pesantren manapun, sehingga tidak bisa menambah data pesantren di sini. Silakan masuk sebagai admin pesantren yang bersangkutan.',
                ]);
            }
        });
    }

    // Helper: bypass Global Scope untuk query lintas tenant (super_admin only)
    public static function allTenants(): Builder
    {
        return static::withoutGlobalScope('pesantren');
    }
}
