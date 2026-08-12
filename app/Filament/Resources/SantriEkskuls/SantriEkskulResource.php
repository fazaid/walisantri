<?php

namespace App\Filament\Resources\SantriEkskuls;

use App\Filament\Clusters\Akademik;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesQueryToUstadzSantri;
use App\Filament\Resources\SantriEkskuls\Pages\ListSantriEkskuls;
use App\Filament\Resources\SantriEkskuls\Pages\ViewSantriEkskul;
use App\Filament\Resources\SantriEkskuls\Schemas\SantriEkskulForm;
use App\Filament\Resources\SantriEkskuls\Schemas\SantriEkskulInfolist;
use App\Filament\Resources\SantriEkskuls\Tables\SantriEkskulsTable;
use App\Models\SantriEkskul;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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

    /**
     * Cegah satu santri terdaftar dua kali di ekskul yang sama.
     *
     * Dulu tinggal di CreateSantriEkskul::beforeCreate() dan
     * EditSantriEkskul::beforeSave(). Sejak tambah/edit jadi modal, kedua
     * halaman itu hilang — closure ini dipasang lewat ->before() pada
     * Create/EditAction. $record null saat membuat, terisi saat mengubah.
     */
    public static function guardDuplikat(): Closure
    {
        return function (array $data, Action $action, ?Model $record): void {
            $exists = SantriEkskul::where('santri_id', $data['santri_id'])
                ->where('ekskul_id', $data['ekskul_id'])
                ->when($record, fn ($query) => $query->where('id', '!=', $record->getKey()))
                ->exists();

            if (! $exists) {
                return;
            }

            Notification::make()
                ->title('Santri ini sudah terdaftar di ekskul yang dipilih.')
                ->danger()
                ->send();

            $action->halt();
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSantriEkskuls::route('/'),
            'view' => ViewSantriEkskul::route('/{record}'),
        ];
    }
}
