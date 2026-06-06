<?php

namespace App\Filament\Resources\KesantrianMutabaahs;

use App\Filament\Resources\KesantrianMutabaahs\Pages\CreateKesantrianMutabaah;
use App\Filament\Resources\KesantrianMutabaahs\Pages\EditKesantrianMutabaah;
use App\Filament\Resources\KesantrianMutabaahs\Pages\ListKesantrianMutabaahs;
use App\Filament\Resources\KesantrianMutabaahs\Pages\ViewKesantrianMutabaah;
use App\Filament\Resources\KesantrianMutabaahs\Schemas\KesantrianMutabaahForm;
use App\Filament\Resources\KesantrianMutabaahs\Schemas\KesantrianMutabaahInfolist;
use App\Filament\Resources\KesantrianMutabaahs\Tables\KesantrianMutabaahsTable;
use App\Models\KesantrianMutabaah;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use UnitEnum;

class KesantrianMutabaahResource extends Resource
{
    protected static ?string $model = KesantrianMutabaah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'tanggal';
    protected static ?string $navigationLabel = 'Mutabaah';
    protected static ?string $modelLabel = 'Mutabaah';
    protected static ?string $pluralModelLabel = 'Data Mutabaah';

    protected static string|UnitEnum|null $navigationGroup = 'Kesantrian';
    protected static ?int $navigationSort = 4;


    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, [
            'admin_pesantren',
            'ustadz',
        ]);
    }

    public static function canCreate(): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']);
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (Auth::user()?->role === 'ustadz') {
            $santriIds = Santri::where('pembimbing_ustadz_id', Auth::id())->pluck('id');
            $query->whereIn('santri_id', $santriIds);
        }
        return $query;
    }

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
