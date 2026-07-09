<?php

namespace App\Filament\Resources\DemoRequests;

use App\Enums\UserRole;
use App\Filament\Resources\DemoRequests\Pages\ListDemoRequests;
use App\Filament\Resources\DemoRequests\Pages\ViewDemoRequest;
use App\Filament\Resources\DemoRequests\Schemas\DemoRequestInfolist;
use App\Filament\Resources\DemoRequests\Tables\DemoRequestsTable;
use App\Models\DemoRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DemoRequestResource extends Resource
{
    protected static ?string $model = DemoRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel  = 'Antrean Demo';
    protected static ?string $modelLabel       = 'Permintaan Demo';
    protected static ?string $pluralModelLabel = 'Antrean Demo';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) DemoRequest::whereNull('contacted_at')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return DemoRequest::whereNull('contacted_at')
            ->where('created_at', '<=', DemoRequest::slaCutoff())
            ->exists() ? 'danger' : 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return DemoRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DemoRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDemoRequests::route('/'),
            'view'  => ViewDemoRequest::route('/{record}'),
        ];
    }
}
