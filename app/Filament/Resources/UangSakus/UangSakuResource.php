<?php

namespace App\Filament\Resources\UangSakus;

use App\Enums\UserRole;
use App\Filament\Clusters\Keuangan;
use App\Filament\Resources\UangSakus\Pages\CreateUangSaku;
use App\Filament\Resources\UangSakus\Pages\ListUangSakus;
use App\Filament\Resources\UangSakus\Schemas\UangSakuForm;
use App\Filament\Resources\UangSakus\Tables\UangSakusTable;
use App\Models\UangSakuSantri;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UangSakuResource extends Resource
{
    protected static ?string $model = UangSakuSantri::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?string $cluster = Keuangan::class;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel  = 'Uang Saku';
    protected static ?string $modelLabel       = 'Transaksi Uang Saku';
    protected static ?string $pluralModelLabel = 'Uang Saku Santri';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::AdminPesantren->value;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function form(Schema $schema): Schema
    {
        return UangSakuForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UangSakusTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUangSakus::route('/'),
            'create' => CreateUangSaku::route('/create'),
        ];
    }
}
