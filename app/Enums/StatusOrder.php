<?php

namespace App\Enums;

enum StatusOrder: string
{
    case PendingPayment        = 'pending_payment';
    case AwaitingConfirmation  = 'awaiting_confirmation';
    case Confirmed             = 'confirmed';
    case Rejected              = 'rejected';
    case Expired               = 'expired';

    public function label(): string
    {
        return match($this) {
            self::PendingPayment       => 'Menunggu Pembayaran',
            self::AwaitingConfirmation => 'Menunggu Konfirmasi',
            self::Confirmed            => 'Dikonfirmasi',
            self::Rejected             => 'Ditolak',
            self::Expired              => 'Kadaluwarsa',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PendingPayment       => 'warning',
            self::AwaitingConfirmation => 'info',
            self::Confirmed            => 'success',
            self::Rejected             => 'danger',
            self::Expired              => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
