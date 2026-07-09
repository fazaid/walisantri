<?php

namespace App\Models;

use App\Models\Concerns\BelongsToPesantren;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('tenant_domains')]
#[Fillable(['pesantren_id', 'hostname', 'type', 'is_primary', 'verified_at', 'ssl_status'])]
class TenantDomain extends Model
{
    use BelongsToPesantren;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }
}
