<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank');
            $table->string('nomor_rekening');
            $table->string('atas_nama');
            $table->string('logo')->nullable();
            $table->smallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Bawa data rekening yang sudah dipakai dari .env (bukan config('billing.bank_transfer') —
        // key config itu sudah dihapus bersamaan dengan migrasi ini, jadi baca env() langsung
        // supaya nilai lama tetap terbawa apapun urutan deploy vs migrate) — super_admin
        // tinggal lengkapi logo lewat panel setelahnya.
        $legacyBanks = [
            [
                'bank'      => env('BILLING_BANK1_NAMA', 'BCA'),
                'nomor'     => env('BILLING_BANK1_NOMOR', '1234567890'),
                'atas_nama' => env('BILLING_BANK1_ATAS_NAMA', 'PT Walisantri Digital'),
            ],
            [
                'bank'      => env('BILLING_BANK2_NAMA', 'Mandiri'),
                'nomor'     => env('BILLING_BANK2_NOMOR', '1110002223334'),
                'atas_nama' => env('BILLING_BANK2_ATAS_NAMA', 'PT Walisantri Digital'),
            ],
        ];

        foreach ($legacyBanks as $i => $bank) {
            DB::table('platform_bank_accounts')->insert([
                'bank'           => $bank['bank'],
                'nomor_rekening' => $bank['nomor'],
                'atas_nama'      => $bank['atas_nama'],
                'urutan'         => $i,
                'aktif'          => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_bank_accounts');
    }
};
