<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\MasterPengumumanCentral;
use Filament\Widgets\Widget;

class PengumumanCentralWidget extends Widget
{
    protected string $view = 'filament.widgets.pengumuman-central-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -2;

    public static function canView(): bool
    {
        $role = auth()->user()?->role;

        if (! in_array($role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ])) {
            return false;
        }

        return MasterPengumumanCentral::where('is_active', true)->exists();
    }

    protected function getViewData(): array
    {
        $pengumuman = MasterPengumumanCentral::where('is_active', true)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(fn ($item) => [
                'judul'   => $item->judul_maklumat,
                'tanggal' => $item->created_at->format('d M Y'),
                'isi'     => $item->isi_maklumat,
            ]);

        return ['pengumuman' => $pengumuman];
    }
}
