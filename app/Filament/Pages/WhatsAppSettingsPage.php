<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
use UnitEnum;

class WhatsAppSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Langganan';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'Pengaturan WhatsApp';

    protected static ?string $title = 'Pengaturan WhatsApp';

    protected string $view = 'filament.pages.whatsapp-settings';

    protected static ?string $slug = 'whatsapp-settings-page';

    public bool $reminder_expired_enabled = true;

    public string $reminder_expired_template = '';

    public static function canAccess(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin->value;
    }

    public function mount(): void
    {
        $this->form->fill([
            'reminder_expired_enabled' => WhatsAppSetting::get('reminder_expired_enabled'),
            'reminder_expired_template' => WhatsAppMessageTemplate::get('reminder_expired'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Reminder Billing')
                ->description('Pengecualian sempit atas kebijakan WhatsApp manual (PRD §12) — hanya mengatur reminder billing H-3/H-1, tidak memengaruhi fitur WA lain (Magic Link, broadcast wali, rapor, dsb).')
                ->schema([
                    Toggle::make('reminder_expired_enabled')
                        ->label('Kirim reminder WhatsApp H-3/H-1 sebelum langganan expired')
                        ->helperText('Matikan sebagai kill-switch cepat, misalnya saat gateway Fonnte bermasalah atau kuota habis, tanpa perlu deploy ulang.')
                        ->default(true),
                    Textarea::make('reminder_expired_template')
                        ->label('Template pesan reminder')
                        ->required()
                        ->rows(8)
                        ->helperText('Placeholder yang bisa dipakai: {nama_pesantren}, {sisa_hari}, {tanggal_expired}, {link_billing}.'),
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
                            ->label('Simpan Pengaturan')
                            ->submit('save'),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        WhatsAppSetting::set('reminder_expired_enabled', (bool) $state['reminder_expired_enabled']);
        WhatsAppMessageTemplate::set('reminder_expired', $state['reminder_expired_template']);

        Notification::make()
            ->title('Pengaturan WhatsApp berhasil disimpan')
            ->success()
            ->send();
    }
}
