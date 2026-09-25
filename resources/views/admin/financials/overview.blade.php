@extends('admin.template.layout2.base')
@section('title', __('admin/financials.tab_overview') . ' — ' . __('admin/financials.menu'))

@use('App\Support\FinancialsDemo', 'F')
@php
    $cards = [
        ['icon' => 'cil-chart-line', 'title' => __('admin/financials.tab_pl'), 'desc' => __('admin/financials.pl_desc'), 'url' => url('/admin/financials/profit-loss')],
        ['icon' => 'cil-balance-scale', 'title' => __('admin/financials.tab_bs'), 'desc' => __('admin/financials.bs_desc'), 'url' => url('/admin/financials/balance-sheet')],
        ['icon' => 'cil-money', 'title' => __('admin/financials.tab_cf'), 'desc' => __('admin/financials.cf_desc'), 'url' => url('/admin/financials/cash-flow')],
    ];
@endphp

@section('content')
<div class="page-body">
    @include('admin.financials._head', [
        'title' => __('admin/financials.overview_title'),
        'desc'  => __('admin/financials.overview_desc', ['period' => $period]),
        'tab'   => 'overview',
    ])

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-xl-3">
            <div class="card fin-kpi"><div class="card-body">
                <div class="fin-label">{{ __('admin/financials.revenue') }}</div>
                <div class="fin-value">{{ F::idr($kpi['revenue']['value']) }}</div>
                <div class="fin-sub">
                    <span class="{{ $kpi['revenue']['change'] >= 0 ? 'fin-up' : 'fin-down' }}"><i class="{{ $kpi['revenue']['change'] >= 0 ? 'cil-arrow-top' : 'cil-arrow-bottom' }}"></i> {{ F::pct($kpi['revenue']['change']) }}</span>
                </div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card fin-kpi"><div class="card-body">
                <div class="fin-label">{{ __('admin/financials.ebitda') }}</div>
                <div class="fin-value">{{ F::idr($kpi['ebitda']['value']) }}</div>
                <div class="fin-sub">
                    <span class="{{ $kpi['ebitda']['change'] >= 0 ? 'fin-up' : 'fin-down' }}"><i class="{{ $kpi['ebitda']['change'] >= 0 ? 'cil-arrow-top' : 'cil-arrow-bottom' }}"></i> {{ F::pct($kpi['ebitda']['change']) }}</span>
                    &nbsp;{{ __('admin/financials.margin', ['value' => number_format($kpi['ebitda']['margin'], 1, ',', '.') . '%']) }}
                </div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card fin-kpi"><div class="card-body">
                <div class="fin-label">{{ __('admin/financials.total_assets') }}</div>
                <div class="fin-value">{{ F::idr($kpi['total_assets']['value']) }}</div>
                <div class="fin-sub">{{ __('admin/financials.equity_of', ['value' => F::idr($kpi['total_assets']['equity'])]) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card fin-kpi"><div class="card-body">
                <div class="fin-label">{{ __('admin/financials.closing_cash') }}</div>
                <div class="fin-value">{{ F::idr($kpi['closing_cash']['value']) }}</div>
                <div class="fin-sub">{{ __('admin/financials.net_movement', ['value' => F::idr($kpi['closing_cash']['net'])]) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="fin-card-title">{{ __('admin/financials.rev_ebitda') }}</h6>
            <div class="fin-card-desc mb-3">{{ __('admin/financials.rolling_12') }}</div>
            <div class="fin-chart lg"><canvas id="chartRevEbitda"></canvas></div>
        </div>
    </div>

    <div class="row g-3">
        @foreach ($cards as $c)
            <div class="col-md-4">
                <a href="{{ $c['url'] }}" class="card fin-link-card h-100 text-reset text-decoration-none">
                    <div class="card-body">
                        <span class="fin-icon mb-2"><i class="{{ $c['icon'] }}"></i></span>
                        <h6 class="fin-card-title mt-2">{{ $c['title'] }}</h6>
                        <div class="fin-card-desc mb-2">{{ $c['desc'] }}</div>
                        <span class="text-primary fw-semibold small">{{ __('admin/financials.open') }} <i class="cil-arrow-right"></i></span>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var rows = @json($series);
    new Chart(document.getElementById('chartRevEbitda'), {
        type: 'line',
        data: {
            labels: rows.map(function (r) { return r.label; }),
            datasets: [
                { label: @json(__('admin/financials.revenue')), data: rows.map(function (r) { return r.revenue; }), borderColor: FIN.color.primary, backgroundColor: FIN.color.light, fill: true, tension: .35, pointRadius: 0, borderWidth: 2 },
                { label: @json(__('admin/financials.ebitda')), data: rows.map(function (r) { return r.ebitda; }), borderColor: FIN.color.gold, backgroundColor: 'rgba(189, 168, 112, .15)', fill: true, tension: .35, pointRadius: 0, borderWidth: 2 }
            ]
        },
        options: FIN.options()
    });
});
</script>
@endpush
