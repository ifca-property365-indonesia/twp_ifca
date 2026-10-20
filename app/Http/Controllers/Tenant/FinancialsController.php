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

    /** Sementara disembunyikan di portal tenant (config financials.tenant_enabled / FINANCIALS_TENANT). */
    public function callAction($method, $parameters)
    {
        abort_unless(config('financials.tenant_enabled'), 404);
        return $this->{$method}(...array_values($parameters));
    }
}
