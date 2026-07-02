<?php

namespace App\Filament\Resources\TahfidzProgress\Pages;

use App\Filament\Resources\TahfidzProgress\Schemas\TahfidzProgressForm;
use App\Filament\Resources\TahfidzProgress\TahfidzProgressResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

class CreateTahfidzProgress extends CreateRecord
{
    protected static string $resource = TahfidzProgressResource::class;

    public function form(Schema $form): Schema
    {
        return TahfidzProgressForm::configure($form);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
