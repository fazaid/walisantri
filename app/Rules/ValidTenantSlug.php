<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidTenantSlug implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Slug harus berupa teks.');
            return;
        }

        if (strlen($value) < 3 || strlen($value) > 30) {
            $fail('Slug harus antara 3 hingga 30 karakter.');
            return;
        }

        if (! preg_match('/^[a-z0-9][a-z0-9\-]*[a-z0-9]$/', $value) && strlen($value) > 1) {
            $fail('Slug hanya boleh huruf kecil, angka, dan tanda hubung; tidak boleh diawali atau diakhiri tanda hubung.');
            return;
        }

        if (preg_match('/--/', $value)) {
            $fail('Slug tidak boleh mengandung dua tanda hubung berurutan.');
        }
    }
}
