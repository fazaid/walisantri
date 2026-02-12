<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParentProfileResource\Pages;
use App\Filament\Resources\ParentProfileResource\RelationManagers;
use App\Models\ParentProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParentProfileResource extends Resource
{
    protected static ?string $model = ParentProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Ayah')
                ->schema([
                    Forms\Components\TextInput::make('nama_ayah')->required(),
                    Forms\Components\TextInput::make('pekerjaan_ayah'),
                    Forms\Components\Select::make('pendidikan_ayah')
                        ->options([
                            'SD' => 'SD',
                            'SMP' => 'SMP',
                            'SMA' => 'SMA/Sederajat',
                            'D3' => 'D3',
                            'S1' => 'S1',
                            'S2' => 'S2',
                            'S3' => 'S3',
                            'Tidak Sekolah' => 'Tidak Sekolah',
                        ]),
                ])->columns(3),

                Forms\Components\Section::make('Data Ibu')
                ->schema([
                    Forms\Components\TextInput::make('nama_ibu')->required(),
                    Forms\Components\TextInput::make('pekerjaan_ibu'),
                    Forms\Components\Select::make('pendidikan_ibu')
                        ->options([
                            'SD' => 'SD',
                            'SMP' => 'SMP',
                            'SMA' => 'SMA/Sederajat',
                            'D3' => 'D3',
                            'S1' => 'S1',
                            'S2' => 'S2',
                            'S3' => 'S3',
                            'Tidak Sekolah' => 'Tidak Sekolah',
                        ]),
                ])->columns(3),

                Forms\Components\TextInput::make('no_hp_wali')->required(),
                Forms\Components\Textarea::make('alamat_ortu'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_ayah')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama_ibu')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_hp_wali')
                    ->label('No. HP Wali'),
                // Tables\Columns\TextColumn::make('pekerjaan_ayah')
                //    ->label('Pekerjaan Ayah'),
                // Menampilkan jumlah anak (santri) yang terhubung
                Tables\Columns\TextColumn::make('students_count')
                    ->counts('students')
                    ->label('Jumlah Anak'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListParentProfiles::route('/'),
            'create' => Pages\CreateParentProfile::route('/create'),
            'edit' => Pages\EditParentProfile::route('/{record}/edit'),
        ];
    }
}
