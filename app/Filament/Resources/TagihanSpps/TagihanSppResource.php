<?php

namespace App\Filament\Resources\TagihanSpps;

use App\Enums\UserRole;
use App\Filament\Resources\TagihanSpps\Pages\ListTagihanSpps;
use App\Filament\Resources\TagihanSpps\Pages\ViewTagihanSpp;
use App\Filament\Resources\TagihanSpps\Schemas\TagihanSppInfolist;
use App\Filament\Resources\TagihanSpps\Tables\TagihanSppsTable;
use App\Models\TagihanSpp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TagihanSppResource extends Resource
{
    protected static ?string $model = TagihanSpp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel  = 'Tagihan SPP';
    protected static ?string $modelLabel       = 'Tagihan SPP';
    protected static ?string $pluralModelLabel = 'Tagihan SPP';

    public static function canAccess(): bool
    {
        $role = auth()->user()?->role;
        return $role === UserRole::AdminPesantren->value;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function infolist(Schema $schema): Schema
    {
        return TagihanSppInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TagihanSppsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTagihanSpps::route('/'),
            'view'  => ViewTagihanSpp::route('/{record}'),
        ];
    }
}
