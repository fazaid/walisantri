<?php

namespace App\Filament\Resources\KesantrianAmalMasters;

use App\Filament\Clusters\Mutabaah;
use App\Filament\Resources\KesantrianAmalMasters\Pages\CreateKesantrianAmalMaster;
use App\Filament\Resources\KesantrianAmalMasters\Pages\EditKesantrianAmalMaster;
use App\Filament\Resources\KesantrianAmalMasters\Pages\ListKesantrianAmalMaster;
use App\Filament\Resources\KesantrianAmalMasters\Schemas\KesantrianAmalMasterForm;
use App\Filament\Resources\KesantrianAmalMasters\Tables\KesantrianAmalMasterTable;
use App\Models\KesantrianAmalMaster;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class KesantrianAmalMasterResource extends Resource
{
    protected static ?string $model = KesantrianAmalMaster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $recordTitleAttribute = 'label';
    protected static ?string $navigationLabel = 'Amal';
    protected static ?string $modelLabel = 'Amal Mutabaah';
    protected static ?string $pluralModelLabel = 'Pengaturan Amal Mutabaah';

    protected static ?string $cluster = Mutabaah::class;
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'amal';

    public static function canViewAny(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return KesantrianAmalMasterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KesantrianAmalMasterTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListKesantrianAmalMaster::route('/'),
            'create' => CreateKesantrianAmalMaster::route('/create'),
            'edit'   => EditKesantrianAmalMaster::route('/{record}/edit'),
        ];
    }
}
