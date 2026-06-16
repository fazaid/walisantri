<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Order')
                ->columns(2)
                ->schema([
                    TextEntry::make('nomor_order')
                        ->label('Nomor Order')
                        ->copyable()
                        ->fontFamily('mono'),

                    TextEntry::make('invoice.nomor_invoice')
                        ->label('Nomor Invoice')
                        ->copyable()
                        ->fontFamily('mono'),

                    TextEntry::make('pesantren.nama_pesantren')
                        ->label('Pesantren'),

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->color(fn (Order $record): string => $record->status->color())
                        ->formatStateUsing(fn (Order $record): string => $record->status->label()),

                    TextEntry::make('paket_target')
                        ->label('Paket')
                        ->badge()
                        ->color(fn (Order $record): string => $record->paket_target->color())
                        ->formatStateUsing(fn (Order $record): string => $record->paket_target->label()),

                    TextEntry::make('max_santri_kuota_target')
                        ->label('Kuota Santri')
                        ->numeric(),

                    TextEntry::make('durasi_bulan')
                        ->label('Durasi Bayar')
                        ->formatStateUsing(fn (int $state): string => $state . ' bulan'),

                    TextEntry::make('bonus_bulan')
                        ->label('Bonus')
                        ->formatStateUsing(fn (int $state): string => $state > 0 ? "+{$state} bulan" : '—'),

                    TextEntry::make('durasi_total_bulan')
                        ->label('Total Aktif')
                        ->formatStateUsing(fn (int $state): string => $state . ' bulan'),

                    TextEntry::make('created_at')
                        ->label('Dibuat')
                        ->dateTime('d F Y, H:i'),
                ]),

            Section::make('Rincian Biaya')
                ->columns(2)
                ->schema([
                    TextEntry::make('harga_per_bulan')
                        ->label('Harga / Bulan')
                        ->formatStateUsing(fn (int $state): string => 'Rp ' . number_format($state, 0, ',', '.')),

                    TextEntry::make('harga_total_sebelum_diskon')
                        ->label('Subtotal')
                        ->formatStateUsing(fn (int $state): string => 'Rp ' . number_format($state, 0, ',', '.')),

                    TextEntry::make('kode_kupon_snapshot')
                        ->label('Kode Kupon')
                        ->placeholder('—'),

                    TextEntry::make('diskon_nominal')
                        ->label('Diskon')
                        ->formatStateUsing(fn (int $state): string =>
                            $state > 0 ? '− Rp ' . number_format($state, 0, ',', '.') : '—'
                        ),

                    TextEntry::make('harga_total')
                        ->label('Total Bayar')
                        ->formatStateUsing(fn (int $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                        ->weight('bold')
                        ->size('lg'),
                ]),

            Section::make('Bukti Pembayaran')
                ->columns(2)
                ->schema([
                    TextEntry::make('invoice.bukti_transfer_uploaded_at')
                        ->label('Diunggah Pada')
                        ->dateTime('d F Y, H:i')
                        ->placeholder('Belum ada bukti transfer'),

                    TextEntry::make('invoice.bukti_transfer_path')
                        ->label('File')
                        ->formatStateUsing(fn (?string $state, Order $record): string =>
                            $state
                                ? '✓ Tersedia — gunakan tombol "Lihat Bukti Transfer" di atas'
                                : '—'
                        )
                        ->placeholder('—'),
                ]),

            Section::make('Konfirmasi')
                ->columns(2)
                ->schema([
                    TextEntry::make('catatan_admin')
                        ->label('Catatan Admin')
                        ->placeholder('—')
                        ->columnSpanFull(),

                    TextEntry::make('confirmed_at')
                        ->label('Dikonfirmasi Pada')
                        ->dateTime('d F Y, H:i')
                        ->placeholder('Belum dikonfirmasi'),

                    TextEntry::make('confirmedBy.name')
                        ->label('Dikonfirmasi Oleh')
                        ->placeholder('—'),

                    TextEntry::make('expired_at_baru')
                        ->label('Expired Baru')
                        ->dateTime('d F Y')
                        ->placeholder('—'),
                ]),
        ]);
    }
}
