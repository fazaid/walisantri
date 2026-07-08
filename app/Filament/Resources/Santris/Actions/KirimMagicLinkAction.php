<?php

namespace App\Filament\Resources\Santris\Actions;

use App\Enums\OnboardingStep;
use App\Models\Santri;
use App\Observers\ActivityLogger;
use Filament\Actions\Action;
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
            ->modalContent(fn (Santri $record) => view('filament.actions.magic-link-modal', [
                'url' => $this->buildMagicLinkUrl($record),
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->visible(fn () => in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz']))
            ->mountUsing(function (Santri $record): void {
                ActivityLogger::log('magic_link.viewed', $record, null, [
                    'wali_id' => $record->wali_santri_id,
                    'viewed_by' => Auth::id(),
                ]);

                $record->pesantren?->completeOnboardingStep(OnboardingStep::MagicLink);
            });
    }

    private function buildMagicLinkUrl(Santri $record): string
    {
        $appDomain = config('app.domain', 'app.walisantri.com');
        $scheme    = app()->environment('production') ? 'https' : 'http';

        return "{$scheme}://{$appDomain}/report/{$record->uuid}";
    }
}
