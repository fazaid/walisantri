<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformBankAccount extends Model
{
    protected $fillable = [
        'bank',
        'nomor_rekening',
        'atas_nama',
        'logo',
        'urutan',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];
}
