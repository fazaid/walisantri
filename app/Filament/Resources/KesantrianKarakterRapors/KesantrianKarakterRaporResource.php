<?php

namespace App\Filament\Resources\KesantrianKarakterRapors;

use App\Filament\Clusters\Kesantrian;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesRouteBindingToUstadzSantri;
use App\Filament\Resources\KesantrianKarakterRapors\Pages\ListKesantrianKarakterRapors;
use App\Filament\Resources\KesantrianKarakterRapors\Pages\ViewKesantrianKarakterRapor;
use App\Filament\Resources\KesantrianKarakterRapors\Schemas\KesantrianKarakterRaporForm;
use App\Filament\Resources\KesantrianKarakterRapors\Schemas\KesantrianKarakterRaporInfolist;
use App\Filament\Resources\KesantrianKarakterRapors\Tables\KesantrianKarakterRaporsTable;
use App\Models\KesantrianKarakterRapor;
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

class KesantrianKarakterRaporResource extends Resource
{
    use HasAdminUstadzAccess;
    use ScopesRouteBindingToUstadzSantri;

    protected static ?string $model = KesantrianKarakterRapor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $recordTitleAttribute = 'santri.nama_lengkap';

    protected static ?string $navigationLabel = 'Karakter';

    public static function getRecordTitle(?Model $record): Htmlable|string|null
    {
        if (! $record) {
            return null;
        }

        return $record->santri?->nama_lengkap ?? 'Karakter';
    }

    protected static ?string $modelLabel = 'Karakter';

    protected static ?string $pluralModelLabel = 'Data Karakter';

    protected static ?string $cluster = Kesantrian::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'karakter';

    public static function form(Schema $schema): Schema
    {
        return KesantrianKarakterRaporForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KesantrianKarakterRaporInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KesantrianKarakterRaporsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Satu santri hanya boleh punya satu rapor karakter per periode — dulu
     * beforeCreate()/beforeSave() halaman Create dan Edit, kini menumpang
     * ->before() pada action. $record null saat membuat, terisi saat mengubah.
     */
    public static function guardDuplikat(): Closure
    {
        return function (array $data, Action $action, ?Model $record): void {
            $exists = KesantrianKarakterRapor::where('santri_id', $data['santri_id'])
                ->where('tahun_ajaran', $data['tahun_ajaran'])
                ->where('periode', $data['periode'])
                ->where('bulan', $data['bulan'] ?? null)
                ->when($record, fn ($query) => $query->where('id', '!=', $record->getKey()))
                ->exists();

            if (! $exists) {
                return;
            }

            Notification::make()
                ->title('Data sudah ada')
                ->body('Rapor karakter santri ini untuk periode tersebut sudah pernah diinput.')
                ->danger()
                ->send();

            $action->halt();
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKesantrianKarakterRapors::route('/'),
            'view' => ViewKesantrianKarakterRapor::route('/{record}'),
        ];
    }
}
