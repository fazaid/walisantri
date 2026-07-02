<?php

namespace App\Filament\Resources\TahfidzProgress\Pages;

use App\Filament\Resources\TahfidzProgress\Schemas\TahfidzProgressForm;
use App\Filament\Resources\TahfidzProgress\TahfidzProgressResource;
use App\Models\TahfidzProgress;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateTahfidzProgress extends CreateRecord
{
    protected static string $resource = TahfidzProgressResource::class;

    public function form(Schema $form): Schema
    {
        return TahfidzProgressForm::configureCreate($form);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $ranges = $data['ranges'] ?? [];
        unset($data['ranges']);

        $last = null;
        foreach ($ranges as $range) {
            $last = TahfidzProgress::create(array_merge($data, $range));
        }

        return $last ?? TahfidzProgress::create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
