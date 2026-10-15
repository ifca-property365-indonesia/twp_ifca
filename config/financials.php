<?php

// Menu Financials (App\Http\Controllers\Admin\FinancialsController & Tenant\FinancialsController).
return [
    // Portal tenant: sementara disembunyikan (menu sidebar tidak tampil, /tenant/financials/... 404).
    // Tampilkan lagi dengan FINANCIALS_TENANT=true di .env.
    'tenant_enabled' => (bool) env('FINANCIALS_TENANT', false),
];
