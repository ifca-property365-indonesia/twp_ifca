<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Admin\FinancialsController as BaseFinancialsController;

/**
 * Menu Financials di portal tenant: halaman & data sama dengan admin
 * (Admin\FinancialsController), dengan layout dan URL tenant.
 */
class FinancialsController extends BaseFinancialsController
{
    protected $layout = 'tenant.template.base';
    protected $base = '/tenant/financials';
}
