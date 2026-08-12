<?php

namespace App\Filament\Resources\SantriEkskuls;

use App\Filament\Clusters\Akademik;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesQueryToUstadzSantri;
use App\Filament\Resources\SantriEkskuls\Pages\CreateSantriEkskul;
use App\Filament\Resources\SantriEkskuls\Pages\EditSantriEkskul;
use App\Filament\Resources\SantriEkskuls\Pages\ListSantriEkskuls;
use App\Filament\Resources\SantriEkskuls\Pages\ViewSantriEkskul;
use App\Filament\Resources\SantriEkskuls\Schemas\SantriEkskulForm;
use App\Filament\Resources\SantriEkskuls\Schemas\SantriEkskulInfolist;
use App\Filament\Resources\SantriEkskuls\Tables\SantriEkskulsTable;
use App\Models\SantriEkskul;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class SantriEkskulResource extends Resource
{
    use HasAdminUstadzAccess;
    use ScopesQueryToUstadzSantri;

    protected static ?string $model = SantriEkskul::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $cluster = Akademik::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'id';

    // Label menu diringkas; $modelLabel sengaja tetap "Ekskul Santri" supaya
    // tombol "Tambah Ekskul Santri" tidak rancu dengan "Kelola Ekskul" yang
    // ada di sebelahnya.
    protected static ?string $navigationLabel = 'Ekskul';

    public static function getRecordTitle(?Model $record): Htmlable|string|null
    {
        if (! $record) {
            return null;
        }

        return $record->santri?->nama_lengkap ?? 'Ekskul Santri';
    }

    protected static ?string $modelLabel = 'Ekskul Santri';

    protected static ?string $pluralModelLabel = 'Ekskul Santri';

    public static function form(Schema $schema): Schema
    {
        return SantriEkskulForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SantriEkskulInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SantriEkskulsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSantriEkskuls::route('/'),
            'create' => CreateSantriEkskul::route('/create'),
            'view' => ViewSantriEkskul::route('/{record}'),
            'edit' => EditSantriEkskul::route('/{record}/edit'),
        ];
    }
}
