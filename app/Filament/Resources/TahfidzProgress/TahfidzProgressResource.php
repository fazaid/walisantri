<?php

namespace App\Filament\Resources\TahfidzProgress;

use App\Filament\Clusters\Tahfidz;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesQueryToUstadzSantri;
use App\Filament\Resources\TahfidzProgress\Pages\CreateTahfidzProgress;
use App\Filament\Resources\TahfidzProgress\Pages\EditTahfidzProgress;
use App\Filament\Resources\TahfidzProgress\Pages\ListTahfidzProgress;
use App\Filament\Resources\TahfidzProgress\Pages\ViewTahfidzProgress;
use App\Filament\Resources\TahfidzProgress\Schemas\TahfidzProgressForm;
use App\Filament\Resources\TahfidzProgress\Schemas\TahfidzProgressInfolist;
use App\Filament\Resources\TahfidzProgress\Tables\TahfidzProgressTable;
use App\Models\TahfidzProgress;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TahfidzProgressResource extends Resource
{
    use HasAdminUstadzAccess;
    use ScopesQueryToUstadzSantri;

    protected static ?string $model = TahfidzProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'nama_santri';

    protected static ?string $slug = 'setoran';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Setoran';

    protected static ?string $modelLabel = 'Setoran';

    protected static ?string $pluralModelLabel = 'Setoran Tahfidz';

    protected static ?string $cluster = Tahfidz::class;

    public static function form(Schema $schema): Schema
    {
        return TahfidzProgressForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TahfidzProgressInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TahfidzProgressTable::configure($table);
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
            'index' => ListTahfidzProgress::route('/'),
            'create' => CreateTahfidzProgress::route('/create'),
            'view' => ViewTahfidzProgress::route('/{record}'),
            'edit' => EditTahfidzProgress::route('/{record}/edit'),
        ];
    }
}
