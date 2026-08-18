<?php

namespace App\Rules;

use App\Support\WilayahLookup;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Memastikan kode desa/kelurahan benar-benar ada DAN cocok dengan tiga kode di
 * atasnya yang ikut dikirim form.
 *
 * Leluhurnya diturunkan dari kode desa itu sendiri, tidak pernah dipercaya dari
 * klien (§4.1). Ketidakcocokan berarti form-nya kacau atau payloadnya disunting —
 * dan itu jauh lebih baik ditolak terang-terangan daripada diam-diam "diperbaiki"
 * menjadi wilayah yang tidak pernah dipilih pendaftar.
 */
class WilayahJalurValid implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct(private readonly WilayahLookup $lookup) {}

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $jalur = $this->lookup->jalurDariDesa((string) $value);

        if ($jalur === null) {
            $fail('Desa/Kelurahan yang dipilih tidak dikenali. Silakan pilih ulang dari Provinsi.');

            return;
        }

        foreach (['provinsi', 'kota', 'kecamatan'] as $level) {
            if (($this->data['wilayah_'.$level] ?? null) !== $jalur[$level]['kode']) {
                $fail('Pilihan wilayah tidak konsisten. Silakan pilih ulang dari Provinsi.');

                return;
            }
        }
    }
}
