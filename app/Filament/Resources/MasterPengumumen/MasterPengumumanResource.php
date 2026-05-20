<?php

namespace App\Filament\Resources\MasterPengumumen;

use App\Filament\Resources\MasterPengumumen\Pages\CreateMasterPengumuman;
use App\Filament\Resources\MasterPengumumen\Pages\EditMasterPengumuman;
use App\Filament\Resources\MasterPengumumen\Pages\ListMasterPengumumen;
use App\Filament\Resources\MasterPengumumen\Pages\ViewMasterPengumuman;
use App\Filament\Resources\MasterPengumumen\Schemas\MasterPengumumanForm;
use App\Filament\Resources\MasterPengumumen\Schemas\MasterPengumumanInfolist;
use App\Filament\Resources\MasterPengumumen\Tables\MasterPengumumenTable;
use App\Models\MasterPengumuman;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MasterPengumumanResource extends Resource
{
    protected static ?string $model = MasterPengumuman::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'judul_maklumat';

    public static function form(Schema $schema): Schema
    {
        return MasterPengumumanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MasterPengumumanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterPengumumenTable::configure($table);
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
            'index' => ListMasterPengumumen::route('/'),
            'create' => CreateMasterPengumuman::route('/create'),
            'view' => ViewMasterPengumuman::route('/{record}'),
            'edit' => EditMasterPengumuman::route('/{record}/edit'),
        ];
    }
}
