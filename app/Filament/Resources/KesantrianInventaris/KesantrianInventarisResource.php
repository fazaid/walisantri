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
use App\Models\Santri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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


    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, [
            'admin_pesantren',
            'ustadz',
        ]);
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
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
