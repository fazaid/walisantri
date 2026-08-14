<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Kredensial SMTP (Brevo) — terenkripsi di database, bukan di .env (§3.1).
 *
 * Alasannya operasional: super admin bisa mengganti provider atau merotasi kunci
 * tanpa akses SSH ke server. `.env` tetap jadi cadangan — kalau tabel ini kosong,
 * config bawaan yang berlaku, dan itulah yang membuat CI serta tes lokal tetap
 * memakai mailer `log`/`array` tanpa konfigurasi tambahan.
 */
class EmailGatewaySetting extends Model
{
    protected $table = 'email_gateway_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    /** Kunci yang membentuk konfigurasi mailer smtp. */
    public const KUNCI_SMTP = [
        'smtp_host',
        'smtp_port',
        'smtp_scheme',
        'smtp_username',
        'smtp_password',
    ];

    /** Kunci identitas pengirim, di luar blok mailer. */
    public const KUNCI_PENGIRIM = [
        'from_address',
        'from_name',
    ];

    private const CACHE_KEY = 'email_gateway_settings:semua';

    protected function casts(): array
    {
        return ['value' => 'encrypted'];
    }

    // find()->value dipakai (bukan query builder ->value('value')) supaya cast
    // 'encrypted' jalan lewat hydration Eloquent, tidak mengembalikan ciphertext mentah.
    public static function get(string $key): ?string
    {
        return static::semua()[$key] ?? null;
    }

    public static function set(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public static function lupakan(string $key): void
    {
        static::where('key', $key)->delete();
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Seluruh kredensial dalam SATU cache key.
     *
     * Sengaja tidak satu cache key per kunci: CACHE_STORE=database, jadi tiap
     * cache miss adalah query. Dibaca sekali tiap request lewat applyToConfig(),
     * jadi tujuh key berarti tujuh query — satu key berarti satu.
     *
     * @return array<string, string>
     */
    public static function semua(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            3600,
            fn () => static::all()->pluck('value', 'key')->filter()->all(),
        );
    }

    /**
     * Suntikkan kredensial ke config mailer saat boot.
     *
     * Dipanggil dari AppServiceProvider. Wajib tahan banting: dijalankan juga saat
     * `php artisan migrate` di database yang tabelnya belum ada, dan kegagalan di
     * sana berarti aplikasi tidak bisa di-boot sama sekali — termasuk untuk
     * menjalankan migrasi yang membuat tabelnya.
     */
    public static function applyToConfig(): void
    {
        try {
            $nilai = static::semua();
        } catch (\Throwable) {
            return;
        }

        if ($nilai === []) {
            return;
        }

        $smtp = Config::get('mail.mailers.smtp', []);

        foreach (self::KUNCI_SMTP as $kunci) {
            if (! isset($nilai[$kunci])) {
                continue;
            }

            // smtp_host -> host, smtp_password -> password, dst.
            $smtp[substr($kunci, 5)] = $kunci === 'smtp_port'
                ? (int) $nilai[$kunci]
                : $nilai[$kunci];
        }

        Config::set('mail.mailers.smtp', $smtp);

        // Hanya ambil alih mailer default kalau host-nya benar-benar terisi.
        // Tanpa penjagaan ini, mengisi sekadar nama pengirim akan memaksa seluruh
        // aplikasi mengirim lewat SMTP yang belum dikonfigurasi.
        if (filled($nilai['smtp_host'] ?? null)) {
            Config::set('mail.default', 'smtp');
        }

        if (isset($nilai['from_address'])) {
            Config::set('mail.from.address', $nilai['from_address']);
        }

        if (isset($nilai['from_name'])) {
            Config::set('mail.from.name', $nilai['from_name']);
        }
    }
}
