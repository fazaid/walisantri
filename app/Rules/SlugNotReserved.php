<?php

namespace App\Rules;

use App\Models\SlugRelease;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SlugNotReserved implements ValidationRule
{
    private const RESERVED = [
        'www', 'app', 'api', 'admin', 'central', 'dash', 'mail',
        'billing', 'status', 'docs', 'blog', 'support', 'panel',
        'dashboard', 'static', 'cdn',
        // Dipakai sandbox publik. Tanpa ini, pesantren mana pun bisa mengambil
        // demo.walisantri.com lebih dulu — lalu slugnya terkunci cooldown 90 hari.
        'demo', 'sandbox', 'coba', 'contoh',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array(strtolower((string) $value), self::RESERVED, strict: true)) {
            $fail('Slug ini tidak dapat digunakan karena merupakan kata yang dicadangkan sistem.');

            return;
        }

        if (SlugRelease::isCoolingDown((string) $value)) {
            $fail('Slug ini baru saja dilepas dan masih dalam periode cooldown 90 hari. Coba lagi nanti.');
        }
    }
}
