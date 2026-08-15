<?php

namespace App\Filament\Resources\Presensis;

use App\Filament\Clusters\Presensi as ClusterPresensi;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesQueryToPresensiUstadz;
use App\Filament\Resources\Presensis\Pages\ListPresensis;
use App\Filament\Resources\Presensis\Pages\ViewPresensi;
use App\Filament\Resources\Presensis\Schemas\PresensiForm;
use App\Filament\Resources\Presensis\Schemas\PresensiInfolist;
use App\Filament\Resources\Presensis\Tables\PresensisTable;
use App\Models\Presensi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class PresensiResource extends Resource
{
    use HasAdminUstadzAccess;
    use ScopesQueryToPresensiUstadz;

    protected static ?string $model = Presensi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $cluster = ClusterPresensi::class;

    protected static ?string $navigationLabel = 'Kehadiran';

    protected static ?string $modelLabel = 'Kehadiran';

    protected static ?string $pluralModelLabel = 'Data Kehadiran';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'kehadiran';

    protected static ?string $recordTitleAttribute = 'tanggal';

    public static function getRecordTitle(?Model $record): Htmlable|string|null
    {
        if (! $record) {
            return null;
        }

        return $record->santri?->nama_lengkap ?? 'Kehadiran';
    }

    public static function form(Schema $schema): Schema
    {
        return PresensiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PresensiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PresensisTable::configure($table);
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
            'index' => ListPresensis::route('/'),
            'view' => ViewPresensi::route('/{record}'),
        ];
    }
}
