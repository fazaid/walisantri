<?php

namespace App\Filament\Resources\TahfidzProgress;

use App\Filament\Resources\TahfidzProgress\Pages\CreateTahfidzProgress;
use App\Filament\Resources\TahfidzProgress\Pages\EditTahfidzProgress;
use App\Filament\Resources\TahfidzProgress\Pages\ListTahfidzProgress;
use App\Filament\Resources\TahfidzProgress\Pages\ViewTahfidzProgress;
use App\Filament\Resources\TahfidzProgress\Schemas\TahfidzProgressForm;
use App\Filament\Resources\TahfidzProgress\Schemas\TahfidzProgressInfolist;
use App\Filament\Resources\TahfidzProgress\Tables\TahfidzProgressTable;
use App\Models\TahfidzProgress;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use BackedEnum;
use UnitEnum;

class TahfidzProgressResource extends Resource
{
    protected static ?string $model = TahfidzProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $recordTitleAttribute = 'nama_surah';
    protected static ?string $navigationLabel = 'Setoran Tahfidz';
    protected static ?string $modelLabel = 'Setoran';
    protected static ?string $pluralModelLabel = 'Setoran Tahfidz';

    protected static string|UnitEnum|null $navigationGroup = 'Akademik';
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
        return TahfidzProgressForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TahfidzProgressInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TahfidzProgressTable::configure($table);
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
            'index' => ListTahfidzProgress::route('/'),
            'create' => CreateTahfidzProgress::route('/create'),
            'view' => ViewTahfidzProgress::route('/{record}'),
            'edit' => EditTahfidzProgress::route('/{record}/edit'),
        ];
    }
}
