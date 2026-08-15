<?php

namespace App\Filament\Resources\PresensiIzins\Pages;

use App\Enums\StatusPengajuanIzin;
use App\Filament\Resources\PresensiIzins\PresensiIzinResource;
use App\Models\PresensiIzin;
use App\Services\PresensiIzinService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ListPresensiIzins extends ListRecords
{
    protected static string $resource = PresensiIzinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Pintu kedua: admin mencatat izin langsung, mis. saat santri melapor
            // lisan. Berbeda dari pengajuan wali, yang ini langsung berstatus
            // disetujui — orang yang mencatatnya adalah orang yang berwenang
            // menyetujuinya, jadi meminta persetujuan terpisah cuma ritual kosong.
            CreateAction::make()
                ->label('Catat Izin')
                ->modalHeading('Catat Izin Santri')
                ->modalSubmitActionLabel('Simpan & Setujui')
                ->modalWidth(Width::TwoExtraLarge)
                ->using(function (array $data): Model {
                    $izin = PresensiIzin::create($data + [
                        'status' => StatusPengajuanIzin::Diajukan,
                        // NULL menandai "dicatat admin", bukan diajukan wali.
                        'diajukan_oleh' => null,
                    ]);

                    return app(PresensiIzinService::class)->setujui($izin, Auth::user());
                }),
        ];
    }
}
