<?php

// File: app/Traits/Multitenantable.php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Multitenantable
{
    public static function bootMultitenantable(): void
    {
        // Global Scope: auto-inject WHERE pesantren_id pada Select/Update/Delete
        static::addGlobalScope('pesantren', function (Builder $query) {
            if (auth()->check() && auth()->user()->role !== 'super_admin') {
                $query->where(
                    (new static)->getTable() . '.pesantren_id',
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
        });
    }

    // Helper: bypass Global Scope untuk query lintas tenant (super_admin only)
    public static function allTenants(): Builder
    {
        return static::withoutGlobalScope('pesantren');
    }
}
