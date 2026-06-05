<?php

namespace App\Filament\Resources\PrestasiSantris\Pages;

use App\Filament\Resources\PrestasiSantris\PrestasiSantriResource;
use App\Filament\Resources\PrestasiSantris\Schemas\PrestasiSantriInfolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewPrestasiSantri extends ViewRecord
{
    protected static string $resource = PrestasiSantriResource::class;

    public function infolist(Schema $schema): Schema
    {
        return PrestasiSantriInfolist::configure($schema);
    }
}
