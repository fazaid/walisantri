<?php

namespace App\Filament\Resources\Santris\Actions;

use App\Models\Kelas;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Collection;

class PindahKelasBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'pindah_kelas';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Pindah Kelas')
            ->icon('heroicon-o-academic-cap')
            ->color('info')
            ->form([
                Select::make('kelas_id')
                    ->label('Kelas Tujuan')
                    ->options(fn () => Kelas::where('pesantren_id', auth()->user()?->pesantren_id)
                        ->orderBy('nama_kelas')
                        ->pluck('nama_kelas', 'id'))
                    ->searchable()
                    ->required()
                    ->native(false),
            ])
            ->action(function (Collection $records, array $data): void {
                $records->each->update(['kelas_id' => $data['kelas_id']]);
            })
            ->deselectRecordsAfterCompletion()
            ->successNotificationTitle('Santri berhasil dipindahkan ke kelas baru');
    }
}
