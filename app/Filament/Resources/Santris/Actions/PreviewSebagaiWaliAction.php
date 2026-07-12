<?php

namespace App\Filament\Resources\Santris\Actions;

use App\Models\Santri;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class PreviewSebagaiWaliAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'preview_sebagai_wali';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Preview sebagai Wali')
            ->icon('heroicon-o-eye')
            ->color('gray')
            ->url(fn (Santri $record) => route('admin.preview.wali', $record))
            ->openUrlInNewTab()
            ->tooltip('Lihat halaman laporan santri persis seperti yang dilihat wali, tanpa logout')
            ->visible(fn () => in_array(Auth::user()?->role, ['admin_pesantren', 'ustadz', 'super_admin']));
    }
}
