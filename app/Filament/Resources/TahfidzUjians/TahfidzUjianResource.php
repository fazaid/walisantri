<?php

namespace App\Filament\Resources\TahfidzUjians;

use App\Filament\Resources\TahfidzUjians\Pages\CreateTahfidzUjian;
use App\Filament\Resources\TahfidzUjians\Pages\EditTahfidzUjian;
use App\Filament\Resources\TahfidzUjians\Pages\ListTahfidzUjians;
use App\Filament\Resources\TahfidzUjians\Pages\ViewTahfidzUjian;
use App\Filament\Resources\TahfidzUjians\Schemas\TahfidzUjianForm;
use App\Filament\Resources\TahfidzUjians\Schemas\TahfidzUjianInfolist;
use App\Filament\Resources\TahfidzUjians\Tables\TahfidzUjiansTable;
use App\Filament\Clusters\Tahfidz;
use App\Models\TahfidzUjian;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use Illuminate\Support\Facades\Gate;

class TahfidzUjianResource extends Resource
{
    protected static ?string $model = TahfidzUjian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'target_juz';
    protected static ?string $navigationLabel = 'Ujian';
    protected static ?string $modelLabel = 'Ujian';
    protected static ?string $pluralModelLabel = 'Ujian Tahfidz';

    protected static ?string $cluster = Tahfidz::class;


    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, [
            'admin_pesantren',
            'ustadz',
        ]);
    }
    public static function canAccess(): bool
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
        return TahfidzUjianForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TahfidzUjianInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TahfidzUjiansTable::configure($table);
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
            'index' => ListTahfidzUjians::route('/'),
            'create' => CreateTahfidzUjian::route('/create'),
            'view' => ViewTahfidzUjian::route('/{record}'),
            'edit' => EditTahfidzUjian::route('/{record}/edit'),
        ];
    }
}
