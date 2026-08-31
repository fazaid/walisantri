<?php

namespace App\Filament\Resources\Santris\Actions;

use App\Models\Santri;
use App\Services\KartuSantriPdf;
use App\Services\TahunAjaranOptions;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Unduh kartu identitas berfoto satu santri.
 *
 * Punya modal walau cuma satu isian, karena masa berlakunya tercetak permanen di
 * benda fisik: tanggal yang salah baru ketahuan setelah seratus kartu dilaminating.
 * Bawaannya akhir tahun ajaran berjalan, jadi kasus paling lazim tinggal ditekan.
 */
class UnduhKartuLengkapAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'unduh_kartu_santri';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Unduh Kartu Santri')
            ->icon('heroicon-o-identification')
            ->color('primary')
            ->modalHeading('Unduh Kartu Santri')
            ->modalSubmitActionLabel('Unduh')
            ->visible(fn (): bool => in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']))
            ->form([
                DatePicker::make('masa_berlaku')
                    ->label('Berlaku Sampai')
                    ->required()
                    ->default(fn () => TahunAjaranOptions::akhirTahunAjaran())
                    ->helperText('Tanggal ini tercetak di kartu. Bawaannya akhir tahun ajaran berjalan.'),
            ])
            ->action(fn (array $data, Santri $record) => app(KartuSantriPdf::class)
                ->untukSantri($record, Carbon::parse($data['masa_berlaku'])));
    }
}
