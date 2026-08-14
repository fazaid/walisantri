<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Pesantren;
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
        $user = auth()->user();

        ActivityLog::create([
            'pesantren_id' => self::pesantrenId($model, $user),
            'user_id' => $user?->id,
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Tenant mana yang terdampak — diturunkan dari MODEL lebih dulu, bukan dari pelaku.
     *
     * Super admin punya pesantren_id NULL, jadi mengambilnya dari pelaku membuat setiap
     * tindakannya (suspend pesantren, ubah paket) tercatat dengan pesantren_id NULL walau
     * auditable_id jelas menunjuk ke satu pesantren. Akibatnya semua query audit per tenant
     * tidak pernah menampilkan tindakan super admin sama sekali.
     */
    private static function pesantrenId(Model $model, ?Model $user): ?int
    {
        if ($model instanceof Pesantren) {
            return $model->getKey();
        }

        return $model->pesantren_id ?? $user?->pesantren_id;
    }
}
