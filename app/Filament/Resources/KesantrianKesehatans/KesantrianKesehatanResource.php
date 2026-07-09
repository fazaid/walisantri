<?php

namespace App\Filament\Resources\KesantrianKesehatans;

use App\Filament\Clusters\Kesantrian;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesRouteBindingToUstadzSantri;
use App\Filament\Resources\KesantrianKesehatans\Pages\CreateKesantrianKesehatan;
use App\Filament\Resources\KesantrianKesehatans\Pages\EditKesantrianKesehatan;
use App\Filament\Resources\KesantrianKesehatans\Pages\ListKesantrianKesehatans;
use App\Filament\Resources\KesantrianKesehatans\Pages\ViewKesantrianKesehatan;
use App\Filament\Resources\KesantrianKesehatans\Schemas\KesantrianKesehatanForm;
use App\Filament\Resources\KesantrianKesehatans\Schemas\KesantrianKesehatanInfolist;
use App\Filament\Resources\KesantrianKesehatans\Tables\KesantrianKesehatansTable;
use App\Models\KesantrianKesehatan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class KesantrianKesehatanResource extends Resource
{
    use HasAdminUstadzAccess;
    use ScopesRouteBindingToUstadzSantri;

    protected static ?string $model = KesantrianKesehatan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $recordTitleAttribute = 'kategori_keluhan';

    protected static ?string $navigationLabel = 'Kesehatan';

    public static function getRecordTitle(?Model $record): Htmlable|string|null
    {
        if (! $record) {
            return null;
        }

        return $record->santri?->nama_lengkap ?? 'Rekam Medis';
    }

    protected static ?string $modelLabel = 'Rekam Medis';

    protected static ?string $pluralModelLabel = 'Rekam Medis';

    protected static ?string $cluster = Kesantrian::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'kesehatan';

    public static function form(Schema $schema): Schema
    {
        return KesantrianKesehatanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KesantrianKesehatanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KesantrianKesehatansTable::configure($table);
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
            'index' => ListKesantrianKesehatans::route('/'),
            'create' => CreateKesantrianKesehatan::route('/create'),
            'view' => ViewKesantrianKesehatan::route('/{record}'),
            'edit' => EditKesantrianKesehatan::route('/{record}/edit'),
        ];
    }
}
