<?php

namespace App\Filament\Widgets;

use App\Enums\PaketLangganan;
use App\Enums\StatusBerlangganan;
use App\Enums\UserRole;
use App\Models\Pesantren;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Carbon;

class TenantListWidget extends TableWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    public static function canView(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Semua Pesantren')
            ->query(Pesantren::withoutGlobalScope('pesantren'))
            // Tanpa ORDER BY eksplisit, Postgres tidak menjamin urutan antar halaman —
            // baris yang sudah tampil di halaman 1 bisa muncul lagi di halaman 2.
            ->defaultSort('nama_pesantren')
            ->columns([
                TextColumn::make('nama_pesantren')
                    ->label('Nama Pesantren')
                    ->searchable()
                    ->sortable(),

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
            ])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspend')
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->requiresConfirmation()
                    ->modalHeading('Suspend Pesantren?')
                    ->modalDescription('Akses semua user pesantren ini akan diblokir.')
                    ->action(fn (Pesantren $record) => $record->update(['status_berlangganan' => StatusBerlangganan::Suspended->value]))
                    ->visible(fn (Pesantren $record): bool => in_array($record->status_berlangganan, [
                        StatusBerlangganan::Active->value,
                        StatusBerlangganan::Trial->value,
                    ])),

                // Mengubah status jadi 'active' saja TIDAK cukup untuk tenant expired:
                // expired_at yang masih di masa lalu membuat SaaSLifecycleLock membalik
                // statusnya kembali ke 'expired' pada request berikutnya, dan job malam
                // CheckExpiredTenants ikut membalikkannya SAMBIL mengirim ulang notifikasi
                // WhatsApp "masa aktif habis" ke admin pesantren yang baru saja membayar.
                // Karena itu masa aktif baru wajib ditetapkan bersamaan — pola yang sama
                // dipakai UpgradeOrderService::confirmOrder().
                Action::make('aktifkan')
                    ->label('Aktifkan')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->modalHeading('Aktifkan Pesantren?')
                    ->modalDescription('Status berlangganan diubah menjadi aktif. Tentukan juga masa aktif barunya, karena tanggal expired yang sudah lewat akan langsung mengunci tenant ini lagi.')
                    ->schema([
                        DatePicker::make('expired_at')
                            ->label('Aktif sampai')
                            ->required()
                            ->native(false)
                            ->minDate(now()->addDay())
                            ->default(fn (Pesantren $record): Carbon => $record->expired_at && $record->expired_at->isFuture()
                                ? $record->expired_at
                                : now()->addMonthNoOverflow())
                            ->helperText('Default: satu bulan dari hari ini, atau tanggal expired lama bila masih berlaku.'),
                    ])
                    ->action(fn (Pesantren $record, array $data) => $record->update([
                        'status_berlangganan' => StatusBerlangganan::Active->value,
                        'expired_at' => Carbon::parse($data['expired_at'])->endOfDay(),
                    ]))
                    ->visible(fn (Pesantren $record): bool => in_array($record->status_berlangganan, [
                        StatusBerlangganan::Suspended->value,
                        StatusBerlangganan::Expired->value,
                    ])),
            ]);
    }
}
