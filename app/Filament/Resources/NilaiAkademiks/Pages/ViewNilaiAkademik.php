<?php

namespace App\Filament\Resources\NilaiAkademiks\Pages;

use App\Filament\Resources\NilaiAkademiks\NilaiAkademikResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewNilaiAkademik extends ViewRecord
{
    protected static string $resource = NilaiAkademikResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->modalWidth(Width::FourExtraLarge)
                ->before(NilaiAkademikResource::guardDuplikat()),
            DeleteAction::make(),
        ];
    }
}
