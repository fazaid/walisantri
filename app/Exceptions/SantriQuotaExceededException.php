<?php

namespace App\Exceptions;

use App\Models\Pesantren;
use RuntimeException;

class SantriQuotaExceededException extends RuntimeException
{
    public function __construct(Pesantren $pesantren)
    {
        parent::__construct(
            "Batas kuota paket tercapai! Akun aktif Anda saat ini adalah {$pesantren->jumlahSantriAktif()} santri (maks. {$pesantren->max_santri_kuota}). Silakan upgrade kuota melalui menu Billing."
        );
    }
}
