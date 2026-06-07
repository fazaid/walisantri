<?php

namespace App\Filament\Resources\MataPelajarans;

use App\Filament\Resources\MataPelajarans\Pages\CreateMataPelajaran;
use App\Filament\Resources\MataPelajarans\Pages\EditMataPelajaran;
use App\Filament\Resources\MataPelajarans\Pages\ListMataPelajaran;
use App\Filament\Resources\MataPelajarans\Schemas\MataPelajaranForm;
use App\Filament\Resources\MataPelajarans\Tables\MataPelajaranTable;
use App\Models\MataPelajaran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MataPelajaranResource extends Resource
{
    protected static ?string $model = MataPelajaran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama_mapel';
    protected static ?string $navigationLabel = 'Mata Pelajaran';
    protected static ?string $modelLabel = 'Mata Pelajaran';
    protected static ?string $pluralModelLabel = 'Mata Pelajaran';

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
        return Auth::user()?->role === 'admin_pesantren';
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public static function form(Schema $schema): Schema
    {
        return MataPelajaranForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MataPelajaranTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListMataPelajaran::route('/'),
            'create' => CreateMataPelajaran::route('/create'),
            'edit'   => EditMataPelajaran::route('/{record}/edit'),
        ];
    }
}
