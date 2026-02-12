<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ViolationsRelationManager extends RelationManager
{
    protected static string $relationship = 'violations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('jenis_pelanggaran')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('tanggal_pelanggaran')
                    ->required(),
                Forms\Components\Select::make('kategori_sanksi')
                    ->options([
                        'Ringan' => 'Ringan',
                        'Sedang' => 'Sedang',
                        'Berat' => 'Berat',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('catatan_sanksi')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('jenis_pelanggaran')
            ->columns([
                Tables\Columns\TextColumn::make('jenis_pelanggaran')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal_pelanggaran')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kategori_sanksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ringan' => 'success',
                        'Sedang' => 'warning',
                        'Berat' => 'danger',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
