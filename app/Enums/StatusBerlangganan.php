<?php

namespace App\Enums;

enum StatusBerlangganan: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Trial => 'Trial',
            self::Active => 'Aktif',
            self::Suspended => 'Suspended',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Trial => 'info',
            self::Active => 'success',
            self::Suspended => 'danger',
            self::Expired => 'warning',
        };
    }

    /**
     * Status yang masih "berjalan" dan karenanya bisa kedaluwarsa.
     *
     * Satu daftar untuk semua pemakainya — kartu & tabel "Akan Expired" di dashboard
     * super admin, WarnExpiringTenants(WhatsApp), CheckExpiredTenants, dan
     * Pesantren::isActive(). Dulu daftar ini di-hardcode terpisah di lima tempat;
     * begitu satu bergeser, angka dashboard berhenti cocok dengan tenant yang
     * sebenarnya ditindak job.
     *
     * @return list<string>
     */
    public static function berjalan(): array
    {
        return [self::Trial->value, self::Active->value];
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
