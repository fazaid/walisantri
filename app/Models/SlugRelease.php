<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('slug_releases')]
class SlugRelease extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'slug';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['slug', 'released_at'];

    protected function casts(): array
    {
        return ['released_at' => 'datetime'];
    }

    public static function isCoolingDown(string $slug): bool
    {
        $record = static::find($slug);
        if (! $record) {
            return false;
        }

        return $record->released_at->addDays(90)->isFuture();
    }
}
