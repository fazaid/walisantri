<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Teacher extends Model
{
    protected $guarded = [];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
