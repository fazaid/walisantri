<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
        // Satu sumber kebenaran dengan scopeOverdue(): sudah melewati batas SLA
        // (strict — tepat SLA_BUSINESS_DAYS hari kerja masih dianggap on-time,
        // sesuai janji "1–2 hari kerja" di form demo).
        return $this->contacted_at === null
            && $this->created_at < self::slaCutoff();
    }

    /**
     * Antrean yang belum dihubungi & sudah melewati batas SLA.
     * Dipakai badge navigasi, filter tabel, dan isOverdue() agar konsisten.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('contacted_at')
            ->where('created_at', '<', self::slaCutoff());
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
