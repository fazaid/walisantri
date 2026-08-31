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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PesantrensTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Identitas stabil satu-satunya milik pesantren: slug bisa berganti
                // (ada tabel slug_releases) dan nama bisa disunting, sementara id
                // dipakai apa adanya di activity_logs.auditable_id. Disembunyikan
                // secara bawaan supaya tabel tidak melebar untuk angka yang hanya
                // dibutuhkan saat menautkan tenant dengan tiket dukungan atau log.
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nama_pesantren')
                    ->label('Nama Pesantren')
                    ->searchable()
                    ->sortable(),

                // Siapa yang bisa dihubungi dari pesantren ini — pertanyaan yang
                // benar-benar dibawa super admin ke halaman ini saat menagih,
                // memperingatkan expired, atau menindaklanjuti pesanan upgrade.
                // Menunjuk relasi adminUtama yang sama dengan DataPesantrenExport,
                // jadi nama yang terbaca di layar dan yang tercetak di berkas Excel
                // selalu orang yang sama.
                TextColumn::make('adminUtama.name')
                    ->label('Admin Pesantren')
                    // Bukan kasus teoretis: DeleteAction di bawah meninggalkan user
                    // yatim (users.pesantren_id nullOnDelete), dan pesantren yang
                    // dibuat lewat CreateAction lahir tanpa admin sama sekali.
                    ->placeholder('— belum ada admin —')
                    // description() menerima Htmlable, jadi email & no. HP bisa
                    // ditumpuk masing-masing satu baris di bawah nama. Tiap potong
                    // di-e() sendiri: isinya datang dari input pengguna dan tidak
                    // boleh pernah dirender sebagai markup.
                    ->description(function (Pesantren $record): ?Htmlable {
                        $admin = $record->adminUtama;

                        if ($admin === null) {
                            return null;
                        }

                        // users.email nullable sejak central/2026_07_09_100001, dan
                        // phone_number pun bisa kosong (SegarkanSandboxCommand
                        // sengaja mengosongkannya untuk tenant sandbox). Baris kosong
                        // lebih terbaca daripada tumpukan '—'.
                        $kontak = array_filter([$admin->email, $admin->phone_number], 'filled');

                        if ($kontak === []) {
                            return null;
                        }

                        return new HtmlString(implode('<br>', array_map('e', $kontak)));
                    })
                    // Pencarian ditulis sendiri, tidak menumpang pencarian relasi
                    // bawaan: adminUtama adalah relasi ofMany bersubquery agregat.
                    // Sengaja menyapu SELURUH admin pesantren, bukan hanya admin
                    // utama — super admin yang mengetik email admin kedua tetap
                    // harus menemukan pesantrennya.
                    //
                    // lower() di kedua sisi, bukan `ilike`: produksi memang Postgres
                    // tapi suite tes berjalan di SQLite yang tidak mengenal operator
                    // itu. Bentuk ini juga menyamai perilaku kolom searchable bawaan
                    // Filament, yang di Postgres memang case-insensitive.
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $istilah = '%'.Str::lower($search).'%';

                        return $query->whereHas('users', fn (Builder $user): Builder => $user
                            ->where('role', UserRole::AdminPesantren->value)
                            ->where(fn (Builder $cocok): Builder => $cocok
                                ->whereRaw('lower(users.name) like ?', [$istilah])
                                ->orWhereRaw('lower(users.email) like ?', [$istilah])
                                ->orWhereRaw('lower(users.phone_number) like ?', [$istilah])));
                    })
                    // Tidak sortable: mengurutkan lewat relasi ofMany menuntut join
                    // subquery yang rapuh, sementara tidak ada yang mencari daftar
                    // pesantren terurut menurut nama adminnya.
                    ->toggleable(),

                // Turun jadi kolom tersembunyi, seperti ID: slug adalah identitas
                // teknis untuk routing subdomain yang bisa berganti (ada tabel
                // slug_releases), bukan jawaban atas pertanyaan yang membawa super
                // admin ke halaman ini. Tetap searchable supaya slug yang ditempel
                // ke kotak pencarian tetap menemukan tenantnya walau kolomnya
                // tidak tampak.
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),

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
            // Untuk ikon verifikasi dipakai withExists, bukan eager load relasi
            // utuh: memuat seluruh users hanya demi satu ikon berarti mengangkut
            // ratusan wali santri per baris. Yang di-eager load hanyalah adminUtama,
            // satu baris per pesantren.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                // Kolom Admin Pesantren membaca relasi ini di setiap baris; tanpa
                // eager load ia menembak satu query user per baris — N+1 yang persis
                // sama dengan yang dihindari withExists di bawahnya.
                ->with('adminUtama')
                ->withExists([
                    'users as admin_terverifikasi' => fn (Builder $q): Builder => $q
                        ->where('role', UserRole::AdminPesantren->value)
                        ->whereNotNull('email_verified_at'),
                ]))
            // Tabel ini sebelumnya tidak punya defaultSort sama sekali, jadi Filament
            // jatuh ke fallback orderBy(id, 'asc'): pesantren yang baru mendaftar
            // selalu terdampar di halaman terakhir — persis kebalikan dari yang
            // dicari super admin saat membuka halaman ini.
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('paket_langganan')
                    ->label('Paket')
                    ->options(PaketLangganan::options())
                    ->multiple(),

                SelectFilter::make('status_berlangganan')
                    ->label('Status')
                    ->options(StatusBerlangganan::options())
                    ->multiple(),

                // Tenant sandbox publik disembunyikan secara bawaan supaya daftar
                // ini terbaca sebagai daftar pelanggan. Tetap bisa dimunculkan —
                // menyembunyikannya tanpa jalan keluar akan membuat super admin
                // tidak punya cara menyuntingnya lewat panel.
                TernaryFilter::make('is_demo')
                    ->label('Tenant demo')
                    ->placeholder('Sembunyikan tenant demo')
                    ->trueLabel('Hanya tenant demo')
                    ->falseLabel('Hanya pelanggan')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('is_demo', true),
                        false: fn (Builder $query): Builder => $query->where('is_demo', false),
                        blank: fn (Builder $query): Builder => $query->where('is_demo', false),
                    ),
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
