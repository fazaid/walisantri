<?php

namespace App\Filament\Resources\KesantrianMutabaahs;

use App\Filament\Clusters\Mutabaah;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesRouteBindingToUstadzSantri;
use App\Filament\Resources\KesantrianMutabaahs\Pages\CreateKesantrianMutabaah;
use App\Filament\Resources\KesantrianMutabaahs\Pages\EditKesantrianMutabaah;
use App\Filament\Resources\KesantrianMutabaahs\Pages\ListKesantrianMutabaahs;
use App\Filament\Resources\KesantrianMutabaahs\Pages\ViewKesantrianMutabaah;
use App\Filament\Resources\KesantrianMutabaahs\Schemas\KesantrianMutabaahForm;
use App\Filament\Resources\KesantrianMutabaahs\Schemas\KesantrianMutabaahInfolist;
use App\Filament\Resources\KesantrianMutabaahs\Tables\KesantrianMutabaahsTable;
use App\Models\KesantrianMutabaah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class KesantrianMutabaahResource extends Resource
{
    use HasAdminUstadzAccess;
    use ScopesRouteBindingToUstadzSantri;

    protected static ?string $model = KesantrianMutabaah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'tanggal';

    protected static ?string $navigationLabel = 'Mutabaah';

    public static function getRecordTitle(?Model $record): Htmlable|string|null
    {
        if (! $record) {
            return null;
        }

        return $record->santri?->nama_lengkap ?? 'Mutabaah';
    }

    protected static ?string $modelLabel = 'Mutabaah';

    protected static ?string $pluralModelLabel = 'Data Mutabaah';

    protected static ?string $cluster = Mutabaah::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'mutabaah';

    public static function form(Schema $schema): Schema
    {
        return KesantrianMutabaahForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KesantrianMutabaahInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KesantrianMutabaahsTable::configure($table);
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
            'index' => ListKesantrianMutabaahs::route('/'),
            'create' => CreateKesantrianMutabaah::route('/create'),
            'view' => ViewKesantrianMutabaah::route('/{record}'),
            'edit' => EditKesantrianMutabaah::route('/{record}/edit'),
        ];
    }
}
