<?php

namespace App\Filament\Resources\KesantrianKarakterRapors;

use App\Filament\Resources\KesantrianKarakterRapors\Pages\CreateKesantrianKarakterRapor;
use App\Filament\Resources\KesantrianKarakterRapors\Pages\EditKesantrianKarakterRapor;
use App\Filament\Resources\KesantrianKarakterRapors\Pages\ListKesantrianKarakterRapors;
use App\Filament\Resources\KesantrianKarakterRapors\Pages\ViewKesantrianKarakterRapor;
use App\Filament\Resources\KesantrianKarakterRapors\Schemas\KesantrianKarakterRaporForm;
use App\Filament\Resources\KesantrianKarakterRapors\Schemas\KesantrianKarakterRaporInfolist;
use App\Filament\Resources\KesantrianKarakterRapors\Tables\KesantrianKarakterRaporsTable;
use App\Models\KesantrianKarakterRapor;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use UnitEnum;

class KesantrianKarakterRaporResource extends Resource
{
    protected static ?string $model = KesantrianKarakterRapor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $recordTitleAttribute = 'tanggal_input';
    protected static ?string $navigationLabel = 'Karakter Rapor';
    protected static ?string $modelLabel = 'Karakter Rapor';
    protected static ?string $pluralModelLabel = 'Karakter Rapor';

    protected static string|UnitEnum|null $navigationGroup = 'Kesantrian';
    protected static ?int $navigationSort = 5;


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
        return KesantrianKarakterRaporForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KesantrianKarakterRaporInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KesantrianKarakterRaporsTable::configure($table);
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
            'index' => ListKesantrianKarakterRapors::route('/'),
            'create' => CreateKesantrianKarakterRapor::route('/create'),
            'view' => ViewKesantrianKarakterRapor::route('/{record}'),
            'edit' => EditKesantrianKarakterRapor::route('/{record}/edit'),
        ];
    }
}
