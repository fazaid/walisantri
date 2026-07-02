<?php

namespace App\Filament\Resources\PlatformBankAccounts;

use App\Enums\UserRole;
use App\Filament\Resources\PlatformBankAccounts\Pages\CreatePlatformBankAccount;
use App\Filament\Resources\PlatformBankAccounts\Pages\EditPlatformBankAccount;
use App\Filament\Resources\PlatformBankAccounts\Pages\ListPlatformBankAccounts;
use App\Filament\Resources\PlatformBankAccounts\Schemas\PlatformBankAccountForm;
use App\Filament\Resources\PlatformBankAccounts\Tables\PlatformBankAccountsTable;
use App\Models\PlatformBankAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PlatformBankAccountResource extends Resource
{
    protected static ?string $model = PlatformBankAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?int $navigationSort = 11;

    protected static ?string $recordTitleAttribute = 'bank';
    protected static ?string $navigationLabel = 'Rekening Bank';
    protected static ?string $modelLabel = 'Rekening Bank';
    protected static ?string $pluralModelLabel = 'Rekening Bank Platform';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return PlatformBankAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlatformBankAccountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPlatformBankAccounts::route('/'),
            'create' => CreatePlatformBankAccount::route('/create'),
            'edit'   => EditPlatformBankAccount::route('/{record}/edit'),
        ];
    }
}
