<?php

namespace App\Filament\Resources\Santris\Actions;

use App\Models\Santri;
use App\Observers\ActivityLogger;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class KirimMagicLinkAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'kirim_magic_link';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Link Wali')
            ->icon('heroicon-o-link')
            ->color('info')
            ->modalHeading('Link Portal Wali')
            ->modalDescription('Salin link ini dan kirimkan ke wali santri via WhatsApp atau media lain. Link berlaku permanen sampai di-regenerasi.')
            ->form(fn (Santri $record) => [
                TextInput::make('magic_link_url')
                    ->label('Link Portal Wali')
                    ->default($this->buildMagicLinkUrl($record))
                    ->readOnly()
                    ->copyable()
                    ->copyMessage('Link tersalin!')
                    ->copyMessageDuration(1500),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->visible(fn () => in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']))
            ->mountUsing(function (Santri $record) {
                ActivityLogger::log('magic_link.sent', $record, null, [
                    'wali_id' => $record->wali_santri_id,
                    'sent_by' => Auth::id(),
                ]);
            });
    }

    private function buildMagicLinkUrl(Santri $record): string
    {
        $appDomain = config('app.domain', 'app.walisantri.com');
        $scheme    = app()->environment('production') ? 'https' : 'http';

        return "{$scheme}://{$appDomain}/report/{$record->uuid}";
    }
}
