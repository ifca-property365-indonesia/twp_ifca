<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\FinancialsDemo;
use Illuminate\Support\Carbon;

/**
 * Menu Admin -> Financials: Overview, Profit & Loss, Balance Sheet, Cash Flow.
 * Sementara memakai data contoh (App\Support\FinancialsDemo); tampilan mengikuti template admin.
 */
class FinancialsController extends Controller
{
    public function overview()
    {
        return view('admin.financials.overview', $this->common() + [
            'kpi'    => FinancialsDemo::overview(),
            'series' => FinancialsDemo::monthly(12),
        ]);
    }

    public function profitLoss()
    {
        return view('admin.financials.profit_loss', $this->common() + [
            'kpis'      => FinancialsDemo::plKpis(),
            'statement' => FinancialsDemo::plStatement(),
            'monthly'   => FinancialsDemo::monthly(12),
            'yearly'    => FinancialsDemo::monthly(24),
        ]);
    }

    public function balanceSheet()
    {
        return view('admin.financials.balance_sheet', $this->common() + [
            'bs' => FinancialsDemo::balanceSheet(),
        ]);
    }

    public function cashFlow()
    {
        return view('admin.financials.cash_flow', $this->common() + [
            'cf'     => FinancialsDemo::cashFlow(),
            'series' => FinancialsDemo::monthly(12),
        ]);
    }

    /** Periode laporan untuk judul, mis. "August 2026" / "Agustus 2026". */
    private function common(): array
    {
        return [
            'period' => Carbon::parse(FinancialsDemo::PERIOD . '-01')->locale(app()->getLocale())->translatedFormat('F Y'),
        ];
    }
}
