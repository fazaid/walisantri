<?php

namespace App\Enums;

enum PaketLangganan: string
{
    case Rintisan = 'rintisan';
    case Tumbuh = 'tumbuh';
    case Berkembang = 'berkembang';
    case Maju = 'maju';

    public function label(): string
    {
        return match ($this) {
            self::Rintisan => 'Rintisan',
            self::Tumbuh => 'Tumbuh',
            self::Berkembang => 'Berkembang',
            self::Maju => 'Maju',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Rintisan => 'info',
            self::Tumbuh => 'success',
            self::Berkembang => 'warning',
            self::Maju => 'primary',
        };
    }

    public function maxSantri(): int
    {
        return match ($this) {
            self::Rintisan => 100,
            self::Tumbuh => 250,
            self::Berkembang => 500,
            self::Maju => 1000,
        };
    }

    /**
     * Paket yang boleh dipilih sendiri — di corong pendaftaran (/harga → /register)
     * maupun saat mengganti paket di masa trial.
     *
     * Maju dikecualikan: kuotanya dinegosiasikan lewat add-on per 100 santri (§5.3),
     * jadi CTA-nya membuka percakapan WhatsApp dengan tim (v4.52), bukan sebuah form.
     * Satu tempat untuk pengecualian itu — sebelumnya ia hidup terpisah di
     * PaketHargaService, dan setiap permukaan baru melahirkan salinan keempatnya.
     */
    public function bisaDipilihSendiri(): bool
    {
        return $this !== self::Maju;
    }

    /**
     * @return list<self>
     */
    public static function pilihanMandiri(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $paket) => $paket->bisaDipilihSendiri(),
        ));
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
