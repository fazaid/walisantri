<?php

namespace App\Enums;

enum OnboardingStep: string
{
    case Profil     = 'profil';
    case Ustadz     = 'ustadz';
    case Santri     = 'santri';
    case MagicLink  = 'magic_link';
    case Pengumuman = 'pengumuman';

    public function label(): string
    {
        return match($this) {
            self::Profil     => 'Lengkapi profil pesantren (alamat & logo)',
            self::Ustadz     => 'Tambah ustadz pertama',
            self::Santri     => 'Tambah santri pertama',
            self::MagicLink  => 'Lihat/salin Magic Link wali pertama',
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
