<?php

namespace App\Support;

use App\Models\Santri;
use Illuminate\Support\Facades\DB;

/**
 * Kode kartu presensi santri.
 *
 * ⚠️ Kode ini SENGAJA terpisah dari `santri.uuid`, dan itu bukan soal penamaan.
 * `uuid` adalah token bearer Magic Link: `Santri::linkWali()` merangkainya jadi
 * URL, dan `VerifyMagicToken` menukarnya menjadi `Auth::login($wali)` — sesi wali
 * yang utuh, mencakup semua anaknya, SPP, uang saku, dan rapor. Kartu presensi
 * adalah benda fisik yang dipegang anak, difotokopi, dan dipotret untuk grup
 * WhatsApp; mencetak `uuid` di atasnya sama dengan mencetak kredensial (§13.2).
 *
 * Kode ini tidak membuka data apa pun sendirian — ia hanya bisa ditukar jadi baris
 * presensi di dalam sesi ustadz/admin yang sudah terautentikasi dan ter-scope
 * tenant. Yang dicegah oleh keacakannya bukan pembocoran data, melainkan santri
 * memalsukan kartu temannya dengan menebak; itu sebabnya `nis` juga tidak dipakai
 * (berurutan, dan sudah tercetak di banyak berkas lain).
 */
class KodePresensi
{
    /**
     * Crockford Base32 — sengaja TANPA I, L, O, dan U.
     *
     * I/L/1 dan O/0 mudah tertukar saat kode dibaca manusia (QR rusak, kartu
     * lecek, petugas mengetik ulang), dan U dibuang di skema aslinya untuk
     * menghindari kata yang tidak pantas terbentuk secara kebetulan.
     */
    private const ALFABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const PANJANG = 12;

    /** Prefiks versi skema — supaya format isi QR bisa berevolusi tanpa menebak. */
    public const PREFIKS = 'WSP1.';

    /** Satu kode acak, belum tentu unik — keunikan dijamin pemanggilnya. */
    public static function acak(): string
    {
        $kode = '';
        $batas = strlen(self::ALFABET) - 1;

        for ($i = 0; $i < self::PANJANG; $i++) {
            $kode .= self::ALFABET[random_int(0, $batas)];
        }

        return $kode;
    }

    /**
     * Kode acak yang dijamin belum dipakai.
     *
     * Cek keunikan lewat query builder, bukan Eloquent: dipanggil dari
     * SantriObserver::creating() dan dari migrasi backfill, dan yang terakhir
     * berjalan tanpa sesi auth sehingga global scope `pesantren` akan menyaring
     * habis apa pun yang dibaca lewat model.
     */
    public static function buat(): string
    {
        do {
            $kode = self::acak();
        } while (DB::table('santri')->where('kode_presensi', $kode)->exists());

        return $kode;
    }

    /** Isi QR: string opaque, BUKAN URL — lihat catatan kelas. */
    public static function payload(string $kode): string
    {
        return self::PREFIKS.$kode;
    }

    /**
     * Baca kode dari hasil pindaian.
     *
     * Menerima payload lengkap (`WSP1.XXXX`) maupun kodenya saja, karena petugas
     * bisa saja mengetik kode dari kartu secara manual saat QR-nya rusak.
     */
    public static function bacaPayload(string $masukan): string
    {
        $bersih = trim($masukan);

        return str_starts_with($bersih, self::PREFIKS)
            ? substr($bersih, strlen(self::PREFIKS))
            : $bersih;
    }

    /** Santri pemilik kode — tetap melewati global scope tenant. */
    public static function cariSantri(string $masukan): ?Santri
    {
        $kode = self::bacaPayload($masukan);

        if ($kode === '') {
            return null;
        }

        return Santri::where('kode_presensi', $kode)->first();
    }
}
