<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\MasterPengumumen\MasterPengumumanResource;
use App\Models\MasterPengumuman;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PengumumanWidget extends Widget
{
    protected string $view = 'filament.widgets.pengumuman-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    public static function canView(): bool
    {
        $role = auth()->user()?->role;

        if (! in_array($role, [
            UserRole::AdminPesantren->value,
            UserRole::Ustadz->value,
        ])) {
            return false;
        }

        return static::baseQuery()->exists();
    }

    protected static function baseQuery()
    {
        $pesantrenId = Auth::user()->pesantren_id;

        return MasterPengumuman::withoutGlobalScope('pesantren')
            ->where(function ($query) use ($pesantrenId) {
                $query->where('pesantren_id', $pesantrenId)
                    ->orWhereNull('pesantren_id');
            })
            ->forAdmin();
    }

    protected function getViewData(): array
    {
        $pengumuman = static::baseQuery()
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($item) => [
                'judul'   => $item->judul_maklumat,
                'tanggal' => $item->created_at->format('d M Y'),
                'isi'     => Str::limit(strip_tags($item->isi_maklumat), 100),
            ]);

        return [
            'pengumuman' => $pengumuman,
            'url'        => MasterPengumumanResource::getUrl('index'),
        ];
    }
}
