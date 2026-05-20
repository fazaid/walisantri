<?php

namespace App\Filament\Resources\KesantrianKarakterRapors;

use App\Filament\Resources\KesantrianKarakterRapors\Pages\CreateKesantrianKarakterRapor;
use App\Filament\Resources\KesantrianKarakterRapors\Pages\EditKesantrianKarakterRapor;
use App\Filament\Resources\KesantrianKarakterRapors\Pages\ListKesantrianKarakterRapors;
use App\Filament\Resources\KesantrianKarakterRapors\Pages\ViewKesantrianKarakterRapor;
use App\Filament\Resources\KesantrianKarakterRapors\Schemas\KesantrianKarakterRaporForm;
use App\Filament\Resources\KesantrianKarakterRapors\Schemas\KesantrianKarakterRaporInfolist;
use App\Filament\Resources\KesantrianKarakterRapors\Tables\KesantrianKarakterRaporsTable;
use App\Models\KesantrianKarakterRapor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class KesantrianKarakterRaporResource extends Resource
{
    protected static ?string $model = KesantrianKarakterRapor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'tanggal_input';

    protected static string|UnitEnum|null $navigationGroup = 'Kesantrian';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return KesantrianKarakterRaporForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KesantrianKarakterRaporInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KesantrianKarakterRaporsTable::configure($table);
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
            'index' => ListKesantrianKarakterRapors::route('/'),
            'create' => CreateKesantrianKarakterRapor::route('/create'),
            'view' => ViewKesantrianKarakterRapor::route('/{record}'),
            'edit' => EditKesantrianKarakterRapor::route('/{record}/edit'),
        ];
    }
}
