<?php

namespace App\Filament\Resources\TahfidzUjians;

use App\Filament\Resources\TahfidzUjians\Pages\CreateTahfidzUjian;
use App\Filament\Resources\TahfidzUjians\Pages\EditTahfidzUjian;
use App\Filament\Resources\TahfidzUjians\Pages\ListTahfidzUjians;
use App\Filament\Resources\TahfidzUjians\Pages\ViewTahfidzUjian;
use App\Filament\Resources\TahfidzUjians\Schemas\TahfidzUjianForm;
use App\Filament\Resources\TahfidzUjians\Schemas\TahfidzUjianInfolist;
use App\Filament\Resources\TahfidzUjians\Tables\TahfidzUjiansTable;
use App\Models\TahfidzUjian;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use BackedEnum;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class TahfidzUjianResource extends Resource
{
    protected static ?string $model = TahfidzUjian::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'target_juz';
    protected static ?string $navigationLabel = 'Ujian Tahfidz';
    protected static ?string $modelLabel = 'Ujian';
    protected static ?string $pluralModelLabel = 'Ujian Tahfidz';

    protected static string|UnitEnum|null $navigationGroup = 'Kesantrian';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return Gate::allows('access-modul-akademik');
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
        return TahfidzUjiansTable::configure($table);
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
            'index' => ListTahfidzUjians::route('/'),
            'create' => CreateTahfidzUjian::route('/create'),
            'view' => ViewTahfidzUjian::route('/{record}'),
            'edit' => EditTahfidzUjian::route('/{record}/edit'),
        ];
    }
}
