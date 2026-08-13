<?php

namespace App\Filament\Resources\KesantrianMutabaahs;

use App\Filament\Clusters\Mutabaah;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesRouteBindingToUstadzSantri;
use App\Filament\Resources\KesantrianMutabaahs\Pages\ListKesantrianMutabaahs;
use App\Filament\Resources\KesantrianMutabaahs\Pages\ViewKesantrianMutabaah;
use App\Filament\Resources\KesantrianMutabaahs\Schemas\KesantrianMutabaahForm;
use App\Filament\Resources\KesantrianMutabaahs\Schemas\KesantrianMutabaahInfolist;
use App\Filament\Resources\KesantrianMutabaahs\Tables\KesantrianMutabaahsTable;
use App\Models\KesantrianMutabaah;
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

class KesantrianMutabaahResource extends Resource
{
    use HasAdminUstadzAccess;
    use ScopesRouteBindingToUstadzSantri;

    protected static ?string $model = KesantrianMutabaah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'tanggal';

    protected static ?string $navigationLabel = 'Mutabaah';

    public static function getRecordTitle(?Model $record): Htmlable|string|null
    {
        if (! $record) {
            return null;
        }

        return $record->santri?->nama_lengkap ?? 'Mutabaah';
    }

    protected static ?string $modelLabel = 'Mutabaah';

    protected static ?string $pluralModelLabel = 'Data Mutabaah';

    protected static ?string $cluster = Mutabaah::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'mutabaah';

    public static function form(Schema $schema): Schema
    {
        return KesantrianMutabaahForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KesantrianMutabaahInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KesantrianMutabaahsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Satu santri hanya punya satu mutaba'ah per tanggal, jadi menambah data
     * untuk tanggal yang sudah terisi diperlakukan sebagai memperbarui — dulu
     * di handleRecordCreation() halaman Create, kini menumpang CreateAction.
     */
    public static function simpanAtauPerbarui(): Closure
    {
        return function (array $data): Model {
            // whereDate(), bukan where(): kolomnya date sehingga di Postgres
            // perbandingan langsung masih cocok, tapi tidak di SQLite yang
            // menyimpan '00:00:00' ikut serta.
            $lama = KesantrianMutabaah::where('santri_id', $data['santri_id'])
                ->whereDate('tanggal', $data['tanggal'])
                ->first();

            $record = $lama
                ? tap($lama)->update($data)
                : KesantrianMutabaah::create($data);

            if ($lama) {
                Notification::make()
                    ->title('Data diperbarui, bukan ditambah baru')
                    ->body('Mutaba\'ah santri ini untuk tanggal tersebut sudah ada sebelumnya, sehingga data lama diperbarui dengan isian ini.')
                    ->warning()
                    ->send();
            }

            return $record;
        };
    }

    /**
     * Saat mengubah, pindah ke tanggal milik baris lain harus ditolak — dulu
     * beforeSave() halaman Edit, kini menumpang ->before() EditAction.
     */
    public static function guardTanggalBentrok(): Closure
    {
        return function (array $data, Action $action, ?Model $record): void {
            $bentrok = KesantrianMutabaah::where('santri_id', $data['santri_id'])
                ->whereDate('tanggal', $data['tanggal'])
                ->when($record, fn ($query) => $query->where('id', '!=', $record->getKey()))
                ->exists();

            if (! $bentrok) {
                return;
            }

            Notification::make()
                ->title('Tanggal bentrok')
                ->body('Sudah ada mutaba\'ah untuk santri dan tanggal tersebut. Ubah tanggal atau santri terlebih dahulu.')
                ->danger()
                ->send();

            $action->halt();
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKesantrianMutabaahs::route('/'),
            'view' => ViewKesantrianMutabaah::route('/{record}'),
        ];
    }
}
