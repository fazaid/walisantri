<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

/**
 * Verifikasi alamat email — LUNAK, tidak memblokir apa pun (§12.2).
 *
 * Tujuannya bukan menghalangi akses, melainkan mengetahui pesantren mana yang
 * alamat emailnya terbukti hidup. Sejak WhatsApp dimatikan, email adalah
 * satu-satunya kanal: satu huruf salah membuat sebuah pesantren tidak menerima
 * tagihan, tidak menerima peringatan masa aktif, dan tidak bisa memulihkan kata
 * sandinya sendiri — tanpa ada yang menyadarinya.
 */
class VerifikasiEmailController extends Controller
{
    /**
     * Sengaja TIDAK mewajibkan login.
     *
     * Tautannya lumrah dibuka di perangkat lain (email di ponsel, panel di
     * laptop). Tanda tangan URL sudah membuktikan tautan itu benar-benar kami
     * terbitkan, dan hash alamat membuktikan pemegangnya menerima email di
     * alamat itu — mewajibkan sesi hanya menambah kebuntuan tanpa menambah bukti.
     */
    public function verify(Request $request, string $id, string $hash)
    {
        $user = User::find($id);

        if (! $user || ! hash_equals($hash, sha1((string) $user->getEmailForVerification()))) {
            abort(403, 'Tautan verifikasi tidak berlaku.');
        }

        // Idempoten: membuka tautan yang sama dua kali bukan kesalahan, dan
        // stempel waktu verifikasi pertama tidak boleh tergeser.
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        Notification::make()
            ->title('Alamat email terkonfirmasi')
            ->body('Terima kasih. Tagihan dan pemberitahuan masa aktif akan kami kirim ke alamat ini.')
            ->success()
            ->send();

        return redirect('/admin');
    }

    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back();
        }

        if (blank($user->email)) {
            return back();
        }

        $user->sendEmailVerificationNotification();

        Notification::make()
            ->title('Tautan konfirmasi dikirim ulang')
            ->body("Kami kirim ke {$user->email}. Periksa juga folder spam bila tidak muncul dalam beberapa menit.")
            ->success()
            ->send();

        return back();
    }
}
