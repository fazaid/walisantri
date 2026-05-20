<?php

// File: app/Models/User.php
// Ganti seluruh isi file default Laravel dengan kode ini.

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Table('users')]
#[Fillable([
    'pesantren_id',
    'name',
    'email',
    'phone_number',
    'password',
    'role',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    // Hanya role berikut yang boleh masuk panel Filament
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, [
            'super_admin',
            'admin_pesantren',
            'ustadz',
        ]);
    }

    // Tidak pakai Multitenantable — users difilter manual via middleware,
    // bukan Global Scope, karena super_admin tidak punya pesantren_id.

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // --- Role helpers ---

    public function isSuperAdmin(): bool    { return $this->role === 'super_admin'; }
    public function isAdminPesantren(): bool { return $this->role === 'admin_pesantren'; }
    public function isUstadz(): bool        { return $this->role === 'ustadz'; }
    public function isWaliSantri(): bool    { return $this->role === 'wali_santri'; }

    // --- Relations ---

    public function pesantren(): BelongsTo
    {
        return $this->belongsTo(Pesantren::class);
    }

    // Santri yang diasuh (sebagai wali)
    public function anakSantri(): HasMany
    {
        return $this->hasMany(Santri::class, 'wali_santri_id');
    }

    // Santri yang dibimbing (sebagai ustadz halaqah)
    public function halaqah(): HasMany
    {
        return $this->hasMany(Santri::class, 'pembimbing_ustadz_id');
    }
}