<?php

namespace App\Filament\Resources\DemoRequests\Schemas;

use App\Models\DemoRequest;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DemoRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Pesantren')
                ->columns(2)
                ->schema([
                    TextEntry::make('nama_pesantren')->label('Nama Pesantren'),
                    TextEntry::make('kota')->label('Kota/Kabupaten')->placeholder('—'),
                    TextEntry::make('jumlah_santri')->label('Jumlah Santri')->placeholder('—'),
                    TextEntry::make('created_at')->label('Tanggal Daftar')->dateTime('d M Y, H:i'),
                ]),

            Section::make('Kontak')
                ->columns(2)
                ->schema([
                    TextEntry::make('nama_kontak')->label('Nama PIC'),
                    TextEntry::make('email')->label('Email')->copyable(),
                    TextEntry::make('no_hp')->label('No. HP / WhatsApp')->copyable(),
                ]),

            Section::make('Kebutuhan & Tindak Lanjut')
                ->schema([
                    TextEntry::make('catatan')->label('Fitur yang Dibutuhkan')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('sla')
                        ->label('Status SLA')
                        ->badge()
                        ->state(fn (DemoRequest $record): string => match (true) {
                            $record->contacted_at !== null => 'Selesai',
                            $record->isOverdue() => 'Overdue',
                            default => $record->businessDaysWaiting().' hr kerja',
                        })
                        ->color(fn (DemoRequest $record): string => match (true) {
                            $record->contacted_at !== null => 'success',
                            $record->isOverdue() => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('contacted_at')
                        ->label('Dihubungi Pada')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('Belum dihubungi'),
                    TextEntry::make('duplicateOf.nama_pesantren')
                        ->label('Kemungkinan Duplikat Dari')
                        ->state(fn (DemoRequest $record): ?string => $record->duplicateOf
                            ? "{$record->duplicateOf->nama_pesantren} ({$record->duplicateOf->created_at->format('d M Y, H:i')})"
                            : null)
                        ->color('warning')
                        ->visible(fn (DemoRequest $record): bool => $record->duplicate_of_id !== null)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
