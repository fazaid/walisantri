<?php

namespace App\Filament\Resources\KesantrianKesehatans\Tables;

use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class KesantrianKesehatansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_periksa')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('santri.nama_lengkap')
                    ->label('Santri')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jenis_rekam')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'rutin'   => 'success',
                        default   => 'danger',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'rutin'   => 'Rutin',
                        default   => 'Keluhan',
                    }),
                TextColumn::make('kategori_keluhan')
                    ->label('Keluhan')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn (?string $state): string => match ($state) {
                        'Demam'       => 'danger',
                        'Batuk_Pilek' => 'warning',
                        'Sakit_Perut' => 'warning',
                        'Pusing'      => 'info',
                        'Kulit_Gatal' => 'info',
                        'Luka_Fisik'  => 'danger',
                        default       => 'gray',
                    }),
                TextColumn::make('status_pemulihan')
                    ->label('Status')
                    ->badge()
                    ->placeholder('—')
                    ->color(fn (?string $state): string => match ($state) {
                        'Rawat_Mandiri'   => 'success',
                        'Istirahat_Total' => 'warning',
                        'Rujukan_Luar'    => 'danger',
                        default           => 'gray',
                    }),
                TextColumn::make('berat_badan')
                    ->label('BB (kg)')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tinggi_badan')
                    ->label('TB (cm)')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal_periksa', 'desc')
            ->filters([
                SelectFilter::make('jenis_rekam')
                    ->label('Jenis Rekam')
                    ->options([
                        'keluhan' => 'Keluhan Sakit',
                        'rutin'   => 'Pemeriksaan Rutin',
                    ]),
                SelectFilter::make('kategori_keluhan')
                    ->label('Kategori Keluhan')
                    ->options([
                        'Demam'       => 'Demam',
                        'Batuk_Pilek' => 'Batuk / Pilek',
                        'Sakit_Perut' => 'Sakit Perut',
                        'Pusing'      => 'Pusing',
                        'Kulit_Gatal' => 'Kulit Gatal',
                        'Luka_Fisik'  => 'Luka Fisik',
                        'Lainnya'     => 'Lainnya',
                    ]),
                SelectFilter::make('status_pemulihan')
                    ->label('Status Pemulihan')
                    ->options([
                        'Rawat_Mandiri'   => 'Rawat Mandiri',
                        'Istirahat_Total' => 'Istirahat Total',
                        'Rujukan_Luar'    => 'Rujukan Luar',
                    ]),
                SelectFilter::make('santri')
                    ->label('Santri')
                    ->relationship('santri', 'nama_lengkap')
                    ->searchable(),
                Filter::make('tanggal_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        DatePicker::make('dari')->label('Dari')->native(false),
                        DatePicker::make('sampai')->label('Sampai')->native(false)->default(now()),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['dari'], fn ($q) => $q->whereDate('tanggal_periksa', '>=', $data['dari']))
                        ->when($data['sampai'], fn ($q) => $q->whereDate('tanggal_periksa', '<=', $data['sampai']))
                    )
                    ->indicateUsing(function (array $data) {
                        $indicators = [];
                        if ($data['dari']) {
                            $indicators[] = 'Dari: ' . Carbon::parse($data['dari'])->format('d M Y');
                        }
                        if ($data['sampai']) {
                            $indicators[] = 'Sampai: ' . Carbon::parse($data['sampai'])->format('d M Y');
                        }
                        return $indicators;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}