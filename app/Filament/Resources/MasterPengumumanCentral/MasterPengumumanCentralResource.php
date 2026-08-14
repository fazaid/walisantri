<?php

namespace App\Filament\Resources\MasterPengumumanCentral;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Resources\MasterPengumumanCentral\Pages\ListMasterPengumumanCentral;
use App\Filament\Resources\MasterPengumumanCentral\Pages\ViewMasterPengumumanCentral;
use App\Filament\Resources\MasterPengumumanCentral\Schemas\MasterPengumumanCentralForm;
use App\Filament\Resources\MasterPengumumanCentral\Schemas\MasterPengumumanCentralInfolist;
use App\Filament\Resources\MasterPengumumanCentral\Tables\MasterPengumumanCentralTable;
use App\Models\MasterPengumumanCentral;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class MasterPengumumanCentralResource extends Resource
{
    protected static ?string $model = MasterPengumumanCentral::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Platform;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'judul_maklumat';

    protected static ?string $navigationLabel = 'Pengumuman Central';

    protected static ?string $modelLabel = 'Pengumuman Central';

    protected static ?string $pluralModelLabel = 'Pengumuman Central';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDeleteAny(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return MasterPengumumanCentralForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MasterPengumumanCentralInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterPengumumanCentralTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMasterPengumumanCentral::route('/'),
            'view' => ViewMasterPengumumanCentral::route('/{record}'),
        ];
    }
}
