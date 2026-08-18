<?php

namespace App\Rules;

use App\Services\FonnteWhatsAppService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Nomor WhatsApp yang benar-benar bisa dikirimi pesan.
 *
 * Regexnya sama dengan kolom no_hp di form demo (DemoController), tapi ditambah
 * satu hal yang selama ini hilang: kepastian bahwa normalisasi Fonnte tidak
 * mengembalikan null. Yang disimpan ke users.phone_number adalah bentuk
 * ternormalisasi (62…) seperti jalur impor Excel (SantriImport::normalizePhone),
 * sehingga nomor yang lolos di sini pasti bisa dipakai — bukan gagal belakangan
 * sebagai RuntimeException jauh dari titik input.
 */
class NomorWhatsApp implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/^[0-9+\-\s()]{8,20}$/', (string) $value)) {
            $fail('Format nomor WhatsApp tidak valid — gunakan angka saja.');

            return;
        }

        if (app(FonnteWhatsAppService::class)->normalizePhoneNumber((string) $value) === null) {
            $fail('Nomor WhatsApp terlalu pendek.');
        }
    }
}
