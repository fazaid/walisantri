<?php

namespace App\Filament\Concerns;

use App\Enums\UserRole;
use App\Models\Presensi;
use App\Support\PenugasanUstadz;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Membatasi query presensi ke cakupan penugasan ustadz yang login.
 *
 * `ScopesQueryToUstadzSantri` yang sudah ada tidak bisa dipakai: ia menyaring SATU
 * kolom (`ustadzScopeColumn()`), sedangkan aturan presensi bercabang berdasarkan ISI
 * baris — wali kelas memegang presensi harian kelasnya, pengampu mapel memegang
 * presensi jam pelajaran yang ia ampu, dan keduanya bisa dipegang orang yang sama.
 *
 * Pembimbing halaqah sengaja NOL akses (§5.4): halaqah adalah relasi pembinaan
 * hafalan dan adab, bukan kehadiran kelas. Bila seorang pembimbing memang perlu
 * mengabsen, admin cukup menjadikannya wali kelas — satu klik, dan jejaknya terbaca
 * di PenugasanUstadz::ringkasan().
 *
 * Query dan route-model binding ditutup di SATU trait, berbeda dari pasangan
 * ScopesQueryToUstadzSantri/ScopesRouteBindingToUstadzSantri: memecahnya akan
 * menduplikasi logika cabang di bawah ini, dan cabang itulah bagian yang paling
 * mudah menyimpang saat salah satunya disunting.
 */
trait ScopesQueryToPresensiUstadz
{
    public static function getEloquentQuery(): Builder
    {
        return static::terapkanCakupanPresensi(parent::getEloquentQuery());
    }

    /** Tanpa ini, ustadz bisa menjangkau record di luar cakupannya dengan menebak URL. */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::terapkanCakupanPresensi(parent::getRecordRouteBindingEloquentQuery());
    }

    protected static function terapkanCakupanPresensi(Builder $query): Builder
    {
        if (Auth::user()?->role !== UserRole::Ustadz->value) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->where(fn (Builder $harian) => $harian
                ->where('jam_ke', Presensi::HARIAN)
                ->whereIn('santri_id', PenugasanUstadz::santriIdsPerwalianKelas()))
            // Cabang ini belum menghasilkan baris apa pun sampai Fase 6 (presensi per
            // jam pelajaran) dibangun, dan itu tidak apa-apa: ia aturan sebenarnya,
            // bukan spekulasi, jadi Fase 6 tidak perlu menyentuh trait ini lagi.
            ->orWhere(fn (Builder $perJam) => $perJam
                ->where('jam_ke', '>', Presensi::HARIAN)
                ->whereIn('mata_pelajaran_id', PenugasanUstadz::mataPelajaranIdsDiampu())));
    }
}
