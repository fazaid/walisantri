<?php

namespace App\Filament\Resources\Users;

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
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

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen';

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
