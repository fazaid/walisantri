<?php

namespace App\Filament\Resources\KesantrianKesehatans;

use App\Filament\Clusters\Kesantrian;
use App\Filament\Resources\KesantrianKesehatans\Pages\CreateKesantrianKesehatan;
use App\Filament\Resources\KesantrianKesehatans\Pages\EditKesantrianKesehatan;
use App\Filament\Resources\KesantrianKesehatans\Pages\ListKesantrianKesehatans;
use App\Filament\Resources\KesantrianKesehatans\Pages\ViewKesantrianKesehatan;
use App\Filament\Resources\KesantrianKesehatans\Schemas\KesantrianKesehatanForm;
use App\Filament\Resources\KesantrianKesehatans\Schemas\KesantrianKesehatanInfolist;
use App\Filament\Resources\KesantrianKesehatans\Tables\KesantrianKesehatansTable;
use App\Models\KesantrianKesehatan;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use Illuminate\Support\Facades\Gate;

class KesantrianKesehatanResource extends Resource
{
    protected static ?string $model = KesantrianKesehatan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $recordTitleAttribute = 'kategori_keluhan';
    protected static ?string $navigationLabel = 'Kesehatan';
    protected static ?string $modelLabel = 'Rekam Medis';
    protected static ?string $pluralModelLabel = 'Rekam Medis';

    protected static ?string $cluster = Kesantrian::class;
    protected static ?int $navigationSort = 2;


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

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, [
            'admin_pesantren',
            'ustadz',
        ]);
    }
}
