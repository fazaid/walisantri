<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts a resource's Eloquent query to records belonging to the logged-in
 * ustadz's own santri. Override ustadzScopeColumn()/ustadzScopedIds() for
 * resources scoped through a different relation.
 */
trait ScopesQueryToUstadzSantri
{
    protected static function ustadzScopeColumn(): string
    {
        return 'santri_id';
    }

    protected static function ustadzScopedIds(): Collection
    {
        return Santri::where('pembimbing_ustadz_id', Auth::id())->pluck('id');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()?->role === UserRole::Ustadz->value) {
            $query->whereIn(static::ustadzScopeColumn(), static::ustadzScopedIds());
        }

        return $query;
    }
}
