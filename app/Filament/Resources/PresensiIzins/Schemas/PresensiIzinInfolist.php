<?php

namespace App\Filament\Resources\PresensiIzins\Schemas;

use App\Enums\JenisIzin;
use App\Enums\StatusPengajuanIzin;
use App\Models\PresensiIzin;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PresensiIzinInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pengajuan')->columns(2)->schema([
                TextEntry::make('santri.nama_lengkap')->label('Santri'),
                TextEntry::make('jenis')->label('Jenis')->badge()
                    ->formatStateUsing(fn (JenisIzin $state) => $state->label())
                    ->color(fn (JenisIzin $state) => $state->color()),
                TextEntry::make('tanggal_mulai')->label('Mulai')->date('d M Y'),
                TextEntry::make('tanggal_selesai')->label('Selesai')->date('d M Y'),
                TextEntry::make('alasan')->label('Alasan')->columnSpanFull(),
                TextEntry::make('lampiran')
                    ->label('Lampiran')
                    ->placeholder('Tidak ada')
                    // Disk 'local' — berkasnya tidak punya URL publik, jadi yang
                    // ditampilkan hanya keterangan ada/tidak. Membukanya lewat rute
                    // terotorisasi (wali.izin.lampiran).
                    ->formatStateUsing(fn (?string $state) => $state ? 'Ada (surat/dokumen)' : 'Tidak ada'),
            ]),

            Section::make('Pemrosesan')->columns(2)->schema([
                TextEntry::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (StatusPengajuanIzin $state) => $state->label())
                    ->color(fn (StatusPengajuanIzin $state) => $state->color()),
                TextEntry::make('asal')
                    ->label('Asal Pengajuan')
                    ->state(fn (PresensiIzin $record) => $record->dariWali() ? 'Diajukan wali santri' : 'Dicatat admin'),
                TextEntry::make('diproses_oleh')
                    ->label('Diproses oleh')
                    ->placeholder('—')
                    // FK logis ke users di DB central — tidak ada relasi Eloquent
                    // yang bisa di-eager-load, jadi namanya diambil manual.
                    ->formatStateUsing(fn (?int $state) => $state ? (User::find($state)?->name ?? '—') : '—'),
                TextEntry::make('diproses_at')->label('Waktu diproses')->dateTime('d M Y H:i')->placeholder('—'),
                TextEntry::make('catatan_petugas')->label('Catatan Petugas')->placeholder('—')->columnSpanFull(),
            ]),
        ]);
    }
}
