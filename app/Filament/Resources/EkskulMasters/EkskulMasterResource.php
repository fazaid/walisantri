<?php

namespace App\Filament\Resources\EkskulMasters;

use App\Filament\Clusters\Akademik;
use App\Filament\Concerns\HasAdminOnlyAccess;
use App\Filament\Resources\EkskulMasters\Pages\CreateEkskulMaster;
use App\Filament\Resources\EkskulMasters\Pages\EditEkskulMaster;
use App\Filament\Resources\EkskulMasters\Pages\ListEkskulMasters;
use App\Filament\Resources\EkskulMasters\Schemas\EkskulMasterForm;
use App\Filament\Resources\EkskulMasters\Tables\EkskulMastersTable;
use App\Models\EkskulMaster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EkskulMasterResource extends Resource
{
    use HasAdminOnlyAccess;

    protected static ?string $model = EkskulMaster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $cluster = Akademik::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'nama';

    protected static ?string $navigationLabel = 'Ekskul';

    protected static ?string $modelLabel = 'Ekskul';

    protected static ?string $pluralModelLabel = 'Master Ekskul';

    public static function form(Schema $schema): Schema
    {
        return EkskulMasterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EkskulMastersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEkskulMasters::route('/'),
            'create' => CreateEkskulMaster::route('/create'),
            'edit' => EditEkskulMaster::route('/{record}/edit'),
        ];
    }
}
