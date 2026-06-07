<?php

namespace App\Filament\Resources\NilaiAkademiks\Pages;

use App\Filament\Resources\NilaiAkademiks\NilaiAkademikResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNilaiAkademik extends ListRecords
{
    protected static string $resource = NilaiAkademikResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
