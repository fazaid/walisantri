<?php

namespace App\Filament\Resources\MasterPengumumen;

use App\Enums\UserRole;
use App\Filament\Resources\MasterPengumumen\Pages\CreateMasterPengumuman;
use App\Filament\Resources\MasterPengumumen\Pages\EditMasterPengumuman;
use App\Filament\Resources\MasterPengumumen\Pages\ListMasterPengumumen;
use App\Filament\Resources\MasterPengumumen\Pages\ViewMasterPengumuman;
use App\Filament\Resources\MasterPengumumen\Schemas\MasterPengumumanForm;
use App\Filament\Resources\MasterPengumumen\Schemas\MasterPengumumanInfolist;
use App\Filament\Resources\MasterPengumumen\Tables\MasterPengumumenTable;
use App\Models\MasterPengumuman;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class MasterPengumumanResource extends Resource
{
    protected static ?string $model = MasterPengumuman::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSpeakerWave;

    protected static ?string $recordTitleAttribute = 'judul_maklumat';
    protected static ?string $navigationLabel = 'Pengumuman';
    protected static ?string $modelLabel = 'Pengumuman';
    protected static ?string $pluralModelLabel = 'Pengumuman';

    public static function canAccess(): bool
    {
        $role = auth()->user()?->role;

        return in_array($role, [
            UserRole::SuperAdmin->value,
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ]);
    }

    public static function canViewAny(): bool
    {
        $role = auth()->user()?->role;

        return in_array($role, [
            UserRole::SuperAdmin->value,
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ]);
    }

    // Super admin melihat semua pengumuman lintas tenant.
    // Admin/Ustadz melihat pengumuman pesantrennya + global super admin, target admin/semua.
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if ($user?->role === UserRole::SuperAdmin->value) {
            return parent::getEloquentQuery()->withoutGlobalScopes();
        }

        return parent::getEloquentQuery()
            ->withoutGlobalScopes()
            ->where(function (Builder $query) use ($user) {
                $query->where('pesantren_id', $user->pesantren_id)
                    ->orWhereNull('pesantren_id');
            })
            ->forAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return MasterPengumumanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MasterPengumumanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MasterPengumumenTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListMasterPengumumen::route('/'),
            'create' => CreateMasterPengumuman::route('/create'),
            'view'   => ViewMasterPengumuman::route('/{record}'),
            'edit'   => EditMasterPengumuman::route('/{record}/edit'),
        ];
    }
}
