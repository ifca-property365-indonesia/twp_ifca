<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\FinancialsDemo;
use Illuminate\Http\Request;

/**
 * Menu Financials: Overview, Profit & Loss, Balance Sheet, Cash Flow.
 * Dipakai portal admin dan tenant (Tenant\FinancialsController) dengan view yang sama
 * (resources/views/financials); layout & URL mengikuti portal.
 * Sementara memakai data contoh (App\Support\FinancialsDemo).
 *
 * Filter di atas halaman (group, period, currency, compare) dikirim lewat query string
 * dan disimpan di session, jadi tetap berlaku saat pindah tab / menu.
 */
class FinancialsController extends Controller
{
    /** Layout portal & prefix URL halaman Financials. */
    protected $layout = 'admin.template.layout2.base';
    protected $base = '/admin/financials';

    public function overview(Request $request)
    {
        $fin = $this->demo($request);
        return view('financials.overview', $this->common($fin) + [
            'kpi'    => $fin->overview(),
            'series' => $fin->monthly(12),
        ]);
    }

    public function profitLoss(Request $request)
    {
        $fin = $this->demo($request);
        return view('financials.profit_loss', $this->common($fin) + [
            'kpis'      => $fin->plKpis(),
            'statement' => $fin->plStatement(),
            'monthly'   => $fin->monthly(12),
            'yearly'    => $fin->monthly(24),
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $fin = $this->demo($request);
        return view('financials.balance_sheet', $this->common($fin) + [
            'bs' => $fin->balanceSheet(),
        ]);
    }

    public function cashFlow(Request $request)
    {
        $fin = $this->demo($request);
        return view('financials.cash_flow', $this->common($fin) + [
            'cf'     => $fin->cashFlow(),
            'series' => $fin->monthly(12),
        ]);
    }

    /** Data contoh sesuai filter: query string > filter terakhir di session > default. */
    private function demo(Request $request): FinancialsDemo
    {
        $filter = FinancialsDemo::normalize(array_merge(
            (array) $request->session()->get('financials_filter', []),
            $request->only(['group', 'period', 'currency', 'compare'])
        ));
        $request->session()->put('financials_filter', $filter);
        return new FinancialsDemo($filter);
    }

    /** Layout, prefix URL, filter aktif, pilihan filter, dan teks judul/keterangan. */
    private function common(FinancialsDemo $fin): array
    {
        $labels = $fin->labels();
        return [
            'layout'   => $this->layout,
            'base'     => $this->base,
            'filter'   => $fin->filter,
            'options'  => FinancialsDemo::options(),
            'labels'   => $labels,
            'period'   => $labels['period'],
            'currency' => $fin->currency(),
        ];
    }
}
