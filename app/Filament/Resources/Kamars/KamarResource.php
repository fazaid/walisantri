<?php

namespace App\Filament\Resources\Kamars;

use App\Filament\Clusters\Santri;
use App\Filament\Concerns\HasAdminOnlyAccess;
use App\Filament\Resources\Kamars\Pages\CreateKamar;
use App\Filament\Resources\Kamars\Pages\EditKamar;
use App\Filament\Resources\Kamars\Pages\ListKamars;
use App\Filament\Resources\Kamars\Schemas\KamarForm;
use App\Filament\Resources\Kamars\Tables\KamarsTable;
use App\Models\Kamar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KamarResource extends Resource
{
    use HasAdminOnlyAccess;

    protected static ?string $model = Kamar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $cluster = Santri::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'nama_kamar';

    protected static ?string $navigationLabel = 'Kamar';

    protected static ?string $modelLabel = 'Kamar';

    protected static ?string $pluralModelLabel = 'Data Kamar';

    public static function form(Schema $schema): Schema
    {
        return KamarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KamarsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKamars::route('/'),
            'create' => CreateKamar::route('/create'),
            'edit' => EditKamar::route('/{record}/edit'),
        ];
    }
}
