<?php

namespace App\Filament\Resources\TahfidzRapors;

use App\Filament\Resources\TahfidzRapors\Pages\CreateTahfidzRapor;
use App\Filament\Resources\TahfidzRapors\Pages\EditTahfidzRapor;
use App\Filament\Resources\TahfidzRapors\Pages\ListTahfidzRapors;
use App\Filament\Resources\TahfidzRapors\Pages\ViewTahfidzRapor;
use App\Filament\Resources\TahfidzRapors\Schemas\TahfidzRaporForm;
use App\Filament\Resources\TahfidzRapors\Schemas\TahfidzRaporInfolist;
use App\Filament\Resources\TahfidzRapors\Tables\TahfidzRaporsTable;
use App\Filament\Clusters\Tahfidz;
use App\Models\TahfidzRapor;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use BackedEnum;

class TahfidzRaporResource extends Resource
{
    protected static ?string $model = TahfidzRapor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'tahun_ajaran';
    protected static ?string $navigationLabel = 'Nilai';
    protected static ?string $modelLabel = 'Nilai Tahfidz';
    protected static ?string $pluralModelLabel = 'Nilai Tahfidz';

    protected static ?string $cluster = Tahfidz::class;


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
        return TahfidzRaporForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TahfidzRaporInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TahfidzRaporsTable::configure($table);
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
            'index' => ListTahfidzRapors::route('/'),
            'create' => CreateTahfidzRapor::route('/create'),
            'view' => ViewTahfidzRapor::route('/{record}'),
            'edit' => EditTahfidzRapor::route('/{record}/edit'),
        ];
    }
}
