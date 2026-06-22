<?php

namespace App\Services;

class TahunAjaranOptions
{
    public static function current(): string
    {
        return now()->month >= 7
            ? now()->year . '/' . (now()->year + 1)
            : (now()->year - 1) . '/' . now()->year;
    }

    public static function options(int $before = 2, int $after = 1): array
    {
        $startYear = now()->month >= 7 ? now()->year : now()->year - 1;

        $options = [];
        for ($i = -$before; $i <= $after; $i++) {
            $year = $startYear + $i;
            $label = $year . '/' . ($year + 1);
            $options[$label] = $label;
        }

        return $options;
    }
}
