<?php

namespace App\Filament\Resources\TahfidzProgress\Pages;

use App\Filament\Resources\TahfidzProgress\TahfidzProgressResource;
use App\Models\TahfidzProgress;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTahfidzProgress extends EditRecord
{
    protected static string $resource = TahfidzProgressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['ranges'] = [[
            'halaman_mulai'  => $data['halaman_mulai'],
            'halaman_selesai' => $data['halaman_selesai'],
            'nama_surah'     => $data['nama_surah'],
        ]];
        unset($data['halaman_mulai'], $data['halaman_selesai'], $data['nama_surah']);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $ranges = $data['ranges'] ?? [];
        unset($data['ranges']);

        if (! empty($ranges)) {
            $data['pesantren_id'] = $record->pesantren_id;
            $first = array_shift($ranges);
            $record->update(array_merge($data, $first));

            foreach ($ranges as $range) {
                TahfidzProgress::create(array_merge($data, $range));
            }
        }

        return $record;
    }
}
