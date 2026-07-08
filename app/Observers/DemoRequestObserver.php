<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Filament\Resources\DemoRequests\DemoRequestResource;
use App\Models\DemoRequest;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class DemoRequestObserver
{
    public function creating(DemoRequest $demoRequest): void
    {
        $match = DemoRequest::where(function ($query) use ($demoRequest) {
                $query->where('email', $demoRequest->email)
                    ->orWhere('no_hp', $demoRequest->no_hp);
            })
            ->where('created_at', '>=', now()->subDays(30))
            ->latest('created_at')
            ->first();

        if ($match) {
            $demoRequest->duplicate_of_id = $match->id;
        }
    }

    public function created(DemoRequest $demoRequest): void
    {
        $superAdmins = User::where('role', UserRole::SuperAdmin->value)->get();

        if ($superAdmins->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Lead demo baru: '.$demoRequest->nama_pesantren)
            ->body(trim("{$demoRequest->kota} • {$demoRequest->no_hp}", ' •'))
            ->icon('heroicon-o-sparkles')
            ->actions([
                Action::make('view')
                    ->label('Lihat')
                    ->url(DemoRequestResource::getUrl('view', ['record' => $demoRequest]))
                    ->markAsRead(),
            ])
            ->sendToDatabase($superAdmins);
    }
}
