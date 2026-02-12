<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MedicalRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'medicalRecords';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_penyakit')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('tanggal_sakit')
                    ->required(),
                Forms\Components\TextInput::make('tindakan')
                    ->placeholder('Contoh: Berobat ke Puskesmas'),
                Forms\Components\Textarea::make('catatan_medis')
                    ->label('Catatan/Alergi'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama_penyakit')
            ->columns([
                Tables\Columns\TextColumn::make('nama_penyakit')->searchable(),
                Tables\Columns\TextColumn::make('tanggal_sakit')->date()->sortable(),
                Tables\Columns\TextColumn::make('tindakan'),
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
