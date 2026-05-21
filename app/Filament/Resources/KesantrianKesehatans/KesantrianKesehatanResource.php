<?php

namespace App\Filament\Resources\KesantrianKesehatans;

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
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class KesantrianKesehatanResource extends Resource
{
    protected static ?string $model = KesantrianKesehatan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $recordTitleAttribute = 'kategori_keluhan';
    protected static ?string $navigationLabel = 'Kesehatan';
    protected static ?string $modelLabel = 'Rekam Medis';
    protected static ?string $pluralModelLabel = 'Rekam Medis';

    protected static string|UnitEnum|null $navigationGroup = 'Kesantrian';
    protected static ?int $navigationSort = 6;


    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, [
            'admin_pesantren',
            'ustadz',
        ]);
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
