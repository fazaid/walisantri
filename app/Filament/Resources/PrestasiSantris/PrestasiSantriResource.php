<?php

namespace App\Filament\Resources\PrestasiSantris;

use App\Enums\UserRole;
use App\Filament\Resources\PrestasiSantris\Pages\CreatePrestasiSantri;
use App\Filament\Resources\PrestasiSantris\Pages\EditPrestasiSantri;
use App\Filament\Resources\PrestasiSantris\Pages\ListPrestasiSantris;
use App\Filament\Resources\PrestasiSantris\Pages\ViewPrestasiSantri;
use App\Filament\Resources\PrestasiSantris\Schemas\PrestasiSantriForm;
use App\Filament\Resources\PrestasiSantris\Schemas\PrestasiSantriInfolist;
use App\Filament\Resources\PrestasiSantris\Tables\PrestasiSantrisTable;
use App\Models\PrestasiSantri;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PrestasiSantriResource extends Resource
{
    protected static ?string $model = PrestasiSantri::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Santri';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel  = 'Prestasi';
    protected static ?string $modelLabel       = 'Prestasi';
    protected static ?string $pluralModelLabel = 'Prestasi Santri';

    protected static ?string $slug = 'prestasi';

    public static function canAccess(): bool
    {
        $role = auth()->user()?->role;
        return in_array($role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ]);
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ]);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return in_array(auth()->user()?->role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ]);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->role === UserRole::AdminPesantren->value;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->role === UserRole::AdminPesantren->value;
    }

    public static function form(Schema $schema): Schema
    {
        return PrestasiSantriForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PrestasiSantriInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrestasiSantrisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPrestasiSantris::route('/'),
            'create' => CreatePrestasiSantri::route('/create'),
            'view'   => ViewPrestasiSantri::route('/{record}'),
            'edit'   => EditPrestasiSantri::route('/{record}/edit'),
        ];
    }
}
