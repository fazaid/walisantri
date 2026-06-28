<?php

namespace App\Filament\Resources\Santris\Pages;

use App\Filament\Resources\Santris\SantriResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSantris extends ListRecords
{
    protected static string $resource = SantriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Ekspor Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => auth()->user()?->role === 'admin_pesantren')
                ->url(fn () => route('admin.export.santri')),

            CreateAction::make()->visible(fn () => static::getResource()::canCreate()),
        ];
    }
}
