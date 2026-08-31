<?php

// File: app/Filament/Resources/Santris/Schemas/SantriInfolist.php

namespace App\Filament\Resources\Santris\Schemas;

use App\Filament\Resources\Santris\Actions\UnduhKartuLengkapAction;
use App\Filament\Resources\Santris\Actions\UnduhKartuQrAction;
use App\Models\Santri;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class SantriInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Santri')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nis')
                            ->label('NIS'),
                        TextEntry::make('nama_lengkap')
                            ->label('Nama Lengkap')
                            ->columnSpanFull(),
                        TextEntry::make('nama_panggilan')
                            ->label('Nama Panggilan')
                            ->placeholder('—'),
                        TextEntry::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->date('d M Y')
                            ->placeholder('—'),
                        TextEntry::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->formatStateUsing(fn ($state) => $state?->label())
                            ->placeholder('—'),
                        TextEntry::make('kelas.nama_kelas')
                            ->label('Kelas'),
                        TextEntry::make('kamar.nama_kamar')
                            ->label('Kamar'),
                        IconEntry::make('status_aktif')
                            ->label('Status Aktif')
                            ->boolean(),
                        TextEntry::make('uuid')
                            ->label('Magic Link UUID')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),

                // Kartu diletakkan tepat di bawah Data Santri, bukan di dekat Foto
                // Profil: yang dicari admin di sini adalah "cetakkan kartu anak ini",
                // dan itu tindakan identitas, bukan urusan berkas gambar.
                Section::make('Kartu Santri')
                    // Key eksplisit: tombol unduh hidup di footerActions section ini,
                    // bukan di header halaman, jadi tes harus bisa menyebut alamatnya
                    // (TestAction::...->schemaComponent('kartu-santri')).
                    ->key('kartu-santri')
                    ->description('QR yang dipindai saat presensi, dan tombol unduh kedua jenis kartu.')
                    ->schema([
                        // $record tersedia sendiri di blade lewat getExtraViewData()
                        // milik komponen schema — tidak perlu viewData().
                        View::make('filament.infolists.kartu-santri'),
                    ])
                    ->footerActions([
                        UnduhKartuQrAction::make(),
                        UnduhKartuLengkapAction::make(),
                    ]),

                Section::make('Biodata')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('nama_ayah')
                            ->label('Nama Ayah')
                            ->placeholder('—'),
                        TextEntry::make('nama_ibu')
                            ->label('Nama Ibu')
                            ->placeholder('—'),
                        TextEntry::make('alamat_lengkap')
                            ->label('Alamat Lengkap')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('jumlah_saudara')
                            ->label('Jumlah Saudara')
                            ->placeholder('—'),
                        TextEntry::make('cita_cita')
                            ->label('Cita-cita')
                            ->placeholder('—'),
                        TextEntry::make('ciri_fisik')
                            ->label('Ciri Fisik yang Mudah Dikenali')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),

                Section::make('Foto Profil')
                    ->schema([
                        ImageEntry::make('foto_profil')
                            ->label('Foto Profil')
                            ->disk('public')
                            ->height(200)
                            ->placeholder('Belum ada foto'),
                    ]),

                Section::make('Relasi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('wali.name')
                            ->label('Wali Santri'),
                        TextEntry::make('pembimbing.name')
                            ->label('Ustadz Pembimbing'),
                        TextEntry::make('pesantren.nama_pesantren')
                            ->label('Pesantren'),
                    ]),

                Section::make('Timestamps')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('updated_at')
                            ->label('Diperbarui')
                            ->dateTime('d M Y, H:i'),
                        TextEntry::make('deleted_at')
                            ->label('Dihapus')
                            ->dateTime('d M Y, H:i')
                            ->visible(fn (Santri $record): bool => $record->trashed()),
                    ]),
            ]);
    }
}
