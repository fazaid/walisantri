<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\PlatformBrandingSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class PlatformLogoSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?int $navigationSort = 13;

    protected static ?string $navigationLabel = 'Logo & Favicon';

    protected static ?string $title = 'Logo & Favicon Platform';

    protected string $view = 'filament.pages.platform-logo-settings';

    protected static ?string $slug = 'platform-logo-settings-page';

    public $logo = null; // FileUpload state (single) hydrates sebagai array secara internal

    public $favicon = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function mount(): void
    {
        $this->form->fill([
            'logo'    => PlatformBrandingSetting::get('logo'),
            'favicon' => PlatformBrandingSetting::get('favicon'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Logo Walisantri.com')
                ->description('Logo ini dipakai di header landing page, halaman login, panel admin, dan invoice PDF. Kosongkan/hapus untuk kembali memakai logo bawaan (public/images/logo.svg).')
                ->schema([
                    FileUpload::make('logo')
                        ->label('File Logo (SVG)')
                        ->disk('public')
                        ->directory('branding-platform')
                        ->image()
                        ->acceptedFileTypes(['image/svg+xml'])
                        ->maxSize(512)
                        ->nullable(),
                ]),
            Section::make('Favicon Walisantri.com')
                ->description('Ikon kecil di tab browser untuk landing page, halaman login, area wali, dan panel admin. Kosongkan/hapus untuk kembali memakai favicon bawaan (public/favicon.svg).')
                ->schema([
                    FileUpload::make('favicon')
                        ->label('File Favicon (SVG)')
                        ->disk('public')
                        ->directory('branding-platform')
                        ->image()
                        ->acceptedFileTypes(['image/svg+xml'])
                        ->maxSize(256)
                        ->nullable(),
                ]),
        ]);
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
                            ->label('Simpan')
                            ->submit('save'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (['logo', 'favicon'] as $key) {
            $new = $state[$key] ?? null;
            $old = PlatformBrandingSetting::get($key);

            if ($old && $old !== $new) {
                Storage::disk('public')->delete($old);
            }

            PlatformBrandingSetting::set($key, $new);
        }

        Notification::make()
            ->title('Branding platform berhasil disimpan')
            ->success()
            ->send();
    }
}
