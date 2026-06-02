<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('activity_logs')]
class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'pesantren_id',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
