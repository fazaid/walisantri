<?php

// File: app/Http/Controllers/Admin/BillingController.php
// php artisan make:controller Admin/BillingController
// (atau buat manual)

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BillingCalculatorService;

class BillingController extends Controller
{
    public function __construct(
        private BillingCalculatorService $billing
    ) {}

    public function index()
    {
        $user      = auth()->user();
        $pesantren = $user->pesantren;

        abort_unless($pesantren, 404);

        $billingInfo    = $this->billing->hitung($pesantren);
        $santriAktif    = $pesantren->santri()->where('status_aktif', true)->count();
        $sisaKuota      = $pesantren->max_santri_kuota - $santriAktif;
        $isExpired      = $pesantren->expired_at && now()->isAfter($pesantren->expired_at);
        $isSuspended    = $pesantren->status_berlangganan === 'suspended';

        return view('admin.billing.index', compact(
            'pesantren',
            'billingInfo',
            'santriAktif',
            'sisaKuota',
            'isExpired',
            'isSuspended',
        ));
    }
}