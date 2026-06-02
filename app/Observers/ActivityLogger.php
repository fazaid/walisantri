<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityLogger
{
    public function __construct(private readonly Request $request) {}

    public static function log(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): void {
        $user      = auth()->user();
        $pesantren = $user?->pesantren_id;

        ActivityLog::create([
            'pesantren_id'   => $pesantren,
            'user_id'        => $user?->id,
            'event'          => $event,
            'auditable_type' => get_class($model),
            'auditable_id'   => $model->getKey(),
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'ip_address'     => request()?->ip(),
            'user_agent'     => request()?->userAgent(),
            'created_at'     => now(),
        ]);
    }
}
