<?php

namespace App\Filament\Resources\NilaiAkademiks;

use App\Filament\Clusters\Akademik;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesQueryToUstadzSantri;
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
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class NilaiAkademikResource extends Resource
{
    use HasAdminUstadzAccess;
    use ScopesQueryToUstadzSantri;

    protected static ?string $model = NilaiAkademik::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $cluster = Akademik::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Nilai';

    public static function getRecordTitle(?Model $record): Htmlable|string|null
    {
        if (! $record) {
            return null;
        }

        return $record->santri?->nama_lengkap ?? 'Nilai Akademik';
    }

    protected static ?string $modelLabel = 'Nilai Akademik';

    protected static ?string $pluralModelLabel = 'Nilai Akademik';

    protected static function ustadzScopeColumn(): string
    {
        return 'mata_pelajaran_id';
    }

    protected static function ustadzScopedIds(): Collection
    {
        return MataPelajaran::where('ustadz_id', Auth::id())->pluck('id');
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
            'index' => ListNilaiAkademik::route('/'),
            'create' => CreateNilaiAkademik::route('/create'),
            'edit' => EditNilaiAkademik::route('/{record}/edit'),
        ];
    }
}
