<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Mutabaah;
use App\Models\KesantrianAmalMaster;
use App\Models\KesantrianMutabaah;
use App\Models\Santri;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MutabaahHarianPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static ?string $cluster = Mutabaah::class;

    protected static ?string $navigationLabel = 'Isi Harian';

    protected static ?string $title = 'Isi Mutabaah Harian';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'isi-harian';

    protected string $view = 'filament.pages.mutabaah-harian-page';

    public ?string $tanggal = null;

    public array $rows = [];

    public const STATUS_UDZUR_OPTIONS = [
        'Tidak'        => 'Tidak',
        'Sakit'        => 'Sakit',
        'Haid'         => 'Haid',
        'Izin_Pulang'  => 'Izin Pulang',
        'Tugas_Pondok' => 'Tugas Pondok',
    ];

    protected ?Collection $amalMasterList = null;

    public static function canAccess(): bool
    {
        return in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']);
    }

    public function mount(): void
    {
        $tanggal = now()->toDateString();

        $this->form->fill([
            'tanggal' => $tanggal,
            'rows'    => $this->buildRows($tanggal),
        ]);
    }

    protected function amalMasterList(): Collection
    {
        return $this->amalMasterList ??= KesantrianAmalMaster::where('pesantren_id', Auth::user()?->pesantren_id)
            ->where('aktif', true)
            ->orderBy('urutan')
            ->get();
    }

    protected function getSantriQuery(): Builder
    {
        $query = Santri::where('status_aktif', true);

        if (Auth::user()?->role === 'ustadz') {
            $query->where('pembimbing_ustadz_id', Auth::id());
        }

        return $query;
    }

    protected function buildRows(?string $tanggal): array
    {
        if (! $tanggal) {
            return [];
        }

        $santriList = $this->getSantriQuery()->orderBy('nama_lengkap')->get(['id', 'nama_lengkap']);

        $existing = KesantrianMutabaah::where('tanggal', $tanggal)
            ->whereIn('santri_id', $santriList->pluck('id'))
            ->get()
            ->keyBy('santri_id');

        $masterList = $this->amalMasterList();

        return $santriList->map(function (Santri $santri) use ($existing, $masterList) {
            $rec    = $existing->get($santri->id);
            $amalan = $rec?->amalan ?? [];

            $defaultAmalan = $masterList->mapWithKeys(function (KesantrianAmalMaster $item) use ($amalan) {
                $default = $item->tipe === 'hitungan' ? $item->nilai_maks : true;

                return [$item->kode => $amalan[$item->kode] ?? $default];
            })->all();

            return [
                'santri_id'    => $santri->id,
                'nama'         => $santri->nama_lengkap,
                'amalan'       => $defaultAmalan,
                'status_udzur' => $rec?->status_udzur ?? 'Tidak',
            ];
        })->values()->toArray();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->required()
                    ->maxDate(now())
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('rows', $this->buildRows($state));
                    }),

                Repeater::make('rows')
                    ->hiddenLabel()
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->itemLabel(fn (array $state): ?string => $state['nama'] ?? null)
                    ->schema([
                        Hidden::make('santri_id'),
                        Hidden::make('nama'),

                        Select::make('status_udzur')
                            ->label('Udzur')
                            ->options(self::STATUS_UDZUR_OPTIONS)
                            ->required(),

                        ...$this->amalanFields(),
                    ])
                    ->columns(['default' => 2, 'md' => 4]),
            ]);
    }

    protected function amalanFields(): array
    {
        return $this->amalMasterList()->map(function (KesantrianAmalMaster $item) {
            $label = trim(($item->icon ? $item->icon.' ' : '').$item->label);

            if ($item->tipe === 'hitungan') {
                return TextInput::make("amalan.{$item->kode}")
                    ->label("{$label} (dari {$item->nilai_maks})")
                    ->numeric()
                    ->minValue(0)
                    ->maxValue($item->nilai_maks)
                    ->required();
            }

            return Toggle::make("amalan.{$item->kode}")
                ->label($label)
                ->inline(false);
        })->all();
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Simpan Semua')
                            ->submit('save'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $rows = $data['rows'] ?? [];

        foreach ($rows as $row) {
            KesantrianMutabaah::updateOrCreate(
                [
                    'santri_id' => $row['santri_id'],
                    'tanggal'   => $data['tanggal'],
                ],
                [
                    'amalan'       => $row['amalan'] ?? [],
                    'status_udzur' => $row['status_udzur'],
                ]
            );
        }

        Notification::make()
            ->title('Mutabaah tersimpan untuk '.count($rows).' santri.')
            ->success()
            ->send();
    }
}
