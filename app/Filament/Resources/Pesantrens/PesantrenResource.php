<?php

namespace App\Filament\Resources\Pesantrens;

use App\Enums\UserRole;
use App\Filament\Resources\Pesantrens\Pages\CreatePesantren;
use App\Filament\Resources\Pesantrens\Pages\EditPesantren;
use App\Filament\Resources\Pesantrens\Pages\ListPesantrens;
use App\Filament\Resources\Pesantrens\Pages\ViewPesantren;
use App\Filament\Resources\Pesantrens\Schemas\PesantrenForm;
use App\Filament\Resources\Pesantrens\Schemas\PesantrenInfolist;
use App\Filament\Resources\Pesantrens\Tables\PesantrensTable;
use App\Models\Pesantren;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;

class PesantrenResource extends Resource
{
    protected static ?string $model = Pesantren::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $recordTitleAttribute = 'nama_pesantren';
    protected static ?string $navigationLabel = 'Pesantren';
    protected static ?string $modelLabel = 'Pesantren';
    protected static ?string $pluralModelLabel = 'Data Pesantren';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public static function form(Schema $schema): Schema
    {
        return PesantrenForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PesantrenInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PesantrensTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPesantrens::route('/'),
            'create' => CreatePesantren::route('/create'),
            'view'   => ViewPesantren::route('/{record}'),
            'edit'   => EditPesantren::route('/{record}/edit'),
        ];
    }
}
