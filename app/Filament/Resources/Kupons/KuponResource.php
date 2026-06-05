<?php

namespace App\Filament\Resources\Kupons;

use App\Enums\UserRole;
use App\Filament\Resources\Kupons\Pages\CreateKupon;
use App\Filament\Resources\Kupons\Pages\EditKupon;
use App\Filament\Resources\Kupons\Pages\ListKupons;
use App\Filament\Resources\Kupons\Schemas\KuponForm;
use App\Filament\Resources\Kupons\Tables\KuponsTable;
use App\Models\Kupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class KuponResource extends Resource
{
    protected static ?string $model = Kupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel  = 'Kupon Diskon';
    protected static ?string $modelLabel       = 'Kupon';
    protected static ?string $pluralModelLabel = 'Kupon Diskon';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return KuponForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KuponsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListKupons::route('/'),
            'create' => CreateKupon::route('/create'),
            'edit'   => EditKupon::route('/{record}/edit'),
        ];
    }
}
