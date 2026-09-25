<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\FinancialsDemo;
use Illuminate\Support\Carbon;

/**
 * Menu Financials: Overview, Profit & Loss, Balance Sheet, Cash Flow.
 * Dipakai portal admin dan tenant (Tenant\FinancialsController) dengan view yang sama
 * (resources/views/financials); layout & URL mengikuti portal.
 * Sementara memakai data contoh (App\Support\FinancialsDemo).
 */
class FinancialsController extends Controller
{
    /** Layout portal & prefix URL halaman Financials. */
    protected $layout = 'admin.template.layout2.base';
    protected $base = '/admin/financials';

    public function overview()
    {
        return view('financials.overview', $this->common() + [
            'kpi'    => FinancialsDemo::overview(),
            'series' => FinancialsDemo::monthly(12),
        ]);
    }

    public function profitLoss()
    {
        return view('financials.profit_loss', $this->common() + [
            'kpis'      => FinancialsDemo::plKpis(),
            'statement' => FinancialsDemo::plStatement(),
            'monthly'   => FinancialsDemo::monthly(12),
            'yearly'    => FinancialsDemo::monthly(24),
        ]);
    }

    public function balanceSheet()
    {
        return view('financials.balance_sheet', $this->common() + [
            'bs' => FinancialsDemo::balanceSheet(),
        ]);
    }

    public function cashFlow()
    {
        return view('financials.cash_flow', $this->common() + [
            'cf'     => FinancialsDemo::cashFlow(),
            'series' => FinancialsDemo::monthly(12),
        ]);
    }

    /** Layout, prefix URL, dan periode laporan untuk judul (mis. "August 2026" / "Agustus 2026"). */
    private function common(): array
    {
        return [
            'layout' => $this->layout,
            'base'   => $this->base,
            'period' => Carbon::parse(FinancialsDemo::PERIOD . '-01')->locale(app()->getLocale())->translatedFormat('F Y'),
        ];
    }
}
