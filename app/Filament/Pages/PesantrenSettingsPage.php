<?php

namespace App\Filament\Pages;

use App\Rules\SlugNotReserved;
use App\Rules\ValidTenantSlug;
use App\Filament\Clusters\PengaturanPesantren;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use UnitEnum;

class PesantrenSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $cluster = PengaturanPesantren::class;

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static ?string $title = 'Pengaturan';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.pesantren-settings-page';

    public string $nama_pesantren  = '';
    public string $pesantren_slug  = '';
    public string $alamat          = '';
    public string $telepon         = '';
    public string $deskripsi       = '';
    public array  $rekening        = [];
    public array  $program         = [];
    public ?string $tahun_berdiri  = null;
    public ?string $akreditasi     = null;

    public static function canAccess(): bool
    {
        return Auth::user()?->role === 'admin_pesantren';
    }

    public function mount(): void
    {
        $pesantren = Auth::user()->pesantren;

        $this->form->fill([
            'nama_pesantren' => $pesantren->nama_pesantren,
            'pesantren_slug' => $pesantren->slug,
            'alamat'         => $pesantren->profil['alamat']    ?? '',
            'telepon'        => $pesantren->profil['telepon']   ?? '',
            'deskripsi'      => $pesantren->profil['deskripsi']     ?? '',
            'rekening'       => $pesantren->profil['rekening']      ?? [],
            'program'        => $pesantren->profil['program']       ?? [],
            'tahun_berdiri'  => $pesantren->profil['tahun_berdiri'] ?? null,
            'akreditasi'     => $pesantren->profil['akreditasi']    ?? null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $pesantren  = Auth::user()->pesantren;
        $baseDomain = config('app.base_domain', 'walisantri.com');

        return $schema
            ->components([
                Section::make('Identitas Pesantren')
                    ->description('Nama dan subdomain publik pesantren Anda.')
                    ->schema([
                        TextInput::make('nama_pesantren')
                            ->label('Nama Pesantren')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true),

                        TextInput::make('pesantren_slug')
                            ->label('Subdomain')
                            ->required()
                            ->prefix($baseDomain . '/')
                            ->helperText(fn (Get $get): string =>
                                'URL publik: https://' . ($get('pesantren_slug') ?: '...') . '.' . $baseDomain
                            )
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'string',
                                Rule::unique('pesantrens', 'slug')->ignore($pesantren->id),
                                new ValidTenantSlug(),
                                new SlugNotReserved(),
                            ])
                            ->hint('Mengubah slug akan melepas slug lama ke cooldown 90 hari.')
                            ->hintColor('warning'),
                    ]),

                Section::make('Profil Publik')
                    ->description('Tampil di halaman profil publik pesantren.')
                    ->schema([
                        TextInput::make('telepon')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('alamat')
                            ->label('Alamat')
                            ->maxLength(500),

                        Textarea::make('deskripsi')
                            ->label('Deskripsi Singkat')
                            ->rows(4)
                            ->maxLength(1000),
                    ]),

                Section::make('Program & Jenjang Pendidikan')
                    ->description('Ditampilkan di halaman profil publik pesantren.')
                    ->schema([
                        Repeater::make('program')
                            ->label('')
                            ->schema([
                                TextInput::make('nama')
                                    ->label('Nama Program')
                                    ->placeholder('Tahfidz Al-Qur\'an')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('jenjang')
                                    ->label('Jenjang')
                                    ->placeholder('Setingkat SMP / SMA')
                                    ->maxLength(100),
                            ])
                            ->columns(2)
                            ->addActionLabel('+ Tambah Program')
                            ->defaultItems(0)
                            ->reorderable(false),
                    ]),

                Section::make('Statistik Ringkas')
                    ->description('Ditampilkan di halaman profil publik pesantren. Jumlah santri dihitung otomatis dari data aktif.')
                    ->schema([
                        TextInput::make('tahun_berdiri')
                            ->label('Tahun Berdiri')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue((int) date('Y')),

                        TextInput::make('akreditasi')
                            ->label('Akreditasi')
                            ->placeholder('A / B / C')
                            ->maxLength(20),
                    ]),

                Section::make('Rekening Pembayaran SPP')
                    ->description('Informasi rekening yang ditampilkan ke wali santri saat melihat tagihan SPP.')
                    ->schema([
                        Repeater::make('rekening')
                            ->label('')
                            ->schema([
                                TextInput::make('nama_bank')
                                    ->label('Nama Bank')
                                    ->placeholder('BCA / BRI / Mandiri / ...')
                                    ->required()
                                    ->maxLength(50),
                                TextInput::make('nomor_rekening')
                                    ->label('Nomor Rekening')
                                    ->placeholder('1234567890')
                                    ->required()
                                    ->maxLength(30),
                                TextInput::make('atas_nama')
                                    ->label('Atas Nama')
                                    ->required()
                                    ->maxLength(100),
                            ])
                            ->columns(3)
                            ->addActionLabel('+ Tambah Rekening')
                            ->defaultItems(0)
                            ->reorderable(false),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Simpan Perubahan')
                            ->submit('save'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data      = $this->form->getState();
        $pesantren = Auth::user()->pesantren;

        $pesantren->update([
            'nama_pesantren' => $data['nama_pesantren'],
            'slug'           => Str::slug($data['pesantren_slug']),
            'profil'         => array_merge($pesantren->profil ?? [], [
                'alamat'        => $data['alamat'],
                'telepon'       => $data['telepon'],
                'deskripsi'     => $data['deskripsi'],
                'rekening'      => $data['rekening'] ?? [],
                'program'       => $data['program'] ?? [],
                'tahun_berdiri' => $data['tahun_berdiri'],
                'akreditasi'    => $data['akreditasi'],
            ]),
        ]);

        Notification::make()
            ->title('Pengaturan berhasil disimpan.')
            ->success()
            ->send();
    }
}
