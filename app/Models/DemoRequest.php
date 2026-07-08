<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoRequest extends Model
{
    public const SLA_BUSINESS_DAYS = 2;

    protected $fillable = [
        'nama_pesantren',
        'nama_kontak',
        'email',
        'no_hp',
        'jumlah_santri',
        'kota',
        'catatan',
        'contacted_at',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }

    public function businessDaysWaiting(): int
    {
        return $this->created_at->diffInWeekdays(now());
    }

    public function isOverdue(): bool
    {
        return $this->contacted_at === null
            && $this->businessDaysWaiting() > self::SLA_BUSINESS_DAYS;
    }

    public static function slaCutoff(int $businessDays = self::SLA_BUSINESS_DAYS): Carbon
    {
        $cutoff = now();
        $counted = 0;

        while ($counted < $businessDays) {
            $cutoff->subDay();

            if (! $cutoff->isWeekend()) {
                $counted++;
            }
        }

        return $cutoff;
    }
}
