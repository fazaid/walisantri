<?php

// File: routes/web.php
// Ini adalah KESELURUHAN isi web.php — replace seluruh file.

use App\Http\Controllers\Auth\WaliLoginController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\SlugCheckController;
use App\Http\Controllers\Wali\DashboardController;
use App\Http\Controllers\Wali\LaporanController;
use App\Http\Controllers\Wali\PengumumanController;
use App\Http\Controllers\Wali\RaporController;
use App\Http\Controllers\Wali\ReportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;


// --- Slug availability check (§1.4, rate-limit 30/mnt) ---
Route::get('/check-slug/{slug}', SlugCheckController::class)
    ->middleware('throttle:check-slug')
    ->name('check-slug');

// --- Auth Wali Santri (login terpusat §1.3 — branded via ?tenant=slug) ---
Route::get('/login', [WaliLoginController::class, 'showLoginForm'])
    ->name('wali.login');
Route::post('/login', [WaliLoginController::class, 'login'])
    ->name('wali.login.submit');
Route::post('/wali/logout', [WaliLoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
// --- Portal Wali Santri (butuh login) ---
Route::middleware(['auth', 'saas.lifecycle'])
    ->prefix('wali')
    ->name('wali.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/santri/{santri}', [ReportController::class, 'show'])
            ->name('santri.show');
        Route::get('/pengumuman', [PengumumanController::class, 'index'])
            ->name('pengumuman');
        Route::get('/rapor', [RaporController::class, 'index'])
            ->name('rapor');
        Route::get('/laporan/pdf', [LaporanController::class, 'exportPdf'])
            ->name('laporan.pdf');
    });

// --- Magic Link (tanpa login form, via UUID santri) ---
Route::get('/report/{uuid}', [ReportController::class, 'showByUuid'])
    ->middleware('magic.token')
    ->name('wali.magic.report');

// --- Root redirect ---
Route::get('/', function () {
    if (auth()->check()) {
        return match (auth()->user()->role) {
            'wali_santri' => redirect()->route('wali.dashboard'),
            default       => redirect('/admin'),
        };
    }
    return redirect()->route('wali.login');
});