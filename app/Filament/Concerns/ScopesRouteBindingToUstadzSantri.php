<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Applies the same ustadz->santri scope as ScopesQueryToUstadzSantri to direct
 * record route-model binding, so an ustadz can't reach another ustadz's record
 * by guessing its URL.
 */
trait ScopesRouteBindingToUstadzSantri
{
    use ScopesQueryToUstadzSantri;

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        if (Auth::user()?->role === UserRole::Ustadz->value) {
            $query->whereIn(static::ustadzScopeColumn(), static::ustadzScopedIds());
        }

        return $query;
    }
}
