<?php

namespace App\Filament\Resources\Presensis\Schemas;

use App\Enums\StatusKehadiran;
use App\Enums\SumberPresensi;
use App\Models\Presensi;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PresensiInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Santri & Tanggal')->columns(2)->schema([
                    TextEntry::make('santri.nama_lengkap')->label('Santri'),
                    TextEntry::make('tanggal')->label('Tanggal')->date('d M Y'),
                    TextEntry::make('kelas.nama_kelas')
                        ->label('Kelas saat dicatat')
                        ->placeholder('—')
                        ->helperText('Kelas yang tercatat saat presensi dibuat, bukan kelas santri hari ini.'),
                    TextEntry::make('jam_ke')
                        ->label('Jam')
                        ->formatStateUsing(fn (int $state) => $state === Presensi::HARIAN ? 'Harian' : "Jam ke-{$state}"),
                ]),

                Section::make('Kehadiran')->columns(2)->schema([
                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn (StatusKehadiran $state) => $state->label())
                        ->color(fn (StatusKehadiran $state) => $state->color()),
                    TextEntry::make('menit_terlambat')
                        ->label('Terlambat')
                        ->placeholder('—')
                        ->suffix(' menit'),
                    TextEntry::make('sumber')
                        ->label('Sumber')
                        ->badge()
                        ->formatStateUsing(fn (SumberPresensi $state) => $state->label())
                        ->color(fn (SumberPresensi $state) => $state->color()),
                    TextEntry::make('catatan')->label('Catatan')->placeholder('—'),
                ]),

                Section::make('Jejak Pencatatan')->columns(2)->schema([
                    // FK logis ke users di DB central — tidak ada relasi Eloquent yang
                    // bisa di-eager-load, jadi namanya diambil manual di sini.
                    TextEntry::make('dicatat_oleh')
                        ->label('Dicatat oleh')
                        ->placeholder('—')
                        ->formatStateUsing(fn (?int $state) => $state ? (User::find($state)?->name ?? '—') : '—'),
                    TextEntry::make('dicatat_at')->label('Waktu dicatat')->dateTime('d M Y H:i')->placeholder('—'),
                ]),
            ]);
    }
}
