<?php

namespace App\Models\Concerns;

use App\Models\Pesantren;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToPesantren
{
    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }
}
