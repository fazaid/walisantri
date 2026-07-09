<?php

namespace App\Models\Concerns;

use App\Models\Santri;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSantri
{
    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }
}
