@extends($layout)
@section('title', __('admin/financials.tab_cf') . ' — ' . __('admin/financials.menu'))

@use('App\Support\FinancialsDemo', 'F')
@php
    $above = $cf['ending'] >= $cf['threshold'];
    $tiles = [
        ['beginning_cash', $cf['beginning']],
        ['operating', $cf['operating']],
        ['investing', $cf['investing']],
        ['financing', $cf['financing']],
        ['net_cash_flow', $cf['net']],
    ];
    $lbl = [ 'beginning' => __('admin/financials.beginning_cash'), 'operating' => __('admin/financials.operating'), 'investing' => __('admin/financials.investing'), 'financing' => __('admin/financials.financing'), 'ending' => __('admin/financials.ending_cash'), 'closing' => __('admin/financials.closing_cash_s'), 'inflow' => __('admin/financials.inflow'), 'outflow' => __('admin/financials.outflow'), 'actual' => __('admin/financials.actual_cash'), 'forecast' => __('admin/financials.forecast'), ];
@endphp

@section('content')
<div class="page-body">
    @include('financials._head', [
        'title' => __('admin/financials.cf_title'),
        'desc'  => __('admin/financials.period_idr', ['period' => $period]),
        'tab'   => 'cf',
    ])

    <div class="card mb-3">
        <div class="card-body">
            <span class="badge {{ $above ? 'badge-soft-success' : 'badge-soft-danger' }} mb-3">
                <i class="{{ $above ? 'cil-check-circle' : 'cil-warning' }}"></i> {{ __('admin/financials.' . ($above ? 'above_threshold' : 'below_threshold')) }}
            </span>
            <div class="row g-3 align-items-end">
                @foreach ($tiles as [$key, $value])
                    <div class="col-6 col-md-4 col-xl-2">
                        <div class="fin-kpi">
                            <div class="fin-label">{{ __('admin/financials.' . $key) }}</div>
                            <div class="fin-value" style="font-size: 1.25rem;">{{ F::idr($value) }}</div>
                        </div>
                    </div>
                @endforeach
                <div class="col-12 col-md-4 col-xl-2">
                    <div class="fin-kpi">
                        <div class="fin-label">{{ __('admin/financials.ending_cash') }}</div>
                        <div class="fin-value" style="font-size: 1.25rem;">{{ F::idr($cf['ending']) }}</div>
                        <div class="fin-sub"><span class="{{ $cf['ending_mom'] >= 0 ? 'fin-up' : 'fin-down' }}">{{ F::pct($cf['ending_mom']) }}</span> MoM</div>
                    </div>
                </div>
            </div>
            <div class="fin-card-desc mt-3">{{ __('admin/financials.min_threshold', ['value' => F::idr($cf['threshold'])]) }}</div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="fin-card-title">{{ __('admin/financials.cash_movement') }}</h6>
                <div class="fin-card-desc mb-3">{{ $period }}</div>
                <div class="fin-chart"><canvas id="chartWaterfall"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="fin-card-title">{{ __('admin/financials.cash_trend') }}</h6>
                <div class="fin-card-desc mb-3">{{ __('admin/financials.rolling_12') }}</div>
                <div class="fin-chart"><canvas id="chartCashTrend"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="fin-card-title">{{ __('admin/financials.inflow_outflow') }}</h6>
                <div class="fin-card-desc mb-3">{{ __('admin/financials.rolling_12') }}</div>
                <div class="fin-chart"><canvas id="chartInOut"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="fin-card-title">{{ __('admin/financials.cash_forecast') }}</h6>
                <div class="fin-card-desc mb-3">{{ __('admin/financials.forecast_desc') }}</div>
                <div class="fin-chart"><canvas id="chartForecast"></canvas></div>
                <div class="fin-card-desc mt-2"><i class="cil-info"></i> {{ __('admin/financials.forecast_note') }}</div>
            </div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var CF = @json($cf);
    var series = @json($series);
    var LBL = @json($lbl);
    var monthLabel = function (m) { var d = new Date(m + '-01T00:00:00'); return d.toLocaleString('en-US', { month: 'short' }) + ' ' + String(d.getFullYear()).slice(2); };

    // Pergerakan kas: semua batang mulai dari 0 (sama dengan referensi); arus keluar di bawah 0
    var amounts = [CF.beginning, CF.operating, CF.investing, CF.financing, CF.ending];
    var steps = amounts;
    new Chart(document.getElementById('chartWaterfall'), {
        type: 'bar',
        data: {
            labels: [LBL.beginning, LBL.operating, LBL.investing, LBL.financing, LBL.ending],
            datasets: [{
                data: steps,
                backgroundColor: amounts.map(function (v, i) { return i === 0 || i === 4 ? FIN.color.primary : (v >= 0 ? FIN.color.green : FIN.color.red); }),
                borderRadius: 3
            }]
        },
        options: FIN.options({
            interaction: { mode: 'nearest', intersect: true },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return FIN.idr(amounts[c.dataIndex]); } } } }
        })
    });

    new Chart(document.getElementById('chartCashTrend'), {
        type: 'line',
        data: { labels: series.map(function (r) { return r.label; }), datasets: [
            { label: LBL.closing, data: series.map(function (r) { return r.cash; }), borderColor: FIN.color.primary, backgroundColor: FIN.color.light, fill: true, tension: .35, pointRadius: 2, borderWidth: 2 }
        ] },
        options: FIN.options()
    });

    new Chart(document.getElementById('chartInOut'), {
        type: 'bar',
        data: { labels: CF.inflow_outflow.map(function (r) { return monthLabel(r[0]); }), datasets: [
            { label: LBL.inflow, data: CF.inflow_outflow.map(function (r) { return r[1]; }), backgroundColor: FIN.color.green, borderRadius: 3 },
            { label: LBL.outflow, data: CF.inflow_outflow.map(function (r) { return r[2]; }), backgroundColor: FIN.color.red, borderRadius: 3 }
        ] },
        options: FIN.options()
    });

    new Chart(document.getElementById('chartForecast'), {
        type: 'line',
        data: { labels: CF.forecast.map(function (r) { return monthLabel(r[0]); }), datasets: [
            { label: LBL.actual, data: CF.forecast.map(function (r) { return r[1]; }), borderColor: FIN.color.primary, backgroundColor: FIN.color.primary, tension: .35, pointRadius: 2, borderWidth: 2 },
            { label: LBL.forecast, data: CF.forecast.map(function (r) { return r[2]; }), borderColor: FIN.color.gold, backgroundColor: FIN.color.gold, borderDash: [6, 4], tension: .35, pointRadius: 2, borderWidth: 2 }
        ] },
        options: FIN.options({ spanGaps: false })
    });
});
</script>
@endpush
