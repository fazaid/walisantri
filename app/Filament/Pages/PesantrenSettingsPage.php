<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Models\Wilayah;
use App\Rules\SlugNotReserved;
use App\Rules\ValidTenantSlug;
use App\Support\WilayahLookup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use UnitEnum;

class PesantrenSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Manajemen;

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static ?string $title = 'Pengaturan';

    // Cluster PengaturanPesantren dibubarkan (tinggal satu anggota setelah Billing
    // dipindah). Slug dipertahankan "pengaturan" supaya URL tetap /admin/pengaturan
    // persis seperti waktu masih jadi root cluster.
    protected static ?string $slug = 'pengaturan';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.pesantren-settings-page';

    public string $nama_pesantren = '';

    public string $pesantren_slug = '';

    public string $alamat = '';

    public string $telepon = '';

    public string $email_kontak = '';

    // Kolom wilayah menyimpan KODE Kemendagri; namanya diambil ulang dari tabel saat
    // simpan, supaya nama yang tersimpan di profil tidak pernah basi terhadap tabelnya.
    public ?string $wilayah_provinsi = null;

    public ?string $wilayah_kota = null;

    public ?string $wilayah_kecamatan = null;

    public ?string $wilayah_desa = null;

    public string $deskripsi = '';

    public array $rekening = [];

    public array $program = [];

    public ?string $tahun_berdiri = null;

    public ?string $akreditasi = null;

    public $logo = null; // FileUpload state (single) hydrates sebagai array secara internal

    public array $galeri = [];

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
            'alamat' => $pesantren->profil['alamat'] ?? '',
            'telepon' => $pesantren->profil['telepon'] ?? '',
            'email_kontak' => $pesantren->profil['email_kontak'] ?? '',
            'wilayah_provinsi' => $pesantren->profil['wilayah']['provinsi']['kode'] ?? null,
            'wilayah_kota' => $pesantren->profil['wilayah']['kota']['kode'] ?? null,
            'wilayah_kecamatan' => $pesantren->profil['wilayah']['kecamatan']['kode'] ?? null,
            'wilayah_desa' => $pesantren->profil['wilayah']['desa']['kode'] ?? null,
            'deskripsi' => $pesantren->profil['deskripsi'] ?? '',
            'rekening' => $pesantren->profil['rekening'] ?? [],
            'program' => $pesantren->profil['program'] ?? [],
            'tahun_berdiri' => $pesantren->profil['tahun_berdiri'] ?? null,
            'akreditasi' => $pesantren->profil['akreditasi'] ?? null,
            'logo' => $pesantren->profil['logo'] ?? null,
            'galeri' => $pesantren->profil['galeri'] ?? [],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $pesantren = Auth::user()->pesantren;
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
                            ->suffix('.'.$baseDomain)
                            ->helperText(fn (Get $get): string => 'URL publik: https://'.($get('pesantren_slug') ?: '...').'.'.$baseDomain
                            )
                            ->live(onBlur: true)
                            ->rules([
                                'required',
                                'string',
                                Rule::unique('pesantrens', 'slug')->ignore($pesantren->id),
                                new ValidTenantSlug,
                                new SlugNotReserved,
                            ])
                            ->hint('Mengubah slug akan melepas slug lama ke cooldown 90 hari.')
                            ->hintColor('warning'),
                    ]),

                Section::make('Logo & Galeri Pesantren')
                    ->description('Tampil di halaman profil publik pesantren.')
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Logo Pesantren')
                            ->disk('public')
                            ->directory('logo-pesantren')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml'])
                            ->maxSize(1024)
                            ->nullable(),

                        FileUpload::make('galeri')
                            ->label('Galeri Foto')
                            ->multiple()
                            ->disk('public')
                            ->directory('galeri-pesantren')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->maxFiles(12)
                            ->reorderable()
                            ->nullable(),
                    ]),

                Section::make('Profil Publik')
                    ->description('Tampil di halaman profil publik pesantren.')
                    ->schema([
                        TextInput::make('telepon')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('email_kontak')
                            ->label('Email Pesantren')
                            ->email()
                            ->maxLength(100),

                        // Kaskade wilayah (§3.1). Tanpa kolom ini, salah pilih saat
                        // mendaftar bersifat permanen — tidak ada permukaan lain yang
                        // bisa mengubah profil['wilayah'].
                        //
                        // Opsi terbesar adalah daftar desa satu kecamatan (≤ 100 baris),
                        // jadi ->options() biasa + searchable sudah cukup; tabelnya boleh
                        // 91 ribu baris tanpa perlu getSearchResultsUsing().
                        Select::make('wilayah_provinsi')
                            ->label('Provinsi')
                            ->options(fn () => Wilayah::provinsi()->pluck('nama', 'kode'))
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('wilayah_kota', null);
                                $set('wilayah_kecamatan', null);
                                $set('wilayah_desa', null);
                            }),

                        Select::make('wilayah_kota')
                            ->label('Kota/Kabupaten')
                            ->options(fn (Get $get) => Wilayah::anak($get('wilayah_provinsi'))->pluck('nama', 'kode'))
                            ->disabled(fn (Get $get) => blank($get('wilayah_provinsi')))
                            ->placeholder('Pilih provinsi dulu')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('wilayah_kecamatan', null);
                                $set('wilayah_desa', null);
                            }),

                        Select::make('wilayah_kecamatan')
                            ->label('Kecamatan')
                            ->options(fn (Get $get) => Wilayah::anak($get('wilayah_kota'))->pluck('nama', 'kode'))
                            ->disabled(fn (Get $get) => blank($get('wilayah_kota')))
                            ->placeholder('Pilih kota/kabupaten dulu')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('wilayah_desa', null)),

                        Select::make('wilayah_desa')
                            ->label('Desa/Kelurahan')
                            ->options(fn (Get $get) => Wilayah::anak($get('wilayah_kecamatan'))->pluck('nama', 'kode'))
                            ->disabled(fn (Get $get) => blank($get('wilayah_kecamatan')))
                            ->placeholder('Pilih kecamatan dulu')
                            ->searchable(),

                        TextInput::make('alamat')
                            ->label('Alamat')
                            ->helperText('Nama jalan, RT/RW, dan patokan. Wilayah administratif sudah diisi lewat kolom di atas.')
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
        $data = $this->form->getState();
        $pesantren = Auth::user()->pesantren;
        $oldProfil = $pesantren->profil ?? [];

        // profil hanya ditulis dari halaman ini — cleanup file lama inline, tanpa Observer terpisah
        $oldLogo = $oldProfil['logo'] ?? null;
        if ($oldLogo && $oldLogo !== $data['logo']) {
            Storage::disk('public')->delete($oldLogo);
        }

        $removedGaleri = array_diff($oldProfil['galeri'] ?? [], $data['galeri'] ?? []);
        if ($removedGaleri) {
            Storage::disk('public')->delete($removedGaleri);
        }

        $pesantren->update([
            'nama_pesantren' => $data['nama_pesantren'],
            'slug' => Str::slug($data['pesantren_slug']),
            'profil' => array_merge($oldProfil, [
                'alamat' => $data['alamat'],
                'telepon' => $data['telepon'],
                'email_kontak' => $data['email_kontak'],
                'deskripsi' => $data['deskripsi'],
                'rekening' => $data['rekening'] ?? [],
                'program' => $data['program'] ?? [],
                'tahun_berdiri' => $data['tahun_berdiri'],
                'akreditasi' => $data['akreditasi'],
                'logo' => $data['logo'],
                'galeri' => $data['galeri'] ?? [],
            ], $this->wilayahTersimpan($data)),
        ]);

        Notification::make()
            ->title('Pengaturan berhasil disimpan.')
            ->success()
            ->send();
    }

    /**
     * Rakit ulang key `wilayah` dari kode desa yang dipilih.
     *
     * Sengaja mengabaikan tiga kode di atasnya: leluhurnya diturunkan dari kode desa
     * itu sendiri (WilayahLookup), aturan yang sama dengan /register — sehingga
     * kombinasi tidak konsisten mustahil tersimpan dari permukaan mana pun.
     *
     * Mengembalikan array kosong bila desa belum dipilih, supaya array_merge
     * membiarkan nilai lama apa adanya alih-alih menimpanya dengan null.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function wilayahTersimpan(array $data): array
    {
        if (blank($data['wilayah_desa'] ?? null)) {
            return [];
        }

        $jalur = app(WilayahLookup::class)->jalurDariDesa($data['wilayah_desa']);

        return $jalur === null ? [] : ['wilayah' => $jalur];
    }
}
