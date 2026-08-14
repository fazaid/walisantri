<?php

namespace App\Filament\Resources\Pesantrens\Tables;

use App\Enums\PaketLangganan;
use App\Enums\StatusBerlangganan;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Pesantren;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PesantrensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_pesantren')
                    ->label('Nama Pesantren')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),

                TextColumn::make('paket_langganan')
                    ->label('Paket')
                    ->badge()
                    ->color(fn (string $state): string => PaketLangganan::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => PaketLangganan::tryFrom($state)?->label() ?? $state)
                    ->sortable(),

                TextColumn::make('status_berlangganan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => StatusBerlangganan::tryFrom($state)?->color() ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => StatusBerlangganan::tryFrom($state)?->label() ?? $state)
                    ->sortable(),

                TextColumn::make('max_santri_kuota')
                    ->label('Maks. Santri')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('expired_at')
                    ->label('Expired')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->placeholder('-'),

                // Menjawab "pesantren mana yang tidak terjangkau", bukan "user mana
                // yang belum menekan tautan" — karena itu ditaruh di sini, bukan di
                // tabel Pengguna. Sejak WhatsApp dimatikan, alamat email yang keliru
                // berarti tagihan & peringatan expired tidak pernah sampai (§12.2).
                IconColumn::make('admin_terverifikasi')
                    ->label('Email Admin')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckBadge)
                    ->falseIcon(Heroicon::OutlinedExclamationTriangle)
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (bool $state): string => $state
                        ? 'Alamat email admin sudah dikonfirmasi'
                        : 'Belum dikonfirmasi — pesantren ini berisiko tidak menerima tagihan & peringatan expired')
                    ->toggleable(),
            ])
            // withExists, bukan eager load relasi utuh: tabel ini sebelumnya tidak
            // punya eager loading sama sekali, dan memuat seluruh users hanya untuk
            // satu ikon akan melahirkan N+1 di setiap baris.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withExists([
                'users as admin_terverifikasi' => fn (Builder $q): Builder => $q
                    ->where('role', UserRole::AdminPesantren->value)
                    ->whereNotNull('email_verified_at'),
            ]))
            ->filters([
                SelectFilter::make('paket_langganan')
                    ->label('Paket')
                    ->options(PaketLangganan::options())
                    ->multiple(),

                SelectFilter::make('status_berlangganan')
                    ->label('Status')
                    ->options(StatusBerlangganan::options())
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->modalWidth(Width::TwoExtraLarge),

                // Menghapus pesantren TIDAK bisa dibatalkan: Pesantren tidak memakai
                // SoftDeletes, dan seluruh tabel tenant ber-FK cascadeOnDelete (santri
                // dan semua turunannya: nilai, tahfidz, mutabaah, tagihan SPP, uang saku,
                // prestasi, order, invoice). Akun penggunanya justru nullOnDelete —
                // selamat sebagai yatim dengan pesantren_id NULL, permanen kena 403 di
                // SaaSLifecycleLock, dan emailnya yang unik global tetap memblokir
                // pendaftaran ulang. Karena itu konfirmasi generik "Yakin?" tidak cukup:
                // super admin harus mengetik ulang nama pesantrennya.
                DeleteAction::make()
                    ->modalHeading(fn (Pesantren $record): string => "Hapus {$record->nama_pesantren}?")
                    ->modalDescription(fn (Pesantren $record): string => 'Tindakan ini permanen dan tidak bisa dibatalkan. Seluruh data pesantren ini akan ikut terhapus: '
                        .$record->santri_count_cache.' santri beserta nilai, hafalan, mutaba\'ah, tagihan SPP, dan uang sakunya, termasuk riwayat pesanan & invoice. '
                        .'Akun penggunanya tidak ikut terhapus, tetapi akan kehilangan akses selamanya.')
                    ->schema([
                        TextInput::make('konfirmasi_nama')
                            ->label('Ketik nama pesantren untuk mengonfirmasi')
                            ->required()
                            ->autocomplete(false)
                            ->helperText(fn (Pesantren $record): string => $record->nama_pesantren)
                            ->rule(fn (Pesantren $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                                if (trim((string) $value) !== $record->nama_pesantren) {
                                    $fail('Nama pesantren tidak cocok.');
                                }
                            }),
                    ])
                    ->modalSubmitActionLabel('Hapus permanen')
                    // activity_logs.pesantren_id ber-FK nullOnDelete, jadi kolom itu PASTI
                    // jadi NULL begitu pesantrennya terhapus. Identitas tenant karena itu
                    // disimpan di auditable_id (bigInteger biasa, bukan FK) dan di dalam
                    // old_values — keduanya selamat dari cascade.
                    ->before(function (Pesantren $record): void {
                        ActivityLog::create([
                            'pesantren_id' => $record->id,
                            'user_id' => auth()->id(),
                            'event' => 'pesantren.deleted',
                            'auditable_type' => Pesantren::class,
                            'auditable_id' => $record->id,
                            'old_values' => [
                                'id' => $record->id,
                                'nama_pesantren' => $record->nama_pesantren,
                                'slug' => $record->slug,
                                'paket_langganan' => $record->paket_langganan,
                                'santri_count_cache' => $record->santri_count_cache,
                            ],
                            'ip_address' => request()?->ip(),
                            'user_agent' => request()?->userAgent(),
                            'created_at' => now(),
                        ]);
                    }),
            ]);
        // Sengaja tanpa toolbarActions: DeleteBulkAction dibuang karena menghapus
        // banyak tenant sekaligus tidak punya kasus pakai yang sah, sementara satu
        // salah-centang berarti kehilangan data beberapa pesantren sekaligus.
    }
}
