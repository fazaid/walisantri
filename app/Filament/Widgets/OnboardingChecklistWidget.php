<?php

namespace App\Filament\Widgets;

use App\Enums\OnboardingStep;
use App\Enums\UserRole;
use App\Filament\Pages\PesantrenSettingsPage;
use App\Filament\Resources\MasterPengumumen\MasterPengumumanResource;
use App\Filament\Resources\Santris\SantriResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class OnboardingChecklistWidget extends Widget
{
    protected string $view = 'filament.widgets.onboarding-checklist-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -2;

    public static function canView(): bool
    {
        $user = Auth::user();

        if ($user?->role !== UserRole::AdminPesantren->value) {
            return false;
        }

        return $user->pesantren && ! $user->pesantren->isOnboardingComplete();
    }

    protected function getViewData(): array
    {
        $pesantren = Auth::user()->pesantren;

        $items = collect(OnboardingStep::cases())->map(fn (OnboardingStep $step) => [
            'label'    => $step->label(),
            'done'     => $pesantren->hasCompletedOnboardingStep($step),
            'required' => $step->isRequired(),
            'url'      => $this->urlFor($step),
        ]);

        return [
            'items'         => $items,
            'requiredDone'  => $items->where('required', true)->where('done', true)->count(),
            'requiredTotal' => count(OnboardingStep::required()),
        ];
    }

    private function urlFor(OnboardingStep $step): string
    {
        return match ($step) {
            OnboardingStep::Profil     => PesantrenSettingsPage::getUrl(),
            OnboardingStep::Ustadz     => UserResource::getUrl('create'),
            OnboardingStep::Santri     => SantriResource::getUrl('create'),
            OnboardingStep::MagicLink  => SantriResource::getUrl('index'),
            OnboardingStep::Pengumuman => MasterPengumumanResource::getUrl('create'),
        };
    }
}
