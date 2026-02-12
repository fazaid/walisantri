<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeacherResource\Pages;
use App\Filament\Resources\TeacherResource\RelationManagers;
use App\Models\Teacher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;

class TeacherResource extends Resource
{
    protected static ?string $model = Teacher::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nip')
                    ->label('NIP/NIY')
                    ->unique(ignoreRecord: true),
                TextInput::make('nama_pengajar')
                    ->label('Nama Lengkap')
                    ->required(),
                TextInput::make('no_hp')
                    ->label('Nomor HP/WhatsApp')
                    ->tel(),
                TextInput::make('spesialisasi')
                    ->placeholder('Contoh: Tahfidz, Bahasa Arab, atau Wali Kelas 7A'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nip')->searchable(),
                Tables\Columns\TextColumn::make('nama_pengajar')->searchable(),
                Tables\Columns\TextColumn::make('spesialisasi'),
                Tables\Columns\TextColumn::make('students_count')
                    ->counts('students')
                    ->label('Jumlah Santri'), // Ini akan otomatis menghitung jumlah santri binaannya
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
            'index' => Pages\ListTeachers::route('/'),
            'create' => Pages\CreateTeacher::route('/create'),
            'edit' => Pages\EditTeacher::route('/{record}/edit'),
        ];
    }
}
