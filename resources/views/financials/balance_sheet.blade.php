@extends($layout)
@section('title', __('admin/financials.tab_bs') . ' — ' . __('admin/financials.menu'))

@use('App\Support\FinancialsDemo', 'F')
@php
    $k = $bs['kpis'];
    $num = function ($v) { return number_format($v, 2, ',', '.'); };
    $mom = function ($v) { return ($v >= 0 ? '+' : '-') . number_format(abs($v), 1) . '%'; };
    $wcRows = array_map(fn ($r) => ['label' => F::label($r[0]), 'cash' => $r[1], 'ar' => $r[2], 'inventory' => $r[3]], $bs['working_capital_trend']);
@endphp

@section('content')
<div class="page-body">
    @include('financials._head', [
        'title' => __('admin/financials.bs_title'),
        'desc'  => __('admin/financials.bs_desc_period', ['period' => $period]),
        'tab'   => 'bs',
    ])

    <div class="row g-3 mb-3">
        <div class="col-sm-6 col-lg-4 col-xxl-2">
            <div class="card fin-kpi"><div class="card-body">
                <div class="fin-label">{{ __('admin/financials.current_ratio') }}</div>
                <div class="fin-value">{{ $num($k['current_ratio']['value']) }}</div>
                <div class="fin-sub">{{ __('admin/financials.target', ['value' => $num($k['current_ratio']['target'])]) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-lg-4 col-xxl-2">
            <div class="card fin-kpi"><div class="card-body">
                <div class="fin-label">{{ __('admin/financials.debt_equity') }}</div>
                <div class="fin-value">{{ $num($k['debt_equity']['value']) }}</div>
                <div class="fin-sub">{{ __('admin/financials.covenant', ['value' => $num($k['debt_equity']['covenant'])]) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-lg-4 col-xxl-2">
            <div class="card fin-kpi"><div class="card-body">
                <div class="fin-label">{{ __('admin/financials.working_capital') }}</div>
                <div class="fin-value">{{ F::idr($k['working_capital']) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-lg-4 col-xxl-2">
            <div class="card fin-kpi"><div class="card-body">
                <div class="fin-label">{{ __('admin/financials.ar_days') }}</div>
                <div class="fin-value">{{ __('admin/financials.days', ['value' => $k['ar_days']['value']]) }}</div>
                <div class="fin-sub"><span class="{{ $k['ar_days']['mom'] <= 0 ? 'fin-up' : 'fin-down' }}">{{ F::pct($k['ar_days']['mom']) }}</span> {{ __('admin/financials.days_mom') }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-lg-4 col-xxl-2">
            <div class="card fin-kpi"><div class="card-body">
                <div class="fin-label">{{ __('admin/financials.ap_days') }}</div>
                <div class="fin-value">{{ __('admin/financials.days', ['value' => $k['ap_days']]) }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-lg-4 col-xxl-2">
            <div class="card fin-kpi"><div class="card-body">
                <div class="fin-label">{{ __('admin/financials.inventory_days') }}</div>
                <div class="fin-value">{{ __('admin/financials.days', ['value' => $k['inventory_days']]) }}</div>
            </div></div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="fin-card-title mb-2">{{ __('admin/financials.assets') }}</h6>
                <ul class="list-unstyled fin-list mb-0">
                    @foreach ($bs['assets'] as [$item, $value, $change])
                        <li><span>{{ __('admin/financials.items.' . $item) }}</span><span class="text-end text-nowrap">{{ F::idr($value) }} <small class="text-soft ms-1">{{ __('admin/financials.mom', ['value' => $mom($change)]) }}</small></span></li>
                    @endforeach
                    <li class="fin-total-row"><span>{{ __('admin/financials.total') }}</span><span>{{ F::idr($bs['assets_total']) }}</span></li>
                </ul>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="fin-card-title mb-2">{{ __('admin/financials.liabilities') }}</h6>
                <ul class="list-unstyled fin-list mb-0">
                    @foreach ($bs['liabilities'] as [$item, $value, $change])
                        <li><span>{{ __('admin/financials.items.' . $item) }}</span><span class="text-end text-nowrap">{{ F::idr($value) }} <small class="text-soft ms-1">{{ __('admin/financials.mom', ['value' => $mom($change)]) }}</small></span></li>
                    @endforeach
                    <li class="fin-total-row"><span>{{ __('admin/financials.total') }}</span><span>{{ F::idr($bs['liabilities_total']) }}</span></li>
                </ul>
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100"><div class="card-body">
                <h6 class="fin-card-title mb-2">{{ __('admin/financials.equity') }}</h6>
                <div class="fin-value" style="font-size: 1.55rem; font-weight: 700;">{{ F::idr($bs['equity']) }}</div>
                <p class="fin-card-desc mt-2">{{ __('admin/financials.equity_note', ['pct' => number_format($bs['equity_pct'], 1, ',', '.') . '%']) }}</p>
                <ul class="list-unstyled fin-list mb-0">
                    <li><span>{{ __('admin/financials.total_assets') }}</span><span>{{ F::idr($bs['assets_total']) }}</span></li>
                    <li><span>{{ __('admin/financials.total_liabilities') }}</span><span>{{ F::idr($bs['liabilities_total']) }}</span></li>
                    <li class="fin-total-row"><span>{{ __('admin/financials.net_assets') }}</span><span>{{ F::idr($bs['assets_total'] - $bs['liabilities_total']) }}</span></li>
                </ul>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="fin-card-title">{{ __('admin/financials.wc_trend') }}</h6>
            <div class="fin-card-desc mb-3">{{ __('admin/financials.rolling_12') }}</div>
            <div class="fin-chart lg"><canvas id="chartWorkingCapital"></canvas></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var rows = @json($wcRows);
    function col(key) { return rows.map(function (r) { return r[key]; }); }
    new Chart(document.getElementById('chartWorkingCapital'), {
        type: 'line',
        data: {
            labels: col('label'),
            datasets: [
                { label: @json(__('admin/financials.cash')), data: col('cash'), borderColor: FIN.color.primary, backgroundColor: FIN.color.primary, borderWidth: 2, pointRadius: 2, tension: .35 },
                { label: @json(__('admin/financials.receivables')), data: col('ar'), borderColor: FIN.color.gold, backgroundColor: FIN.color.gold, borderWidth: 2, pointRadius: 2, tension: .35 },
                { label: @json(__('admin/financials.inventory')), data: col('inventory'), borderColor: FIN.color.green, backgroundColor: FIN.color.green, borderWidth: 2, pointRadius: 2, tension: .35 }
            ]
        },
        options: FIN.options()
    });
});
</script>
@endpush
