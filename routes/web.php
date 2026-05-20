<?php

// File: routes/web.php
// Ini adalah KESELURUHAN isi web.php — replace seluruh file.

use App\Http\Controllers\Auth\WaliLoginController;
use App\Http\Controllers\Wali\DashboardController;
use App\Http\Controllers\Wali\ReportController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\BillingController;


// --- Auth Wali Santri ---
Route::get('/wali/login', [WaliLoginController::class, 'showLoginForm'])
    ->name('wali.login');
Route::post('/wali/login', [WaliLoginController::class, 'login'])
    ->name('wali.login.submit');
Route::post('/wali/logout', [WaliLoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');
// --- Billing (admin pesantren only) ---
Route::middleware(['auth', 'saas.lifecycle'])
    ->name('billing.')
    ->group(function () {
        Route::get('/billing', [BillingController::class, 'index'])
            ->name('index');
    });

// --- Portal Wali Santri (butuh login) ---
Route::middleware(['auth', 'saas.lifecycle'])
    ->prefix('wali')
    ->name('wali.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/santri/{santri}', [ReportController::class, 'show'])
            ->name('santri.show');
    });

// --- Magic Link (tanpa login form, via UUID santri) ---
Route::get('/report/{uuid}', [ReportController::class, 'showByUuid'])
    ->middleware('magic.token')
    ->name('wali.magic.report');

// --- Redirect root ---
Route::get('/', function () {
    if (auth()->check()) {
        return match (auth()->user()->role) {
            'wali_santri' => redirect()->route('wali.dashboard'),
            default       => redirect('/admin'),
        };
    }
    return redirect()->route('wali.login');
});

// --- Alias route login untuk Laravel auth middleware ---
Route::get('/login', function () {
    return redirect()->route('wali.login');
})->name('login');