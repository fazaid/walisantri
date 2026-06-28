<?php

namespace App\Filament\Resources\SantriEkskuls;

use App\Filament\Clusters\Akademik;
use App\Filament\Resources\SantriEkskuls\Pages\CreateSantriEkskul;
use App\Filament\Resources\SantriEkskuls\Pages\EditSantriEkskul;
use App\Filament\Resources\SantriEkskuls\Pages\ListSantriEkskuls;
use App\Filament\Resources\SantriEkskuls\Schemas\SantriEkskulForm;
use App\Filament\Resources\SantriEkskuls\Tables\SantriEkskulsTable;
use App\Models\SantriEkskul;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SantriEkskulResource extends Resource
{
    protected static ?string $model = SantriEkskul::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $cluster = Akademik::class;

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'id';
    protected static ?string $navigationLabel = 'Ekskul Santri';
    protected static ?string $modelLabel = 'Ekskul Santri';
    protected static ?string $pluralModelLabel = 'Ekskul Santri';

    public static function canViewAny(): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']);
    }

    public static function canCreate(): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']);
    }

    public static function canEdit($record): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']);
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public static function form(Schema $schema): Schema
    {
        return SantriEkskulForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SantriEkskulsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSantriEkskuls::route('/'),
            'create' => CreateSantriEkskul::route('/create'),
            'edit'   => EditSantriEkskul::route('/{record}/edit'),
        ];
    }
}
