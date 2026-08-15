<?php

namespace App\Filament\Resources\PresensiHariLiburs\Pages;

use App\Filament\Resources\PresensiHariLiburs\PresensiHariLiburResource;
use App\Filament\Resources\PresensiHariLiburs\Schemas\PresensiHariLiburForm;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPresensiHariLiburs extends ListRecords
{
    protected static string $resource = PresensiHariLiburResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Form TAMBAH memakai rentang tanggal, berbeda dari form UBAH milik
            // Resource yang per hari. Libur sehari cukup mengisi tanggal mulai dan
            // selesai yang sama — jadi rentang adalah bentuk umumnya, bukan
            // fitur tambahan.
            CreateAction::make()
                ->label('Tambah Hari Libur')
                ->modalHeading('Tambah Hari Libur')
                ->modalSubmitActionLabel('Simpan')
                ->form(PresensiHariLiburForm::komponenRentang())
                ->fillForm(fn (): array => PresensiHariLiburResource::isianAwalRentang())
                ->using(PresensiHariLiburResource::simpanRentang()),
        ];
    }
}
