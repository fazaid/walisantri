<?php

namespace App\Filament\Resources\TahfidzUjian;

use App\Enums\UserRole;
use App\Filament\Clusters\Tahfidz;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesQueryToUstadzSantri;
use App\Filament\Resources\TahfidzUjian\Pages\ListTahfidzUjian;
use App\Filament\Resources\TahfidzUjian\Pages\ViewTahfidzUjian;
use App\Filament\Resources\TahfidzUjian\Schemas\TahfidzUjianForm;
use App\Filament\Resources\TahfidzUjian\Schemas\TahfidzUjianInfolist;
use App\Filament\Resources\TahfidzUjian\Tables\TahfidzUjianTable;
use App\Models\TahfidzUjian;
use BackedEnum;
use Closure;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TahfidzUjianResource extends Resource
{
    use HasAdminUstadzAccess;
    use ScopesQueryToUstadzSantri;

    protected static ?string $model = TahfidzUjian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'nama_santri';

    protected static ?string $slug = 'ujian';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Ujian';

    protected static ?string $modelLabel = 'Ujian';

    protected static ?string $pluralModelLabel = 'Ujian Tahfidz';

    protected static ?string $cluster = Tahfidz::class;

    /**
     * Stempel siapa yang menguji; kembarannya TahfidzProgressResource::stempelPencatat().
     *
     * Alasannya sama persis: ustadz hanya bisa memilih santri bimbingannya, jadi
     * pengujinya tidak mungkin orang lain — sementara dropdown lamanya memuat seluruh
     * ustadz sepesantren tanpa penjagaan server. admin_pesantren tetap memilih sendiri.
     * Hanya di CreateAction, supaya menyunting baris lama tidak menulis ulang kreditnya.
     */
    public static function stempelPenguji(): Closure
    {
        return function (array $data): array {
            if (auth()->user()?->role === UserRole::Ustadz->value) {
                $data['penguji_id'] = auth()->id();
            }

            return $data;
        };
    }

    public static function form(Schema $schema): Schema
    {
        return TahfidzUjianForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TahfidzUjianInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TahfidzUjianTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTahfidzUjian::route('/'),
            'view' => ViewTahfidzUjian::route('/{record}'),
        ];
    }
}
