<?php

namespace App\Filament\Resources\TagihanSpps\Tables;

use App\Enums\StatusTagihanSpp;
use App\Models\PembayaranSpp;
use App\Models\Santri;
use App\Models\TagihanSpp;
use App\Models\TarifSpp;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TagihanSppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('santri.nama_lengkap')
                    ->label('Santri')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('bulan')
                    ->label('Periode')
                    ->formatStateUsing(fn (TagihanSpp $record): string => $record->label_periode)
                    ->sortable(),

                TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nominal')
                    ->label('Nominal')
                    ->formatStateUsing(fn (int $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (TagihanSpp $record): string => $record->status->color())
                    ->formatStateUsing(fn (TagihanSpp $record): string => $record->status->label()),

                TextColumn::make('pembayaran.tanggal_bayar')
                    ->label('Tgl. Bayar')
                    ->date('d M Y')
                    ->placeholder('—'),

                TextColumn::make('jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusTagihanSpp::options()),

                SelectFilter::make('bulan')
                    ->label('Bulan')
                    ->options(TagihanSpp::$namaBulan),

                SelectFilter::make('tahun')
                    ->label('Tahun')
                    ->options(
                        collect(range(now()->year, now()->year - 2))
                            ->mapWithKeys(fn ($y) => [$y => $y])
                            ->all()
                    ),
            ])
            ->headerActions([
                Action::make('generate_massal')
                    ->label('Generate Tagihan Massal')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Select::make('bulan')
                            ->label('Bulan')
                            ->options(TagihanSpp::$namaBulan)
                            ->default(now()->month)
                            ->required(),
                        TextInput::make('tahun')
                            ->label('Tahun')
                            ->numeric()
                            ->default(now()->year)
                            ->minValue(2020)
                            ->maxValue(2099)
                            ->required(),
                        DatePicker::make('jatuh_tempo')
                            ->label('Jatuh Tempo (opsional)'),
                        TextInput::make('keterangan')
                            ->label('Keterangan')
                            ->default('SPP Bulanan'),
                    ])
                    ->action(function (array $data): void {
                        $pesantrenId = auth()->user()->pesantren_id;

                        $tarifMap = TarifSpp::where('pesantren_id', $pesantrenId)
                            ->pluck('nominal', 'kelas_id');

                        $santris = Santri::where('pesantren_id', $pesantrenId)
                            ->where('status_aktif', true)
                            ->get();

                        $created  = 0;
                        $skipped  = 0;
                        $noTarif  = 0;

                        foreach ($santris as $santri) {
                            $exists = TagihanSpp::withoutGlobalScope('pesantren')
                                ->where('santri_id', $santri->id)
                                ->where('bulan', $data['bulan'])
                                ->where('tahun', $data['tahun'])
                                ->exists();

                            if ($exists) {
                                $skipped++;
                                continue;
                            }

                            if (! $santri->kelas_id || ! isset($tarifMap[$santri->kelas_id])) {
                                $noTarif++;
                                continue;
                            }

                            TagihanSpp::create([
                                'pesantren_id' => $pesantrenId,
                                'santri_id'    => $santri->id,
                                'bulan'        => $data['bulan'],
                                'tahun'        => $data['tahun'],
                                'nominal'      => $tarifMap[$santri->kelas_id],
                                'jatuh_tempo'  => $data['jatuh_tempo'] ?? null,
                                'keterangan'   => $data['keterangan'],
                                'status'       => StatusTagihanSpp::BelumBayar,
                            ]);

                            $created++;
                        }

                        $msg = "{$created} tagihan dibuat";
                        if ($skipped) $msg .= ", {$skipped} dilewati (sudah ada)";
                        if ($noTarif) $msg .= ", {$noTarif} dilewati (tarif belum diatur)";

                        Notification::make()
                            ->title($msg . '.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),

                Action::make('tandai_lunas')
                    ->label('Tandai Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (TagihanSpp $record): bool => ! $record->isLunas())
                    ->badge(fn (TagihanSpp $record): ?string => $record->isMenungguKonfirmasi() ? '!' : null)
                    ->badgeColor('warning')
                    ->form([
                        DatePicker::make('tanggal_bayar')
                            ->label('Tanggal Bayar')
                            ->default(now())
                            ->required(),
                        Select::make('metode_bayar')
                            ->label('Metode Bayar')
                            ->options(PembayaranSpp::$metodeBayar)
                            ->default('tunai')
                            ->required(),
                        TextInput::make('catatan')
                            ->label('Catatan (opsional)'),
                    ])
                    ->action(function (TagihanSpp $record, array $data): void {
                        if (! $record->pembayaran()->exists()) {
                            PembayaranSpp::create([
                                'pesantren_id'   => $record->pesantren_id,
                                'tagihan_spp_id' => $record->id,
                                'jumlah'         => $record->nominal,
                                'tanggal_bayar'  => $data['tanggal_bayar'],
                                'metode_bayar'   => $data['metode_bayar'],
                                'catatan'        => $data['catatan'] ?? null,
                                'dicatat_oleh'   => auth()->id(),
                            ]);
                        }

                        $record->update(['status' => StatusTagihanSpp::Lunas]);

                        Notification::make()
                            ->title('Tagihan ditandai lunas.')
                            ->success()
                            ->send();
                    }),

                Action::make('tolak_konfirmasi')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (TagihanSpp $record): bool => $record->isMenungguKonfirmasi())
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Konfirmasi Bukti Transfer')
                    ->modalDescription('Status tagihan akan dikembalikan ke Belum Bayar dan bukti transfer dihapus.')
                    ->action(function (TagihanSpp $record): void {
                        if ($record->bukti_transfer) {
                            Storage::disk('public')->delete($record->bukti_transfer);
                        }

                        $record->update([
                            'bukti_transfer'       => null,
                            'dikonfirmasi_wali_at' => null,
                            'status'               => StatusTagihanSpp::BelumBayar,
                        ]);

                        Notification::make()
                            ->title('Konfirmasi ditolak.')
                            ->warning()
                            ->send();
                    }),
            ]);
    }
}
