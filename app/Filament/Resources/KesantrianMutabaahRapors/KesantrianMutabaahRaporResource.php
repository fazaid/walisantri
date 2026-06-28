<?php

namespace App\Filament\Resources\KesantrianMutabaahRapors;

use App\Filament\Clusters\Rapor;
use App\Filament\Resources\KesantrianMutabaahRapors\Pages\CreateKesantrianMutabaahRapor;
use App\Filament\Resources\KesantrianMutabaahRapors\Pages\ListKesantrianMutabaahRapors;
use App\Filament\Resources\KesantrianMutabaahRapors\Pages\ViewKesantrianMutabaahRapor;
use App\Filament\Resources\KesantrianMutabaahRapors\Schemas\KesantrianMutabaahRaporForm;
use App\Filament\Resources\KesantrianMutabaahRapors\Schemas\KesantrianMutabaahRaporInfolist;
use App\Filament\Resources\KesantrianMutabaahRapors\Tables\KesantrianMutabaahRaporsTable;
use App\Models\KesantrianMutabaahRapor;
use App\Models\Santri;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class KesantrianMutabaahRaporResource extends Resource
{
    protected static ?string $model = KesantrianMutabaahRapor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'santri_id';
    protected static ?string $navigationLabel = 'Mutabaah';
    protected static ?string $modelLabel = 'Rapor Mutabaah';
    protected static ?string $pluralModelLabel = 'Rapor Mutabaah';
    protected static ?string $cluster = Rapor::class;
    protected static ?int $navigationSort = 1;

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        if (! $record) return null;
        return $record->santri?->nama_lengkap ?? 'Rapor Mutabaah';
    }

    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']);
    }

    public static function canCreate(): bool
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
        return KesantrianMutabaahRaporForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KesantrianMutabaahRaporInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KesantrianMutabaahRaporsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListKesantrianMutabaahRapors::route('/'),
            'create' => CreateKesantrianMutabaahRapor::route('/create'),
            'view'   => ViewKesantrianMutabaahRapor::route('/{record}'),
        ];
    }
}
