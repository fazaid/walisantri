<?php

namespace App\Enums;

enum OnboardingStep: string
{
    // Urutan case menentukan urutan tampil di OnboardingChecklistWidget.
    case Profil = 'profil';
    case Ustadz = 'ustadz';
    case Kelas = 'kelas';
    case Santri = 'santri';
    case Pengumuman = 'pengumuman';

    public function label(): string
    {
        return match ($this) {
            self::Profil => 'Lengkapi profil pesantren (alamat & logo)',
            self::Ustadz => 'Tambah ustadz pertama',
            self::Kelas => 'Buat kelas pertama',
            self::Santri => 'Tambah santri pertama',
            self::Pengumuman => 'Buat pengumuman perdana',
        };
    }

    public function isRequired(): bool
    {
        return $this !== self::Pengumuman;
    }

    /** @return self[] */
    public static function required(): array
    {
        return array_values(array_filter(self::cases(), fn (self $step) => $step->isRequired()));
    }
}
