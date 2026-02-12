<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Student extends Model
{
    /// Ini artinya semua kolom boleh diisi (mass assignment)
    // Sangat memudahkan untuk tahap awal development
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(ParentProfile::class, 'parent_profile_id');
    }

    /**
     * Relasi ke Riwayat Prestasi
     */
    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    /**
     * Relasi ke Riwayat Pelanggaran
     */
    public function violations(): HasMany
    {
        return $this->hasMany(Violation::class);
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
