<?php

namespace App\Support;

use App\Models\Pesantren;
use App\Models\User;

/**
 * Satu tempat yang menjawab "siapa yang diemail untuk pesantren ini, dan apakah
 * ia benar-benar bisa diemail" (PRD §12.2).
 *
 * Dipusatkan karena `users.email` NULLABLE sejak `central/2026_07_09_100001` —
 * wali santri sering didaftarkan hanya dengan nomor WhatsApp lewat impor massal.
 * `Mail::to(null)` melempar exception, dan sampai v4.22 `WarnExpiringTenants`
 * memang memanggilnya tanpa penjagaan; itu tidak pernah ketahuan justru karena
 * `MAIL_MAILER=log` menelan segalanya.
 *
 * Kalau penjagaan ini disalin-tempel ke empat pemanggil, satu pasti terlewat.
 */
class PenerimaEmail
{
    /** Admin pesantren yang layak dikirimi email, atau null bila tidak ada. */
    public static function adminPesantren(Pesantren $pesantren): ?User
    {
        $pesantren->loadMissing('users');

        $admin = $pesantren->users
            ->where('role', 'admin_pesantren')
            ->first();

        return self::layak($admin) ? $admin : null;
    }

    /** True bila user ada dan punya alamat email yang bisa dituju. */
    public static function layak(?User $user): bool
    {
        return $user !== null && filled($user->email);
    }
}
