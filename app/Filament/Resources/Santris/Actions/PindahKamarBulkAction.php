<?php

namespace App\Filament\Resources\Santris\Actions;

use App\Models\Kamar;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Collection;

class PindahKamarBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'pindah_kamar';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Pindah Kamar')
            ->icon('heroicon-o-home')
            ->color('warning')
            ->form([
                Select::make('kamar_id')
                    ->label('Kamar Tujuan')
                    ->options(fn () => Kamar::where('pesantren_id', auth()->user()?->pesantren_id)
                        ->orderBy('nama_kamar')
                        ->pluck('nama_kamar', 'id'))
                    ->searchable()
                    ->required()
                    ->native(false),
            ])
            ->action(function (Collection $records, array $data): void {
                $records->each->update(['kamar_id' => $data['kamar_id']]);
            })
            ->deselectRecordsAfterCompletion()
            ->successNotificationTitle('Santri berhasil dipindahkan ke kamar baru');
    }
}
