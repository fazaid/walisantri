<?php

namespace App\Filament\Resources\Presensis\Tables;

use App\Enums\StatusKehadiran;
use App\Enums\SumberPresensi;
use App\Filament\Resources\Presensis\PresensiResource;
use App\Filament\Support\SantriOptions;
use App\Models\Kelas;
use App\Models\Presensi;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PresensisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal')->date('d M Y')->sortable(),
                TextColumn::make('santri.nama_lengkap')->label('Santri')->searchable()->sortable(),
                TextColumn::make('kelas.nama_kelas')
                    ->label('Kelas')
                    ->placeholder('—')
                    ->toggleable(),
                // Tanpa kolom ini, presensi harian dan presensi jam pelajaran
                // tampak identik di tabel — dua baris "Hadir" untuk santri dan
                // tanggal yang sama, tanpa petunjuk apa pun bahwa yang satu jam
                // ke-3 Fiqih. Ikut tampil meski mode per jam mati: data lama tidak
                // hilang saat admin mematikannya kembali.
                TextColumn::make('jam_ke')
                    ->label('Jam')
                    ->formatStateUsing(fn (int $state): string => $state === Presensi::HARIAN
                        ? 'Harian'
                        : 'Jam ke-'.$state)
                    ->description(fn ($record) => $record->jam_ke === Presensi::HARIAN
                        ? null
                        : $record->mataPelajaran?->nama_mapel)
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (StatusKehadiran $state) => $state->label())
                    ->color(fn (StatusKehadiran $state) => $state->color()),
                TextColumn::make('sumber')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn (SumberPresensi $state) => $state->label())
                    ->color(fn (SumberPresensi $state) => $state->color())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('catatan')->label('Catatan')->limit(40)->placeholder('—')->toggleable(),
            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                SelectFilter::make('status')->label('Status')->options(StatusKehadiran::options()),

                SelectFilter::make('jenis')
                    ->label('Jenis')
                    ->options([
                        'harian' => 'Presensi harian',
                        'jam' => 'Jam pelajaran',
                    ])
                    // Bukan SelectFilter pada kolom jam_ke langsung: yang ingin
                    // dipisahkan orang adalah "harian" versus "jam pelajaran",
                    // bukan jam ke-1 versus jam ke-2.
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'harian' => $query->where('jam_ke', Presensi::HARIAN),
                        'jam' => $query->where('jam_ke', '>', Presensi::HARIAN),
                        default => $query,
                    }),

                SelectFilter::make('santri_id')
                    ->label('Santri')
                    ->options(fn () => SantriOptions::aktifUntukPengguna())
                    ->searchable(),

                SelectFilter::make('kelas_id')
                    ->label('Kelas')
                    ->options(fn () => Kelas::orderBy('nama_kelas')->pluck('nama_kelas', 'id')),

                Filter::make('tanpa_kelas')
                    ->label('Tanpa kelas')
                    // Sebelum modul ini tidak ada satu pun whereNull('kelas_id') di repo,
                    // sehingga santri yang kehilangan kelasnya tidak bisa ditemukan lewat
                    // UI sama sekali. Filter ini pintu pertamanya.
                    ->query(fn (Builder $query) => $query->whereNull('kelas_id')),
            ])
            // Filament v5 hanya membaca policy untuk mengunci action, dan aplikasi ini
            // tidak punya policy — jadi aturan "hapus khusus admin" dari
            // HasAdminUstadzAccess dipasang manual di sini.
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->modalWidth(Width::TwoExtraLarge),
                DeleteAction::make()
                    ->visible(fn ($record): bool => PresensiResource::canDelete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => PresensiResource::canDeleteAny()),
                ]),
            ]);
    }
}
