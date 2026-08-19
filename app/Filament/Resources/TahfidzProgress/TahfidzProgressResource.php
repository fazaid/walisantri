<?php

namespace App\Filament\Resources\TahfidzProgress;

use App\Enums\UserRole;
use App\Filament\Clusters\Tahfidz;
use App\Filament\Concerns\HasAdminUstadzAccess;
use App\Filament\Concerns\ScopesQueryToUstadzSantri;
use App\Filament\Resources\TahfidzProgress\Pages\ListTahfidzProgress;
use App\Filament\Resources\TahfidzProgress\Pages\ViewTahfidzProgress;
use App\Filament\Resources\TahfidzProgress\Schemas\TahfidzProgressForm;
use App\Filament\Resources\TahfidzProgress\Schemas\TahfidzProgressInfolist;
use App\Filament\Resources\TahfidzProgress\Tables\TahfidzProgressTable;
use App\Models\TahfidzProgress;
use BackedEnum;
use Closure;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TahfidzProgressResource extends Resource
{
    use HasAdminUstadzAccess;
    use ScopesQueryToUstadzSantri;

    protected static ?string $model = TahfidzProgress::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $recordTitleAttribute = 'nama_santri';

    protected static ?string $slug = 'setoran';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Setoran';

    protected static ?string $modelLabel = 'Setoran';

    protected static ?string $pluralModelLabel = 'Setoran Tahfidz';

    protected static ?string $cluster = Tahfidz::class;

    /**
     * Stempel siapa yang menyimak setoran ini; dipakai CreateAction lewat
     * ->mutateDataUsing(). Pola sama dengan UangSakuResource::catatPencatat().
     *
     * Untuk ustadz nilainya DIPAKSA ke dirinya sendiri dan fieldnya disembunyikan:
     * ia hanya bisa memilih santri bimbingannya (SantriOptions::aktifUntukPengguna),
     * jadi pencatatnya tidak mungkin orang lain — dan sebelum ini dropdown-nya justru
     * memuat SELURUH ustadz sepesantren tanpa penjagaan server sama sekali, sehingga
     * "jejak audit" di §5.4 bisa dibelokkan lewat UI biasa.
     *
     * admin_pesantren tetap memilih sendiri: ia memasukkan data susulan atas nama
     * ustadz yang benar-benar menyimak, bukan mencatat setoran yang ia simak sendiri.
     * Sengaja hanya di CreateAction — menyunting baris lama tidak boleh menulis ulang
     * kreditnya ke penyunting.
     */
    public static function stempelPencatat(): Closure
    {
        return function (array $data): array {
            if (auth()->user()?->role === UserRole::Ustadz->value) {
                $data['ustadz_id'] = auth()->id();
            }

            return $data;
        };
    }

    public static function form(Schema $schema): Schema
    {
        return TahfidzProgressForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TahfidzProgressInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TahfidzProgressTable::configure($table);
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
            'index' => ListTahfidzProgress::route('/'),
            'view' => ViewTahfidzProgress::route('/{record}'),
        ];
    }
}
