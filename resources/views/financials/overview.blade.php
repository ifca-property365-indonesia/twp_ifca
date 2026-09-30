@extends($layout)
@section('title', __('admin/financials.tab_overview') . ' — ' . __('admin/financials.menu'))

{{--
    Overview Financials: tampilan dashboard eksekutif (kartu KPI, tren, ringkasan laba rugi, catatan manajemen,
    kas, neraca, umur piutang/utang, kinerja per bisnis, laporan terbaru). Data: FinancialsDemo::dashboard().
--}}
@use('App\Support\FinancialsDemo', 'F')
@php
    $k = $dash['kpis'];
    $compare = $labels['compare'];
    // biaya naik = buruk (warna badge dibalik)
    $costLines = ['cogs', 'opex', 'da', 'interest', 'tax'];
    $num1 = fn ($v) => number_format($v, 1, ',', '.');
    $statusClass = ['on_track' => 'success', 'attention' => 'warning', 'watch' => 'warning', 'at_risk' => 'danger'];
    $cards = [
        ['revenue', __('admin/financials.revenue'), 'cil-dollar'],
        ['gross_profit', __('admin/financials.dash.gross_profit'), 'cil-chart'],
        ['ebitda', __('admin/financials.ebitda'), 'cil-graph'],
        ['net_profit', __('admin/financials.dash.net_profit'), 'cil-chart-line'],
        ['closing_cash', __('admin/financials.closing_cash'), 'cil-wallet'],
        ['total_assets', __('admin/financials.total_assets'), 'cil-bank'],
    ];
    $agingColors = ['#2b4c7e', '#2fa3b5', '#d49a3a', '#d0473b'];
    $trendLabels = [
        'actual' => __('admin/financials.actual'),
        'budget' => __('admin/financials.budget'),
        'prior'  => __('admin/financials.previous_year'),
    ];
    $sparks = array_map(fn ($c) => $c['spark'], $k);
    $agingData = [
        'ar' => array_values(array_map(fn ($b) => $b['value'], $dash['ar']['buckets'])),
        'ap' => array_values(array_map(fn ($b) => $b['value'], $dash['ap']['buckets'])),
    ];
@endphp

@push('styles')
<style>
    .fin-dash .card { height: 100%; }
    .fin-dash .card-body { padding: 1rem 1.15rem; }
    .fin-dash-kpi .fin-kpi-head { display: flex; justify-content: space-between; align-items: center; }
    .fin-dash-kpi .fin-kpi-head i { color: var(--cui-secondary-color); font-size: 1.05rem; }
    .fin-dash-kpi .fin-kpi-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
    .fin-dash-kpi .fin-spark { width: 96px; height: 34px; flex: none; }
    .fin-dash-kpi .fin-kpi-lines { font-size: .78rem; color: var(--cui-secondary-color); margin-top: .35rem; line-height: 1.55; }
    .fin-dash-kpi .fin-kpi-lines .sep { margin: 0 .35rem; }
    .fin-card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: .75rem; margin-bottom: .85rem; }
    .fin-card-head .fin-card-link { font-size: .8rem; font-weight: 600; white-space: nowrap; text-decoration: none; }
    .fin-seg { display: inline-flex; padding: 2px; border: 1px solid var(--cui-border-color); border-radius: .45rem; background: var(--cui-tertiary-bg); }
    .fin-seg .btn { border: 0; padding: .2rem .6rem; font-size: .75rem; font-weight: 600; color: var(--cui-secondary-color); border-radius: .35rem; }
    .fin-seg .btn.active { background: #fff; color: var(--cui-emphasis-color); box-shadow: 0 1px 2px rgba(31, 41, 55, .12); }
    .fin-dash .fin-table td, .fin-dash .fin-table th { padding: .5rem .6rem; font-size: .83rem; }
    .fin-dash .fin-table td.sub { padding-left: 1.5rem; color: var(--cui-secondary-color); }
    .fin-var { font-size: .78rem; font-weight: 600; white-space: nowrap; }
    .fin-alerts { display: flex; flex-direction: column; gap: .6rem; max-height: 470px; overflow-y: auto; }
    .fin-alert { border: 1px solid; border-radius: .55rem; padding: .7rem .85rem; font-size: .8rem; }
    .fin-alert.critical { background: #fdf0ee; border-color: #f5c9c3; }
    .fin-alert.warning { background: #fff8e8; border-color: #f3dca5; }
    .fin-alert .fin-alert-title { font-weight: 700; color: var(--cui-emphasis-color); }
    .fin-alert.critical .fin-alert-title i { color: #d0473b; }
    .fin-alert.warning .fin-alert-title i { color: #c58a14; }
    .fin-alert .fin-alert-meta { font-size: .72rem; color: var(--cui-secondary-color); }
    .fin-alert .fin-alert-foot { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: var(--cui-secondary-color); margin-top: .35rem; }
    .fin-tile { border: 1px solid var(--cui-border-color-translucent); border-radius: .5rem; padding: .6rem .75rem; height: 100%; }
    .fin-dash .fin-label { font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--cui-secondary-color); }
    .fin-tile .fin-tile-value { font-weight: 700; font-size: 1.05rem; color: var(--cui-emphasis-color); margin-top: .15rem; }
    .fin-stack { display: flex; height: 10px; border-radius: 5px; overflow: hidden; background: var(--cui-tertiary-bg); }
    .fin-legend-dot { display: inline-block; width: .55rem; height: .55rem; border-radius: 50%; margin-right: .35rem; }
    .fin-donut-wrap { display: flex; align-items: center; gap: 1.25rem; }
    .fin-donut { position: relative; width: 150px; height: 150px; flex: none; }
    .fin-donut-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; pointer-events: none; }
    .fin-donut-center small { font-size: .62rem; text-transform: uppercase; letter-spacing: .05em; color: var(--cui-secondary-color); }
    .fin-donut-center b { font-size: .95rem; color: var(--cui-emphasis-color); }
    .fin-aging-list { flex: 1; min-width: 0; }
    .fin-aging-list li { display: flex; justify-content: space-between; align-items: center; gap: .5rem; padding: .35rem .5rem; border-radius: .35rem; font-size: .82rem; }
    .fin-aging-list li.alert-row { background: #fdf0ee; }
    .fin-aging-list li b { color: var(--cui-emphasis-color); }
    .fin-bars li { margin-bottom: .7rem; font-size: .82rem; }
    .fin-bars .fin-bar { height: 7px; border-radius: 4px; background: var(--cui-tertiary-bg); margin-top: .3rem; overflow: hidden; }
    .fin-bars .fin-bar span { display: block; height: 100%; border-radius: 4px; }
    .fin-company-row { cursor: pointer; }
    .fin-reports li { display: flex; justify-content: space-between; align-items: center; gap: .5rem; padding: .55rem 0; border-bottom: 1px solid var(--cui-border-color-translucent); font-size: .82rem; }
    .fin-reports li:last-child { border-bottom: 0; }
    .fin-reports small { color: var(--cui-secondary-color); font-size: .72rem; }
    .fin-selected-row td { background: #f6f7ff; }
    @media (max-width: 575.98px) { .fin-donut-wrap { flex-direction: column; align-items: stretch; } .fin-donut { margin: 0 auto; } }
</style>
@endpush

@section('content')
<div class="page-body fin-dash">
    @include('financials._head', [
        'title' => __('admin/financials.overview_title', ['group' => $labels['group']]),
        'desc'  => __('admin/financials.overview_desc', ['period' => $period, 'currency' => $labels['currency']]),
        'tab'   => 'overview',
    ])

    {{-- Kartu KPI --}}
    <div class="row g-3 mb-3">
        @foreach ($cards as [$key, $label, $icon])
            @php $c = $k[$key]; @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card fin-kpi fin-dash-kpi"><div class="card-body">
                    <div class="fin-kpi-head"><span class="fin-label">{{ $label }}</span><i class="{{ $icon }}"></i></div>
                    <div class="fin-kpi-row">
                        <div class="fin-value">{{ F::money($c['value']) }}</div>
                        <canvas class="fin-spark" data-spark="{{ $key }}"></canvas>
                    </div>
                    <div class="fin-kpi-lines">
                        @if (isset($c['budget']))
                            <div>{{ __('admin/financials.dash.budget_of', ['value' => F::money($c['budget'])]) }}</div>
                            <div>
                                <span class="{{ $c['change'] >= 0 ? 'fin-up' : 'fin-down' }}"><i class="{{ $c['change'] >= 0 ? 'cil-arrow-top' : 'cil-arrow-bottom' }}"></i> {{ F::pct($c['change']) }}</span>
                                {{ __('admin/financials.dash.vs', ['compare' => $compare]) }}
                                @if ($c['margin'] !== null)<span class="sep">·</span>{{ __('admin/financials.margin', ['value' => $num1($c['margin']) . '%']) }}@endif
                            </div>
                        @elseif ($key === 'closing_cash')
                            <div>{{ __('admin/financials.dash.min_of', ['value' => F::money($c['min'])]) }}</div>
                        @else
                            <div>{{ __('admin/financials.equity_of', ['value' => F::money($c['equity'])]) }}</div>
                        @endif
                    </div>
                </div></div>
            </div>
        @endforeach
    </div>

    {{-- Tren pendapatan / EBITDA --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="fin-card-head flex-wrap">
                <div>
                    <h6 class="fin-card-title">{{ __('admin/financials.dash.revenue_trend') }}</h6>
                    <div class="fin-card-desc">{{ __('admin/financials.dash.trend_desc') }}</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <div class="fin-seg" id="trendMetric">
                        <button type="button" class="btn active" data-metric="revenue">{{ __('admin/financials.revenue') }}</button>
                        <button type="button" class="btn" data-metric="ebitda">{{ __('admin/financials.ebitda') }}</button>
                    </div>
                    <div class="fin-seg" id="trendMode">
                        <button type="button" class="btn" data-mode="monthly">{{ __('admin/financials.dash.monthly_year', ['year' => $dash['trend']['year']]) }}</button>
                        <button type="button" class="btn" data-mode="ytd">{{ __('admin/financials.dash.ytd') }}</button>
                        <button type="button" class="btn active" data-mode="rolling">{{ __('admin/financials.dash.rolling') }}</button>
                    </div>
                </div>
            </div>
            <div class="fin-chart"><canvas id="chartTrend"></canvas></div>
        </div>
    </div>

    {{-- Ringkasan laba rugi + catatan manajemen --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="card"><div class="card-body">
                <div class="fin-card-head">
                    <div>
                        <h6 class="fin-card-title">{{ __('admin/financials.dash.pl_summary') }}</h6>
                        <div class="fin-card-desc">{{ __('admin/financials.dash.pl_summary_desc') }}</div>
                    </div>
                    <a class="fin-card-link" href="{{ url($base . '/profit-loss') }}">{{ __('admin/financials.dash.full_pl') }} <i class="cil-arrow-right"></i></a>
                </div>
                <div class="table-responsive">
                    <table class="table fin-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('admin/financials.col_line') }}</th>
                                <th class="text-end">{{ __('admin/financials.col_actual') }}</th>
                                <th class="text-end">{{ __('admin/financials.col_budget') }}</th>
                                <th class="text-end">{{ __('admin/financials.dash.col_variance') }}</th>
                                <th class="text-end">{{ __('admin/financials.dash.col_prev_year') }}</th>
                                <th class="text-end">{{ __('admin/financials.col_yoy') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dash['statement'] as [$line, $actual, $budget, $vsBudget, $prior, $yoy, $isTotal])
                                @php $cost = in_array($line, $costLines, true); @endphp
                                <tr class="{{ $isTotal ? 'fw-semibold' : '' }}">
                                    <td class="{{ $isTotal ? '' : 'sub' }}">{{ __('admin/financials.lines.' . $line) }}</td>
                                    <td class="text-end text-nowrap">{{ F::money($actual) }}</td>
                                    <td class="text-end text-nowrap text-soft">{{ F::money($budget) }}</td>
                                    <td class="text-end"><span class="fin-var {{ ($cost ? $vsBudget <= 0 : $vsBudget >= 0) ? 'fin-up' : 'fin-down' }}"><i class="{{ $vsBudget >= 0 ? 'cil-arrow-top' : 'cil-arrow-bottom' }}"></i> {{ F::pct($vsBudget) }}</span></td>
                                    <td class="text-end text-nowrap text-soft">{{ F::money($prior) }}</td>
                                    <td class="text-end"><span class="fin-var {{ ($cost ? $yoy <= 0 : $yoy >= 0) ? 'fin-up' : 'fin-down' }}"><i class="{{ $yoy >= 0 ? 'cil-arrow-top' : 'cil-arrow-bottom' }}"></i> {{ F::pct($yoy) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
        <div class="col-xl-5">
            <div class="card"><div class="card-body">
                <div class="fin-card-head">
                    <div>
                        <h6 class="fin-card-title">{{ __('admin/financials.dash.attention') }}</h6>
                        <div class="fin-card-desc">{{ __('admin/financials.dash.attention_desc', ['count' => count($dash['alerts']), 'period' => $period]) }}</div>
                    </div>
                </div>
                <div class="fin-alerts">
                    @forelse ($dash['alerts'] as $a)
                        @php $t = 'admin/financials.dash.alerts.' . $a['key']; @endphp
                        <div class="fin-alert {{ $a['severity'] }}">
                            <div class="fin-alert-title"><i class="{{ $a['severity'] === 'critical' ? 'cil-x-circle' : 'cil-warning' }}"></i> {{ __($t . '.title') }}</div>
                            <div class="fin-alert-meta">{{ __('admin/financials.groups.' . $a['company']) }} · {{ __($t . '.area') }}</div>
                            <div class="mt-1">{{ __($t . '.desc') }}</div>
                            <div class="fin-alert-foot">
                                {{ __('admin/financials.dash.severity.' . $a['severity']) }} · {{ \Illuminate\Support\Carbon::parse($a['date'])->locale(app()->getLocale())->translatedFormat('d M Y') }}@if ($a['action']) · {{ __($t . '.action') }}@endif
                            </div>
                        </div>
                    @empty
                        <div class="text-soft small">{{ __('admin/financials.dash.no_alerts') }}</div>
                    @endforelse
                </div>
            </div></div>
        </div>
    </div>

    {{-- Ringkasan kas & neraca --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <div class="fin-card-head">
                    <div>
                        <h6 class="fin-card-title">{{ __('admin/financials.dash.cash_summary') }}</h6>
                        <div class="fin-card-desc">{{ $period }}</div>
                    </div>
                    <a class="fin-card-link" href="{{ url($base . '/cash-flow') }}">{{ __('admin/financials.dash.details') }} <i class="cil-arrow-right"></i></a>
                </div>
                <div class="row g-2">
                    <div class="col-6"><div class="fin-tile"><div class="fin-label">{{ __('admin/financials.dash.cash_in') }}</div><div class="fin-tile-value">{{ F::money($dash['cash']['in']) }}</div></div></div>
                    <div class="col-6"><div class="fin-tile"><div class="fin-label">{{ __('admin/financials.dash.cash_out') }}</div><div class="fin-tile-value">{{ F::money($dash['cash']['out']) }}</div></div></div>
                    <div class="col-6"><div class="fin-tile"><div class="fin-label">{{ __('admin/financials.net_cash_flow') }}</div><div class="fin-tile-value {{ $dash['cash']['net'] >= 0 ? 'fin-up' : 'fin-down' }}">{{ ($dash['cash']['net'] >= 0 ? '+' : '') . F::money($dash['cash']['net']) }}</div></div></div>
                    <div class="col-6"><div class="fin-tile"><div class="fin-label">{{ __('admin/financials.closing_cash') }}</div><div class="fin-tile-value">{{ F::money($dash['cash']['closing']) }}</div></div></div>
                </div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <div class="fin-card-head">
                    <div>
                        <h6 class="fin-card-title">{{ __('admin/financials.dash.bs_summary') }}</h6>
                        <div class="fin-card-desc">{{ __('admin/financials.dash.bs_as_at', ['period' => $period]) }}</div>
                    </div>
                    <a class="fin-card-link" href="{{ url($base . '/balance-sheet') }}">{{ __('admin/financials.dash.details') }} <i class="cil-arrow-right"></i></a>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-4"><div class="fin-tile"><div class="fin-label">{{ __('admin/financials.assets') }}</div><div class="fin-tile-value">{{ F::money($dash['bs']['assets']) }}</div></div></div>
                    <div class="col-4"><div class="fin-tile"><div class="fin-label">{{ __('admin/financials.liabilities') }}</div><div class="fin-tile-value">{{ F::money($dash['bs']['liabilities']) }}</div></div></div>
                    <div class="col-4"><div class="fin-tile"><div class="fin-label">{{ __('admin/financials.equity') }}</div><div class="fin-tile-value">{{ F::money($dash['bs']['equity']) }}</div></div></div>
                </div>
                <div class="fin-card-desc mb-1">{{ __('admin/financials.assets') }}</div>
                <div class="fin-stack mb-2"><span style="width: 100%; background: #2b4c7e;"></span></div>
                <div class="d-flex justify-content-between fin-card-desc mb-1">
                    <span>{{ __('admin/financials.dash.liab_equity') }}</span>
                    <span>{{ __('admin/financials.dash.de', ['value' => number_format($dash['bs']['de'], 2, ',', '.') . 'x']) }}</span>
                </div>
                <div class="fin-stack mb-2">
                    <span style="width: {{ $dash['bs']['liab_pct'] }}%; background: #d49a3a;"></span>
                    <span style="width: {{ $dash['bs']['equity_pct'] }}%; background: #2fa3b5;"></span>
                </div>
                <div class="fin-card-desc">
                    <span class="me-3"><span class="fin-legend-dot" style="background: #d49a3a;"></span>{{ __('admin/financials.liabilities') }} {{ $num1($dash['bs']['liab_pct']) }}%</span>
                    <span><span class="fin-legend-dot" style="background: #2fa3b5;"></span>{{ __('admin/financials.equity') }} {{ $num1($dash['bs']['equity_pct']) }}%</span>
                </div>
            </div></div>
        </div>
    </div>

    {{-- Umur piutang & utang --}}
    <div class="row g-3 mb-3">
        @foreach (['ar' => 'ar_aging', 'ap' => 'ap_aging'] as $which => $titleKey)
            @php $ag = $dash[$which]; @endphp
            <div class="col-lg-6">
                <div class="card"><div class="card-body">
                    <div class="fin-card-head">
                        <div>
                            <h6 class="fin-card-title">{{ __('admin/financials.dash.' . $titleKey) }}</h6>
                            <div class="fin-card-desc">{{ __('admin/financials.dash.total_outstanding', ['value' => F::money($ag['total'])]) }}</div>
                        </div>
                    </div>
                    <div class="fin-donut-wrap">
                        <div class="fin-donut">
                            <canvas data-aging="{{ $which }}"></canvas>
                            <div class="fin-donut-center"><small>{{ __('admin/financials.dash.outstanding') }}</small><b>{{ F::money($ag['total']) }}</b></div>
                        </div>
                        <ul class="list-unstyled fin-aging-list mb-0">
                            @foreach (array_values($ag['buckets']) as $i => $b)
                                @php $bucket = array_keys($ag['buckets'])[$i]; @endphp
                                <li class="{{ $which === 'ar' && $bucket === 'over60' ? 'alert-row' : '' }}">
                                    <span><span class="fin-legend-dot" style="background: {{ $agingColors[$i] }};"></span>{{ __('admin/financials.dash.aging.' . $bucket) }}</span>
                                    <span class="text-nowrap"><b>{{ F::money($b['value']) }}</b> <span class="text-soft">· {{ $num1($b['pct']) }}%</span></span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @if ($which === 'ar')
                        <div class="fin-card-desc mt-2">{!! __('admin/financials.dash.overdue_note', ['value' => '<b class="fin-down">' . e(F::money($ag['buckets']['over60']['value'])) . '</b>']) !!}</div>
                    @endif
                </div></div>
            </div>
        @endforeach
    </div>

    {{-- Kinerja per bisnis --}}
    <div class="card mb-3">
        <div class="card-body">
            <div class="fin-card-head">
                <div>
                    <h6 class="fin-card-title">{{ __('admin/financials.dash.business_perf') }}</h6>
                    <div class="fin-card-desc">{{ __('admin/financials.dash.business_perf_desc') }}</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table fin-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('admin/financials.dash.col_business') }}</th>
                            <th class="text-end">{{ __('admin/financials.revenue') }}</th>
                            <th class="text-end">{{ __('admin/financials.ebitda') }}</th>
                            <th class="text-end">{{ __('admin/financials.dash.col_margin') }}</th>
                            <th>{{ __('admin/financials.dash.col_kpi') }}</th>
                            <th class="text-end">{{ __('admin/financials.dash.col_status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dash['companies'] as $key => $c)
                            <tr class="{{ $filter['group'] === $key ? 'fin-selected-row' : '' }}">
                                <td>
                                    <div class="fw-semibold">{{ __('admin/financials.businesses.' . $key) }}</div>
                                    <small class="text-soft">{{ __('admin/financials.groups.' . $key) }}</small>
                                </td>
                                <td class="text-end text-nowrap">{{ F::money($c['revenue']) }}</td>
                                <td class="text-end text-nowrap">{{ F::money($c['ebitda']) }}</td>
                                <td class="text-end">{{ $num1($c['margin']) }}%</td>
                                <td>{{ __('admin/financials.dash.kpis.' . $c['kpi']['type']) }} <b>{{ $num1($c['kpi']['value']) }}%</b></td>
                                <td class="text-end"><span class="badge badge-soft-{{ $statusClass[$c['kpi']['status']] }}">● {{ __('admin/financials.dash.status.' . $c['kpi']['status']) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pendapatan per unit & beban per kategori --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <div class="fin-card-head"><div>
                    <h6 class="fin-card-title">{{ __('admin/financials.dash.rev_by_unit') }}</h6>
                    <div class="fin-card-desc">{{ __('admin/financials.dash.rev_by_unit_desc') }}</div>
                </div></div>
                @php $maxUnit = max(array_column($dash['units'], 'pct')) ?: 1; @endphp
                <ul class="list-unstyled fin-bars mb-0">
                    @foreach ($dash['units'] as $key => $u)
                        <li>
                            <div class="d-flex justify-content-between"><span>{{ __('admin/financials.businesses.' . $key) }}</span><span class="text-nowrap"><b>{{ F::money($u['value']) }}</b> <span class="text-soft">· {{ $num1($u['pct']) }}%</span></span></div>
                            <div class="fin-bar"><span style="width: {{ $u['pct'] / $maxUnit * 100 }}%; background: #2b4c7e;"></span></div>
                        </li>
                    @endforeach
                </ul>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card"><div class="card-body">
                <div class="fin-card-head"><div>
                    <h6 class="fin-card-title">{{ __('admin/financials.dash.exp_by_cat') }}</h6>
                    <div class="fin-card-desc">{{ __('admin/financials.dash.exp_by_cat_desc') }}</div>
                </div></div>
                @php $maxExp = max(array_column($dash['expenses'], 'pct')) ?: 1; @endphp
                <ul class="list-unstyled fin-bars mb-0">
                    @foreach ($dash['expenses'] as $key => $e)
                        <li>
                            <div class="d-flex justify-content-between"><span>{{ __('admin/financials.dash.expenses.' . $key) }}</span><span class="text-nowrap"><b>{{ F::money($e['value']) }}</b> <span class="text-soft">· {{ $num1($e['pct']) }}%</span></span></div>
                            <div class="fin-bar"><span style="width: {{ $e['pct'] / $maxExp * 100 }}%; background: #d49a3a;"></span></div>
                        </li>
                    @endforeach
                </ul>
            </div></div>
        </div>
    </div>

    {{-- Kinerja perusahaan + laporan terbaru --}}
    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="card"><div class="card-body">
                <div class="fin-card-head"><div>
                    <h6 class="fin-card-title">{{ __('admin/financials.dash.company_perf') }}</h6>
                    <div class="fin-card-desc">{{ __('admin/financials.dash.company_perf_desc') }}</div>
                </div></div>
                <div class="table-responsive">
                    <table class="table table-hover fin-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('admin/financials.dash.col_company') }}</th>
                                <th class="text-end">{{ __('admin/financials.revenue') }}</th>
                                <th class="text-end">{{ __('admin/financials.ebitda') }}</th>
                                <th class="text-end">{{ __('admin/financials.dash.net_profit') }}</th>
                                <th class="text-end">{{ __('admin/financials.dash.col_cash') }}</th>
                                <th class="text-end">{{ __('admin/financials.dash.col_budget') }}</th>
                                <th class="text-end">{{ __('admin/financials.col_yoy') }}</th>
                                <th class="text-end">{{ __('admin/financials.dash.col_status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dash['companies'] as $key => $c)
                                <tr class="fin-company-row {{ $filter['group'] === $key ? 'fin-selected-row' : '' }}" data-href="{{ url($base . '/profit-loss') }}?group={{ $key }}">
                                    <td class="fw-semibold">{{ __('admin/financials.groups.' . $key) }}</td>
                                    <td class="text-end text-nowrap">{{ F::money($c['revenue']) }}</td>
                                    <td class="text-end text-nowrap">{{ F::money($c['ebitda']) }} <small class="text-soft">({{ $num1($c['margin']) }}%)</small></td>
                                    <td class="text-end text-nowrap">{{ F::money($c['net_profit']) }}</td>
                                    <td class="text-end text-nowrap">{{ F::money($c['cash']) }}</td>
                                    <td class="text-end">{{ $num1($c['budget_pct']) }}%</td>
                                    <td class="text-end"><span class="fin-var {{ $c['yoy'] >= 0 ? 'fin-up' : 'fin-down' }}">{{ F::pct($c['yoy']) }}</span></td>
                                    <td class="text-end"><span class="badge badge-soft-{{ $statusClass[$c['status']] }}">● {{ __('admin/financials.dash.status.' . $c['status']) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div></div>
        </div>
        <div class="col-xl-4">
            <div class="card"><div class="card-body">
                <div class="fin-card-head"><h6 class="fin-card-title">{{ __('admin/financials.dash.recent_reports') }}</h6></div>
                <ul class="list-unstyled fin-reports mb-0">
                    @foreach ($dash['reports'] as $r)
                        <li>
                            <div>
                                <div class="fw-semibold">{{ __('admin/financials.dash.reports.' . $r['key']) }}</div>
                                <small>{{ __('admin/financials.dash.updated', ['period' => $period, 'date' => \Illuminate\Support\Carbon::parse($r['updated'])->locale(app()->getLocale())->translatedFormat('d M Y H:i'), 'format' => $r['format']]) }}</small>
                            </div>
                            @if ($r['tab'])
                                <a href="{{ url($base . '/' . $r['tab']) }}" class="btn btn-sm btn-light" title="{{ __('admin/financials.open') }}"><i class="cil-external-link"></i></a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div></div>
        </div>
    </div>

    <p class="fin-card-desc text-center mb-0">{{ __('admin/financials.dash.footnote') }}</p>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var TREND = @json($dash['trend']);
    var SPARKS = @json($sparks);
    var AGING = @json($agingData);
    var AGING_COLORS = @json($agingColors);
    var L = @json($trendLabels);

    // sparkline di kartu KPI
    $('canvas[data-spark]').each(function () {
        var data = SPARKS[$(this).data('spark')] || [];
        new Chart(this, {
            type: 'line',
            data: { labels: data.map(function (v, i) { return i; }), datasets: [{ data: data, borderColor: '#2b4c7e', borderWidth: 1.5, pointRadius: 0, tension: .4, fill: false }] },
            options: { responsive: false, maintainAspectRatio: false, animation: false, events: [], plugins: { legend: { display: false }, tooltip: { enabled: false } }, scales: { x: { display: false }, y: { display: false } } }
        });
    });

    // tren: pilih metrik (pendapatan / EBITDA) dan tampilan (bulanan / YTD / 12 bulan)
    var metric = 'revenue', mode = 'rolling', trendChart = null;
    function drawTrend() {
        if (trendChart) { trendChart.destroy(); }
        var s = TREND[mode], v = s[metric];
        var line = function (label, data, color, dashed) {
            return { label: label, data: data, borderColor: color, backgroundColor: color, borderWidth: 2, pointRadius: 0, tension: .35, borderDash: dashed ? [6, 4] : [], spanGaps: false };
        };
        trendChart = new Chart(document.getElementById('chartTrend'), {
            type: 'line',
            data: { labels: s.labels, datasets: [line(L.actual, v[0], '#2b4c7e'), line(L.budget, v[1], '#d49a3a', true), line(L.prior, v[2], '#2fa3b5', true)] },
            options: FIN.options()
        });
    }
    $('#trendMetric .btn').on('click', function () { $('#trendMetric .btn').removeClass('active'); $(this).addClass('active'); metric = $(this).data('metric'); drawTrend(); });
    $('#trendMode .btn').on('click', function () { $('#trendMode .btn').removeClass('active'); $(this).addClass('active'); mode = $(this).data('mode'); drawTrend(); });
    drawTrend();

    // donat umur piutang / utang
    $('canvas[data-aging]').each(function () {
        new Chart(this, {
            type: 'doughnut',
            data: { datasets: [{ data: AGING[$(this).data('aging')], backgroundColor: AGING_COLORS, borderWidth: 2, borderColor: '#fff' }] },
            options: { cutout: '68%', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return FIN.money(c.parsed); } } } } }
        });
    });

    // klik baris perusahaan -> laporan laba rugi perusahaan itu
    $('.fin-company-row').on('click', function () { window.location.href = $(this).data('href'); });
});
</script>
@endpush
