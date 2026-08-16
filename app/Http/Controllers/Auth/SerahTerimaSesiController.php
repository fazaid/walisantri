<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Serah-terima sesi lintas host, khusus akhir pendaftaran.
 *
 * MASALAHNYA: `/register` hidup di apex (walisantri.com) sementara panel staf di
 * app.walisantri.com, dan cookie sesi ber-scope host (§1.8). Jadi `Auth::login()`
 * di apex menghasilkan sesi yang TIDAK PERNAH terbaca di panel — pendaftar mendarat
 * di `/admin` sebagai tamu lalu dipantulkan ke form login, padahal halaman
 * pendaftaran menjanjikan "akun aktif seketika". Terverifikasi dengan mendaftarkan
 * tenant sungguhan (v4.48); cacatnya sudah ada sejak lama, hanya tidak terlihat di
 * lingkungan lokal yang dulu memakai cookie berbagi.
 *
 * BENTUK PENGAMANNYA — tiga lapis, karena ini jalan masuk tanpa kata sandi:
 *
 * 1. Token acak 64 karakter yang TIDAK membawa identitas apa pun; pemetaan token →
 *    user hidup di cache, bukan di URL. Bocornya URL tidak membocorkan siapa pun.
 * 2. Sekali pakai — `Cache::pull()` menghapusnya saat ditukar, jadi tautan yang
 *    tersimpan di riwayat browser atau ter-forward tidak bisa dipakai ulang.
 * 3. Berumur pendek: tanda tangan URL kedaluwarsa 5 menit, dan entri cache-nya juga.
 *
 * Sengaja BUKAN mekanisme login umum: satu-satunya yang mencetak token adalah
 * RegisterController::store(), tepat setelah tenant dibuat.
 */
class SerahTerimaSesiController extends Controller
{
    private const TTL_MENIT = 5;

    private const PREFIX_CACHE = 'serah-terima-sesi:';

    /**
     * Host tempat sesi user ini seharusnya hidup, beserta halaman tujuannya.
     *
     * Wali pulang ke host pesantrennya, staf ke host platform. Dipakai untuk
     * memutuskan apakah sebuah login perlu diserahterimakan sama sekali.
     */
    public static function rumah(User $user): array
    {
        if ($user->role === 'wali_santri' && $user->pesantren !== null) {
            return [$user->pesantren->hostname(), 'wali.serah-terima', ['slug' => $user->pesantren->slug]];
        }

        return [config('app.domain', 'app.walisantri.com'), 'auth.serah-terima', []];
    }

    /**
     * Cetak URL sekali pakai yang memindahkan sesi ke host rumah user ini.
     */
    public static function untuk(User $user): string
    {
        $token = Str::random(64);

        Cache::put(self::PREFIX_CACHE.$token, $user->id, now()->addMinutes(self::TTL_MENIT));

        [, $namaRute, $parameter] = self::rumah($user);

        return URL::temporarySignedRoute(
            $namaRute,
            now()->addMinutes(self::TTL_MENIT),
            $parameter + ['token' => $token],
        );
    }

    public function __invoke(string $token)
    {
        $userId = Cache::pull(self::PREFIX_CACHE.$token);

        $user = $userId ? User::find($userId) : null;

        if ($user === null) {
            // Token benar tanda tangannya tapi sudah ditukar/kedaluwarsa — bukan
            // serangan, cuma tautan basi. Antar ke pintu login apa adanya.
            return redirect()->route('login')->withErrors([
                'email' => 'Tautan masuk otomatis sudah dipakai atau kedaluwarsa. Silakan masuk dengan email dan kata sandi Anda.',
            ]);
        }

        Auth::login($user);
        request()->session()->regenerate();

        // Sesi magic link tidak boleh menumpang lewat jalur ini.
        request()->session()->forget(['magic_link_session', 'magic_link_santri_id']);

        Log::info('serah_terima_sesi', ['user_id' => $user->id, 'role' => $user->role]);

        return redirect($user->role === 'wali_santri' ? '/wali/dashboard' : '/admin');
    }
}
