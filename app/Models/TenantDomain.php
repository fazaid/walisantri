<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('tenant_domains')]
#[Fillable(['pesantren_id', 'hostname', 'type', 'is_primary', 'verified_at', 'ssl_status'])]
class TenantDomain extends Model
{
    protected function casts(): array
    {
        return [
            'is_primary'  => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }
}
