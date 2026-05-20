<?php

namespace App\Filament\Resources\KesantrianInventaris;

use App\Filament\Resources\KesantrianInventaris\Pages\CreateKesantrianInventaris;
use App\Filament\Resources\KesantrianInventaris\Pages\EditKesantrianInventaris;
use App\Filament\Resources\KesantrianInventaris\Pages\ListKesantrianInventaris;
use App\Filament\Resources\KesantrianInventaris\Pages\ViewKesantrianInventaris;
use App\Filament\Resources\KesantrianInventaris\Schemas\KesantrianInventarisForm;
use App\Filament\Resources\KesantrianInventaris\Schemas\KesantrianInventarisInfolist;
use App\Filament\Resources\KesantrianInventaris\Tables\KesantrianInventarisTable;
use App\Models\KesantrianInventaris as KesantrianInventarisModel;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class KesantrianInventarisResource extends Resource
{
    protected static ?string $model = KesantrianInventarisModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $recordTitleAttribute = 'nama_barang_umum';
    protected static ?string $navigationLabel = 'Inventaris';
    protected static ?string $modelLabel = 'Inventaris';
    protected static ?string $pluralModelLabel = 'Data Inventaris';

    protected static string|UnitEnum|null $navigationGroup = 'Kesantrian';
    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return KesantrianInventarisForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KesantrianInventarisInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KesantrianInventarisTable::configure($table);
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
            'index' => ListKesantrianInventaris::route('/'),
            'create' => CreateKesantrianInventaris::route('/create'),
            'view' => ViewKesantrianInventaris::route('/{record}'),
            'edit' => EditKesantrianInventaris::route('/{record}/edit'),
        ];
    }
}
