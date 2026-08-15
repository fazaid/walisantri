<?php

namespace App\Filament\Resources\PresensiIzins;

use App\Enums\StatusPengajuanIzin;
use App\Enums\UserRole;
use App\Filament\Clusters\Presensi as ClusterPresensi;
use App\Filament\Resources\PresensiIzins\Pages\ListPresensiIzins;
use App\Filament\Resources\PresensiIzins\Pages\ViewPresensiIzin;
use App\Filament\Resources\PresensiIzins\Schemas\PresensiIzinForm;
use App\Filament\Resources\PresensiIzins\Schemas\PresensiIzinInfolist;
use App\Filament\Resources\PresensiIzins\Tables\PresensiIzinsTable;
use App\Models\PresensiIzin;
use App\Support\PenugasanUstadz;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PresensiIzinResource extends Resource
{
    protected static ?string $model = PresensiIzin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelopeOpen;

    protected static ?string $cluster = ClusterPresensi::class;

    protected static ?string $navigationLabel = 'Pengajuan Izin';

    protected static ?string $modelLabel = 'Pengajuan Izin';

    protected static ?string $pluralModelLabel = 'Pengajuan Izin';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'izin';

    protected static ?string $recordTitleAttribute = 'alasan';

    /**
     * Admin pesantren dan wali kelas. Pembimbing halaqah tetap nol akses (§5.4) —
     * scoping-nya di getEloquentQuery(): ustadz yang tidak mewalikan kelas mana pun
     * mendapat daftar kosong, sehingga tidak perlu aturan role terpisah.
     */
    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ], true);
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()?->role === UserRole::AdminPesantren->value;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->role === UserRole::AdminPesantren->value;
    }

    /** Wali kelas hanya melihat pengajuan santri di kelas perwaliannya. */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()?->role === UserRole::Ustadz->value) {
            $query->whereIn('santri_id', PenugasanUstadz::santriIdsPerwalianKelas());
        }

        return $query;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        if (Auth::user()?->role === UserRole::Ustadz->value) {
            $query->whereIn('santri_id', PenugasanUstadz::santriIdsPerwalianKelas());
        }

        return $query;
    }

    /** Badge jumlah pengajuan yang belum diproses — pola DemoRequestResource. */
    public static function getNavigationBadge(): ?string
    {
        $jumlah = static::getEloquentQuery()
            ->where('status', StatusPengajuanIzin::Diajukan->value)
            ->count();

        // null menyembunyikan badge; "0" tetap dirender oleh Filament.
        return $jumlah > 0 ? (string) $jumlah : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getRecordTitle(?Model $record): Htmlable|string|null
    {
        return $record?->santri?->nama_lengkap ?? 'Pengajuan Izin';
    }

    public static function form(Schema $schema): Schema
    {
        return PresensiIzinForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PresensiIzinInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PresensiIzinsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPresensiIzins::route('/'),
            'view' => ViewPresensiIzin::route('/{record}'),
        ];
    }
}
