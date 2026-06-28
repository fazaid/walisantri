<?php

namespace App\Filament\Resources\NilaiAkademiks;

use App\Filament\Clusters\Akademik;
use App\Filament\Resources\NilaiAkademiks\Pages\CreateNilaiAkademik;
use App\Filament\Resources\NilaiAkademiks\Pages\EditNilaiAkademik;
use App\Filament\Resources\NilaiAkademiks\Pages\ListNilaiAkademik;
use App\Filament\Resources\NilaiAkademiks\Schemas\NilaiAkademikForm;
use App\Filament\Resources\NilaiAkademiks\Tables\NilaiAkademikTable;
use App\Models\MataPelajaran;
use App\Models\NilaiAkademik;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class NilaiAkademikResource extends Resource
{
    protected static ?string $model = NilaiAkademik::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $cluster = Akademik::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';
    protected static ?string $navigationLabel = 'Nilai';

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): \Illuminate\Contracts\Support\Htmlable|string|null
    {
        if (! $record) {
            return null;
        }
        return $record->santri?->nama_lengkap ?? 'Nilai Akademik';
    }
    protected static ?string $modelLabel = 'Nilai Akademik';
    protected static ?string $pluralModelLabel = 'Nilai Akademik';

    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']);
    }

    public static function canCreate(): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']);
    }

    public static function canEdit($record): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']);
    }

    public static function canDelete($record): bool
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
            $mapelIds = MataPelajaran::where('ustadz_id', Auth::id())->pluck('id');
            $query->whereIn('mata_pelajaran_id', $mapelIds);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return NilaiAkademikForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NilaiAkademikTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListNilaiAkademik::route('/'),
            'create' => CreateNilaiAkademik::route('/create'),
            'edit'   => EditNilaiAkademik::route('/{record}/edit'),
        ];
    }
}
