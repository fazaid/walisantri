<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Filament\Resources\StudentResource\RelationManagers;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Tabs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                ->tabs([
                    Tabs\Tab::make('Identitas Santri')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                FileUpload::make('foto')
                                ->image() // Memastikan hanya file gambar
                                ->directory('photos/students') // Folder penyimpanan
                                ->imageEditor() // Fitur keren: bisa crop foto langsung!
                                ->maxSize(1024) // Maksimal 1MB agar server tidak penuh
                                ->columnSpanFull(),
                                ])->columns(1),
                        Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('nisn')
                                ->label('NISN')
                                ->required()
                                ->unique(ignoreRecord: true),
                            Forms\Components\TextInput::make('nis')
                                ->label('NIS')
                                ->required()
                                ->unique(ignoreRecord: true),
                            Forms\Components\TextInput::make('nik')
                                ->label('NIK')
                                ->required()
                                ->unique(ignoreRecord: true),
                            Forms\Components\TextInput::make('nama_lengkap')
                                ->required(),
                            Forms\Components\Select::make('jenis_kelamin')
                                ->options([
                                    'L' => 'Laki-laki',
                                    'P' => 'Perempuan',
                                ])->required(),
                            Forms\Components\DatePicker::make('tanggal_lahir')
                                ->required(),
                            Forms\Components\TextInput::make('tempat_lahir')
                                ->required(),
                            Forms\Components\Textarea::make('alamat')
                                ->required(),
                        ]),
                    ]),
                    Tabs\Tab::make('Keluarga / Wali')
                    ->icon('heroicon-m-users')
                    ->schema([
                        Forms\Components\Select::make('parent_profile_id')
                            ->relationship('parent', 'nama_ayah') // Mengambil data dari relasi 'parent' di model Student
                            ->searchable()
                            ->preload()
                            ->label('Orang Tua (Ayah)')
                            ->createOptionForm([ // Hebatnya Filament: Bisa tambah ortu baru langsung dari sini!
                                Forms\Components\TextInput::make('nama_ayah')->required(),
                                Forms\Components\TextInput::make('nama_ibu')->required(),
                                Forms\Components\TextInput::make('no_hp_wali')->required(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('anak_ke')
                                    ->numeric(),
                                Forms\Components\TextInput::make('jumlah_saudara')
                                    ->numeric(),
                            ])
                    ]),

            // Tab 3: Minat & Bakat
                    Tabs\Tab::make('Minat & Bakat')
                        ->icon('heroicon-m-sparkles')
                        ->schema([
                            Forms\Components\TextInput::make('hobi'),
                            Forms\Components\TextInput::make('cita_cita'),
                            Forms\Components\Textarea::make('minat_bakat'),
                        ]),

                //tab 4: Info Akademik
                    Tabs\Tab::make('Status Akademik')
                        ->icon('heroicon-m-academic-cap')
                        ->schema([
                            Forms\Components\DatePicker::make('tanggal_masuk'),
                            Forms\Components\TextInput::make('diterima_di_kelas'),
                            Forms\Components\Select::make('status_aktif')
                                ->options([
                                    'Aktif' => 'Aktif',
                                    'Lulus' => 'Lulus',
                                    'Mutasi' => 'Mutasi',
                                    'Keluar' => 'Keluar',
                                ]),
                            Forms\Components\TextInput::make('kelas_saat_ini'),
                            Forms\Components\TextInput::make('wali_kelas'),
                   ])
            ])->columnSpanFull(), // Agar tab melebar memenuhi layar
         ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('foto')
                    ->label('Foto')
                    ->square()
                    ->size(60), // Ukuran foto 60x60 px
                Tables\Columns\TextColumn::make('nama_lengkap')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nisn')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kelas_saat_ini')->sortable(),
                Tables\Columns\BadgeColumn::make('status_aktif')
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Lulus' => 'info',
                        'Mutasi' => 'warning',
                        'Keluar' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('wali_kelas')
                    ->toggleable(isToggledHiddenByDefault: true), // Bisa disembunyikan
                ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_aktif')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Lulus' => 'Lulus',
                        'Mutasi' => 'Mutasi',
                        'Keluar' => 'Keluar',
                    ]),
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
            // Daftarkan class yang tadi Anda generate lewat terminal
            RelationManagers\AchievementsRelationManager::class,
            RelationManagers\ViolationsRelationManager::class,
            RelationManagers\MedicalRecordsRelationManager::class, // Tambahkan ini
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }
}
