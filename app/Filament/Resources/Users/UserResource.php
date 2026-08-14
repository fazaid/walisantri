<?php

namespace App\Filament\Resources\Users;

use App\Enums\NavigationGroup;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\Santri;
use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Manajemen;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Data Pengguna';

    public static function canAccess(): bool
    {
        $role = auth()->user()?->role;

        return in_array($role, [
            UserRole::SuperAdmin->value,
            UserRole::AdminPesantren->value,
        ]);
    }

    public static function canViewAny(): bool
    {
        $role = auth()->user()?->role;

        return in_array($role, [
            UserRole::SuperAdmin->value,
            UserRole::AdminPesantren->value,
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user?->role === UserRole::AdminPesantren->value) {
            $query->where('pesantren_id', $user->pesantren_id);
        }

        return $query;
    }

    /**
     * Alasan tombol hapus tidak layak DITAMPILKAN sama sekali untuk pengguna ini.
     *
     * Dua kasus struktural yang tidak akan pernah bisa diselesaikan pengguna, jadi
     * menampilkan tombolnya cuma menjanjikan sesuatu yang selalu ditolak:
     *
     * 1. Akun sendiri.
     * 2. Super admin terakhir — tidak ada UI untuk membuat penggantinya
     *    (paksaTenantSaatBuat justru menurunkan role Super Admin yang dibuat admin
     *    pesantren), sehingga platform terkunci permanen.
     */
    public static function alasanSembunyikanHapus(User $user): ?string
    {
        if (auth()->id() === $user->getKey()) {
            return 'Anda tidak bisa menghapus akun Anda sendiri.';
        }

        // COUNT ini hanya jalan untuk baris ber-role super admin — jumlahnya segelintir,
        // jadi tidak perlu dimemoisasi. (Memoisasi lewat properti statis justru berbahaya:
        // nilainya bertahan antar-test dalam satu proses PHPUnit.)
        if ($user->role === UserRole::SuperAdmin->value
            && User::where('role', UserRole::SuperAdmin->value)->count() <= 1
        ) {
            return 'Ini satu-satunya akun super admin yang tersisa. Menghapusnya akan mengunci platform secara permanen.';
        }

        return null;
    }

    /**
     * Alasan pengguna ini tidak boleh dihapus, atau null bila aman dihapus.
     *
     * Mencakup kasus struktural di atas PLUS keterkaitan santri, yang berbeda sifatnya:
     * santri.wali_santri_id dan santri.pembimbing_ustadz_id keduanya restrictOnDelete,
     * jadi tanpa penjagaan Postgres melempar SQLSTATE 23503 mentah dan pengguna melihat
     * error 500, bukan penjelasan. Kasus ini BISA diselesaikan (pindahkan santrinya),
     * jadi tombolnya tetap ditampilkan dan alasannya disampaikan lewat notifikasi.
     */
    public static function alasanTidakBisaDihapus(User $user): ?string
    {
        if ($alasan = static::alasanSembunyikanHapus($user)) {
            return $alasan;
        }

        // withoutGlobalScopes() di sini SENGAJA ikut mencopot SoftDeletingScope: santri
        // yang di-soft-delete barisnya masih ada secara fisik, jadi FK tetap menolak.
        $sebagaiWali = Santri::withoutGlobalScopes()->where('wali_santri_id', $user->getKey())->count();
        $sebagaiPembimbing = Santri::withoutGlobalScopes()->where('pembimbing_ustadz_id', $user->getKey())->count();

        if ($sebagaiWali > 0) {
            return "Pengguna ini masih terdaftar sebagai wali dari {$sebagaiWali} santri. Pindahkan santri tersebut ke wali lain sebelum menghapusnya.";
        }

        if ($sebagaiPembimbing > 0) {
            return "Pengguna ini masih membimbing {$sebagaiPembimbing} santri. Pindahkan santri tersebut ke pembimbing lain sebelum menghapusnya.";
        }

        return null;
    }

    // Admin pesantren tidak boleh menyimpan pengguna ke pesantren lain, dan tidak
    // boleh mengangkat siapa pun jadi Super Admin. Dulu dijaga di halaman
    // CreateUser/EditUser; formnya sekarang modal, jadi penjaganya menempel di aksi.
    public static function paksaTenantSaatBuat(): Closure
    {
        return function (array $data): array {
            $user = auth()->user();

            if ($user?->role !== UserRole::AdminPesantren->value) {
                return $data;
            }

            $data['pesantren_id'] = $user->pesantren_id;

            if (($data['role'] ?? null) === UserRole::SuperAdmin->value) {
                $data['role'] = UserRole::WaliSantri->value;
            }

            return $data;
        };
    }

    // Versi edit: role Super Admin yang diselundupkan dikembalikan ke role lama
    // milik record, bukan ke Wali Santri — supaya ustadz tidak ikut terdegradasi.
    public static function paksaTenantSaatUbah(): Closure
    {
        return function (array $data, Model $record): array {
            $user = auth()->user();

            if ($user?->role !== UserRole::AdminPesantren->value) {
                return $data;
            }

            $data['pesantren_id'] = $user->pesantren_id;

            if (($data['role'] ?? null) === UserRole::SuperAdmin->value) {
                $data['role'] = $record->role;
            }

            return $data;
        };
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
        ];
    }
}
