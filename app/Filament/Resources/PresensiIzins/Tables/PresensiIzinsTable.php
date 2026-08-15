<?php

namespace App\Filament\Resources\PresensiIzins\Tables;

use App\Enums\JenisIzin;
use App\Enums\StatusPengajuanIzin;
use App\Filament\Resources\PresensiIzins\PresensiIzinResource;
use App\Models\PresensiIzin;
use App\Services\PresensiIzinService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PresensiIzinsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('santri.nama_lengkap')->label('Santri')->searchable()->sortable(),
                TextColumn::make('jenis')->label('Jenis')->badge()
                    ->formatStateUsing(fn (JenisIzin $state) => $state->label())
                    ->color(fn (JenisIzin $state) => $state->color()),
                TextColumn::make('tanggal_mulai')->label('Mulai')->date('d M Y')->sortable(),
                TextColumn::make('tanggal_selesai')->label('Selesai')->date('d M Y'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (StatusPengajuanIzin $state) => $state->label())
                    ->color(fn (StatusPengajuanIzin $state) => $state->color()),
                TextColumn::make('diajukan_oleh')
                    ->label('Asal')
                    ->formatStateUsing(fn (?int $state) => $state ? 'Wali' : 'Admin')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Status')->options(StatusPengajuanIzin::options()),
                SelectFilter::make('jenis')->label('Jenis')->options(JenisIzin::options()),
            ])
            ->recordActions([
                ViewAction::make(),

                Action::make('setujui')
                    ->label('Setujui')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (PresensiIzin $record): bool => $record->menungguPersetujuan())
                    ->requiresConfirmation()
                    ->modalHeading('Setujui pengajuan izin?')
                    ->modalDescription('Presensi untuk tanggal-tanggal tersebut akan terisi otomatis. Hari libur dilewati.')
                    ->form([Textarea::make('catatan')->label('Catatan (opsional)')->rows(2)])
                    ->action(function (PresensiIzin $record, array $data): void {
                        app(PresensiIzinService::class)->setujui($record, Auth::user(), $data['catatan'] ?? null);

                        Notification::make()->title('Izin disetujui, presensi terisi.')->success()->send();
                    }),

                Action::make('tolak')
                    ->label('Tolak')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (PresensiIzin $record): bool => $record->menungguPersetujuan())
                    ->requiresConfirmation()
                    ->form([Textarea::make('catatan')->label('Alasan penolakan')->rows(2)->required()])
                    ->action(function (PresensiIzin $record, array $data): void {
                        app(PresensiIzinService::class)->tolak($record, Auth::user(), $data['catatan']);

                        Notification::make()->title('Pengajuan ditolak.')->warning()->send();
                    }),

                Action::make('batalkan')
                    ->label('Batalkan')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('warning')
                    ->visible(fn (PresensiIzin $record): bool => $record->sudahDisetujui())
                    ->requiresConfirmation()
                    ->modalHeading('Batalkan izin yang sudah disetujui?')
                    ->modalDescription('Baris presensi yang dibuat izin ini akan dihapus. Baris yang sudah disunting ustadz secara manual tidak ikut terhapus.')
                    ->form([Textarea::make('catatan')->label('Alasan pembatalan')->rows(2)])
                    ->action(function (PresensiIzin $record, array $data): void {
                        app(PresensiIzinService::class)->batalkan($record, Auth::user(), $data['catatan'] ?? null);

                        Notification::make()->title('Izin dibatalkan, presensi turunannya dihapus.')->warning()->send();
                    }),

                EditAction::make()
                    // Mengubah rentang izin yang sudah disetujui akan membuat baris
                    // presensi lama menggantung tanpa induk yang cocok; batalkan
                    // lebih dulu, lalu ajukan yang benar.
                    ->visible(fn (PresensiIzin $record): bool => $record->menungguPersetujuan()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => PresensiIzinResource::canDeleteAny()),
                ]),
            ]);
    }
}
