<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Perakitan tautan verifikasi email (§12.2).
 *
 * Dipusatkan karena dipakai dari tiga tempat — email sambutan, kirim ulang dari
 * spanduk, dan tes — dan ketiganya harus memakai masa berlaku serta bentuk hash
 * yang persis sama supaya tautannya tidak saling menolak.
 */
class TautanVerifikasiEmail
{
    /**
     * Masa berlaku tautan, disamakan dengan broker reset kata sandi
     * (`config/auth.php`, 60 menit). Ditulis sebagai konstanta karena
     * `config/auth.php` tidak punya blok `verification` sama sekali —
     * memanggil config yang tidak ada hanya menyamarkan angkanya.
     */
    public const MENIT_BERLAKU = 60;

    public static function untuk(User $user): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(self::MENIT_BERLAKU),
            [
                'id' => $user->getKey(),
                // Hash alamat, bukan alamatnya sendiri: tautan lama otomatis mati
                // begitu email diubah, dan alamatnya tidak terbaca di URL.
                'hash' => sha1((string) $user->getEmailForVerification()),
            ],
        );
    }
}
