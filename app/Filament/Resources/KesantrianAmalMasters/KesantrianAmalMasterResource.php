<?php

namespace App\Filament\Resources\KesantrianAmalMasters;

use App\Filament\Clusters\Kesantrian;
use App\Filament\Resources\KesantrianAmalMasters\Pages\ListKesantrianAmalMaster;
use App\Filament\Resources\KesantrianAmalMasters\Schemas\KesantrianAmalMasterForm;
use App\Filament\Resources\KesantrianAmalMasters\Tables\KesantrianAmalMasterTable;
use App\Filament\Support\ModulKomponen;
use App\Models\KesantrianAmalMaster;
use BackedEnum;
use Closure;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class KesantrianAmalMasterResource extends Resource
{
    protected static ?string $model = KesantrianAmalMaster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?string $navigationLabel = 'Amal';

    protected static ?string $modelLabel = 'Amal Mutabaah';

    protected static ?string $pluralModelLabel = 'Pengaturan Amal Mutabaah';

    protected static ?string $cluster = Kesantrian::class;

    protected static ?int $navigationSort = 3;

    /**
     * Pengaturan master yang jarang dibuka — tidak perlu tab sendiri di cluster
     * Mutabaah; masuknya lewat tombol "Amal" di header daftar Mutabaah.
     * canViewAny() tetap membatasi ke admin pesantren.
     */
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'amal';

    public static function canViewAny(): bool
    {
        return Auth::user()?->role === 'admin_pesantren'
            && ModulKomponen::aktif(static::class);
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
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return KesantrianAmalMasterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KesantrianAmalMasterTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Kode dipakai sebagai kunci data amalan dan tidak pernah ditampilkan saat
     * membuat, jadi diturunkan dari label + akhiran angka bila sudah terpakai —
     * dulu mutateFormDataBeforeCreate() halaman Create, kini menumpang
     * CreateAction.
     */
    public static function isiKodeOtomatis(): Closure
    {
        return function (array $data): array {
            $base = Str::slug($data['label'], '_') ?: 'amal';
            $kode = $base;
            $suffix = 1;

            while (
                KesantrianAmalMaster::where('pesantren_id', Auth::user()?->pesantren_id)
                    ->where('kode', $kode)
                    ->exists()
            ) {
                $kode = $base.'_'.(++$suffix);
            }

            $data['kode'] = $kode;

            return $data;
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKesantrianAmalMaster::route('/'),
        ];
    }
}
