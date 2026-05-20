<?php
// ============================================================
// FILE 3: app/Filament/Resources/TahfidzUjians/Schemas/TahfidzUjianInfolist.php
// ============================================================

namespace App\Filament\Resources\TahfidzUjians\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TahfidzUjianInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detail Ujian')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('santri.nama_lengkap')->label('Santri'),
                        TextEntry::make('penguji.name')->label('Penguji'),
                        TextEntry::make('tanggal_ujian')->label('Tanggal')->date('d M Y'),
                        TextEntry::make('target_juz')->label('Target Juz')
                            ->formatStateUsing(fn ($state) => 'Juz ' . $state),
                        TextEntry::make('status_kelulusan')->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Lulus'    => 'success',
                                'Mengulang'=> 'danger',
                            }),
                        TextEntry::make('catatan_ujian')->label('Catatan')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }
}


// ============================================================
// FILE 4: app/Filament/Resources/TahfidzUjians/Tables/TahfidzUjiansTable.php
// ============================================================

namespace App\Filament\Resources\TahfidzUjians\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TahfidzUjiansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_ujian')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('santri.nama_lengkap')->label('Santri')->searchable()->sortable(),
                TextColumn::make('target_juz')->label('Target Juz')
                    ->formatStateUsing(fn ($state) => 'Juz ' . $state),
                TextColumn::make('status_kelulusan')->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Lulus'    => 'success',
                        'Mengulang'=> 'danger',
                    }),
                TextColumn::make('penguji.name')->label('Penguji')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal_ujian', 'desc')
            ->filters([
                SelectFilter::make('status_kelulusan')->label('Status')
                    ->options(['Lulus' => 'Lulus', 'Mengulang' => 'Mengulang']),
                SelectFilter::make('target_juz')->label('Target Juz')
                    ->options(array_combine(
                        ['1','3','5','10','15','20','25','30'],
                        ['Juz 1','Juz 3','Juz 5','Juz 10','Juz 15','Juz 20','Juz 25','Juz 30']
                    )),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}