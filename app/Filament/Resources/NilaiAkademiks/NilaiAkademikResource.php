<?php

namespace App\Filament\Resources\NilaiAkademiks;

use App\Filament\Clusters\Akademik;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesQueryToUstadzSantri;
use App\Filament\Resources\NilaiAkademiks\Pages\ListNilaiAkademik;
use App\Filament\Resources\NilaiAkademiks\Pages\ViewNilaiAkademik;
use App\Filament\Resources\NilaiAkademiks\Schemas\NilaiAkademikForm;
use App\Filament\Resources\NilaiAkademiks\Schemas\NilaiAkademikInfolist;
use App\Filament\Resources\NilaiAkademiks\Tables\NilaiAkademikTable;
use App\Models\NilaiAkademik;
use App\Support\PenugasanUstadz;
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
use Illuminate\Support\Collection;

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
        return PenugasanUstadz::mataPelajaranIdsDiampu();
    }

    public static function form(Schema $schema): Schema
    {
        return NilaiAkademikForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NilaiAkademikInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NilaiAkademikTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Cegah nilai ganda untuk kombinasi santri + mapel + periode yang sama.
     *
     * Dulu tinggal di CreateNilaiAkademik::beforeCreate() dan
     * EditNilaiAkademik::beforeSave(). Sejak tambah/edit jadi modal, kedua
     * halaman itu hilang — closure ini dipasang lewat ->before() pada
     * Create/EditAction. $record null saat membuat, terisi saat mengubah.
     */
    public static function guardDuplikat(): Closure
    {
        return function (array $data, Action $action, ?Model $record): void {
            $exists = NilaiAkademik::where('santri_id', $data['santri_id'])
                ->where('mata_pelajaran_id', $data['mata_pelajaran_id'])
                ->where('tahun_ajaran', $data['tahun_ajaran'])
                ->where('periode', $data['periode'])
                ->where('bulan', $data['bulan'] ?? null)
                ->when($record, fn ($query) => $query->where('id', '!=', $record->getKey()))
                ->exists();

            if (! $exists) {
                return;
            }

            Notification::make()
                ->title('Nilai untuk santri, mata pelajaran, dan periode ini sudah ada.')
                ->danger()
                ->send();

            $action->halt();
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNilaiAkademik::route('/'),
            'view' => ViewNilaiAkademik::route('/{record}'),
        ];
    }
}
