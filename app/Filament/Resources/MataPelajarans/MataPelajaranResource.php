<?php

namespace App\Filament\Resources\MataPelajarans;

use App\Filament\Clusters\Akademik;
use App\Filament\Concerns\HasAdminOnlyAccess;
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

class MataPelajaranResource extends Resource
{
    use HasAdminOnlyAccess;

    protected static ?string $model = MataPelajaran::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $cluster = Akademik::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'nama_mapel';

    protected static ?string $navigationLabel = 'Pelajaran';

    protected static ?string $modelLabel = 'Mata Pelajaran';

    protected static ?string $pluralModelLabel = 'Mata Pelajaran';

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
            'index' => ListMataPelajaran::route('/'),
            'create' => CreateMataPelajaran::route('/create'),
            'edit' => EditMataPelajaran::route('/{record}/edit'),
        ];
    }
}
