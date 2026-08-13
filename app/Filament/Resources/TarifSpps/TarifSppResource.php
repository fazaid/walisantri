<?php

namespace App\Filament\Resources\TarifSpps;

use App\Enums\UserRole;
use App\Filament\Clusters\Keuangan;
use App\Filament\Resources\TarifSpps\Pages\ListTarifSpps;
use App\Filament\Resources\TarifSpps\Schemas\TarifSppForm;
use App\Filament\Resources\TarifSpps\Tables\TarifSppsTable;
use App\Models\TarifSpp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TarifSppResource extends Resource
{
    protected static ?string $model = TarifSpp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $cluster = Keuangan::class;

    // Tidak muncul sebagai tab Keuangan; masuknya lewat tombol "Tarif SPP" di halaman Tagihan SPP.
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Tarif SPP';

    protected static ?string $modelLabel = 'Tarif SPP';

    protected static ?string $pluralModelLabel = 'Tarif SPP';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::AdminPesantren->value;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return TarifSppForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TarifSppsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTarifSpps::route('/'),
        ];
    }
}
