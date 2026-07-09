<?php

namespace App\Filament\Resources\TahfidzUjian;

use App\Filament\Clusters\Tahfidz;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesQueryToUstadzSantri;
use App\Filament\Resources\TahfidzUjian\Pages\CreateTahfidzUjian;
use App\Filament\Resources\TahfidzUjian\Pages\EditTahfidzUjian;
use App\Filament\Resources\TahfidzUjian\Pages\ListTahfidzUjian;
use App\Filament\Resources\TahfidzUjian\Pages\ViewTahfidzUjian;
use App\Filament\Resources\TahfidzUjian\Schemas\TahfidzUjianForm;
use App\Filament\Resources\TahfidzUjian\Schemas\TahfidzUjianInfolist;
use App\Filament\Resources\TahfidzUjian\Tables\TahfidzUjianTable;
use App\Models\TahfidzUjian;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TahfidzUjianResource extends Resource
{
    use HasAdminUstadzAccess;
    use ScopesQueryToUstadzSantri;

    protected static ?string $model = TahfidzUjian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'nama_santri';

    protected static ?string $slug = 'ujian';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Ujian';

    protected static ?string $modelLabel = 'Ujian';

    protected static ?string $pluralModelLabel = 'Ujian Tahfidz';

    protected static ?string $cluster = Tahfidz::class;

    public static function form(Schema $schema): Schema
    {
        return TahfidzUjianForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TahfidzUjianInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TahfidzUjianTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTahfidzUjian::route('/'),
            'create' => CreateTahfidzUjian::route('/create'),
            'view' => ViewTahfidzUjian::route('/{record}'),
            'edit' => EditTahfidzUjian::route('/{record}/edit'),
        ];
    }
}
