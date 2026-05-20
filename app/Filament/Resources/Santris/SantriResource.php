<?php

namespace App\Filament\Resources\Santris;

use App\Filament\Resources\Santris\Pages\CreateSantri;
use App\Filament\Resources\Santris\Pages\EditSantri;
use App\Filament\Resources\Santris\Pages\ListSantris;
use App\Filament\Resources\Santris\Pages\ViewSantri;
use App\Filament\Resources\Santris\Schemas\SantriForm;
use App\Filament\Resources\Santris\Schemas\SantriInfolist;
use App\Filament\Resources\Santris\Tables\SantrisTable;
use App\Models\Santri;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use BackedEnum;
use UnitEnum;

class SantriResource extends Resource
{
    protected static ?string $model = Santri::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $recordTitleAttribute = 'nama_lengkap';
    protected static ?string $navigationLabel = 'Santri';
    protected static ?string $modelLabel = 'Santri';
    protected static ?string $pluralModelLabel = 'Data Santri';

    public static function form(Schema $schema): Schema
    {
        return SantriForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SantriInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SantrisTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSantris::route('/'),
            'create' => CreateSantri::route('/create'),
            'view' => ViewSantri::route('/{record}'),
            'edit' => EditSantri::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
