<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\PenugasanUstadz;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => UserRole::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => UserRole::tryFrom($state)?->label() ?? $state)
                    ->sortable(),

                TextColumn::make('penugasan')
                    ->label('Penugasan')
                    ->state(fn (User $record): array => PenugasanUstadz::ringkasan($record))
                    ->badge()
                    ->color('success')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('pesantren.nama_pesantren')
                    ->label('Pesantren')
                    ->searchable()
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Filter Role')
                    ->options(UserRole::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->modalWidth(Width::TwoExtraLarge)
                    ->mutateDataUsing(UserResource::paksaTenantSaatUbah()),
                DeleteAction::make()
                    // Kasus struktural (akun sendiri, super admin terakhir) tidak bisa
                    // diselesaikan pengguna, jadi tombolnya disembunyikan sekalian.
                    // Keterkaitan santri BISA diselesaikan, jadi tombolnya tetap tampil
                    // dan alasannya disampaikan lewat notifikasi di ->before().
                    ->visible(fn (User $record): bool => UserResource::alasanSembunyikanHapus($record) === null)
                    ->before(function (User $record, DeleteAction $action) {
                        $alasan = UserResource::alasanTidakBisaDihapus($record);

                        if ($alasan === null) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title('Pengguna tidak bisa dihapus')
                            ->body($alasan)
                            ->persistent()
                            ->send();

                        $action->cancel();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Satu pengguna yang tertaut santri sudah cukup membuat seluruh
                    // batch gagal dengan SQLSTATE 23503, jadi batch diperiksa lebih
                    // dulu dan dibatalkan utuh — lebih jelas daripada terhapus separuh.
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action) {
                            $terhalang = $records
                                ->map(fn (User $user): ?string => ($alasan = UserResource::alasanTidakBisaDihapus($user))
                                    ? "{$user->name}: {$alasan}"
                                    : null)
                                ->filter()
                                ->values();

                            if ($terhalang->isEmpty()) {
                                return;
                            }

                            Notification::make()
                                ->danger()
                                ->title($terhalang->count().' pengguna tidak bisa dihapus')
                                ->body($terhalang->implode(' '))
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }),
                ]),
            ]);
    }
}
