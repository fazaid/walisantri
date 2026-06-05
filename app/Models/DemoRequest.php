<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
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
}
