<?php

// ============================================================
// FILE 3: app/Filament/Resources/KesantrianKarakterRapors/Tables/KesantrianKarakterRaporsTable.php
// ============================================================

namespace App\Filament\Resources\KesantrianKarakterRapors\Tables;

use App\Filament\Resources\KesantrianKarakterRapors\KesantrianKarakterRaporResource;
use App\Filament\Support\SantriOptions;
use App\Services\TahunAjaranOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KesantrianKarakterRaporsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal_input')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('santri.nama_lengkap')->label('Santri')->searchable()->sortable(),
                TextColumn::make('tahun_ajaran')->label('Tahun Ajaran')->sortable(),
                TextColumn::make('periode')
                    ->label('Periode')
                    ->formatStateUsing(fn (string $state) => str_replace('_', ' ', $state)),
                TextColumn::make('adab_ustadz')->label('Adab Ustadz')->badge(),
                TextColumn::make('kepribadian_kedisiplinan')->label('Kedisiplinan')->badge(),
            ])
            ->defaultSort('tanggal_input', 'desc')
            ->filters([
                SelectFilter::make('periode')->label('Periode')
                    ->options(TahunAjaranOptions::periodeOptions()),
                SelectFilter::make('santri_id')->label('Santri')
                    ->options(fn () => SantriOptions::aktifUntukPengguna())
                    ->searchable(),
            ])
            // Filament v4 hanya membaca policy untuk mengunci action, dan aplikasi
            // ini tidak punya policy — jadi aturan "hapus khusus admin" dari
            // HasAdminUstadzAccess dipasang manual di sini.
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->modalWidth(Width::FourExtraLarge)
                    ->before(KesantrianKarakterRaporResource::guardDuplikat()),
                DeleteAction::make()
                    ->visible(fn ($record): bool => KesantrianKarakterRaporResource::canDelete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => KesantrianKarakterRaporResource::canDeleteAny()),
                ]),
            ]);
    }
}
