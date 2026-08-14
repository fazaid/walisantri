<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fondasi email transaksional (§12.2).
 *
 * Dua tabel dengan tanggung jawab terpisah, meniru pasangan
 * `whatsapp_gateway_settings` + `whatsapp_settings`:
 *
 * - `email_gateway_settings` — kredensial SMTP, terenkripsi lewat cast Eloquent.
 * - `email_settings` — kill-switch per jenis email, boleh dibaca siapa saja.
 *
 * Dipisah karena umurnya berbeda: kredensial berganti saat provider berganti,
 * sedangkan kill-switch dipakai sehari-hari untuk membungkam satu jenis email
 * tanpa melumpuhkan kanalnya.
 */
return new class extends Migration
{
    private const KILL_SWITCH = [
        ['email_sambutan_enabled', 'Kirim email sambutan ke admin pesantren yang baru mendaftar'],
        ['email_reset_password_enabled', 'Kirim email tautan reset kata sandi (admin, ustadz, super admin)'],
        ['email_invoice_enabled', 'Kirim email invoice saat order upgrade/perpanjangan dibuat'],
        ['email_pembayaran_enabled', 'Kirim email saat pembayaran order dikonfirmasi Super Admin'],
        ['email_reminder_expired_enabled', 'Kirim email peringatan H-7/H-3 sebelum langganan berakhir'],
    ];

    public function up(): void
    {
        Schema::create('email_gateway_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('email_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->boolean('value')->default(true);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });

        DB::table('email_settings')->insert(
            collect(self::KILL_SWITCH)
                ->map(fn (array $baris) => [
                    'key' => $baris[0],
                    'value' => true,
                    'keterangan' => $baris[1],
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('email_settings');
        Schema::dropIfExists('email_gateway_settings');
    }
};
