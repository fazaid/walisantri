<?php

namespace App\Services;

class TrendBulanan
{
    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function duaBelasBulanTerakhir(): array
    {
        $bulan = [];

        for ($i = 11; $i >= 0; $i--) {
            $tanggal = now()->subMonths($i);
            $bulan[] = ['key' => $tanggal->format('Y-m'), 'label' => $tanggal->translatedFormat('M Y')];
        }

        return $bulan;
    }
}
