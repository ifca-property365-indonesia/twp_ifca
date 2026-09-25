@extends($layout)
@section('title', __('admin/financials.tab_pl') . ' — ' . __('admin/financials.menu'))

@use('App\Support\FinancialsDemo', 'F')

@section('content')
<div class="page-body">
    @include('financials._head', [
        'title' => __('admin/financials.pl_title'),
        'desc'  => __('admin/financials.period_idr', ['period' => $period]),
        'tab'   => 'pl',
    ])

    <div class="d-flex justify-content-end mb-3">
        <div class="btn-group btn-group-sm fin-toggle" role="group">
            <button type="button" class="btn btn-primary" data-range="monthly">{{ __('admin/financials.monthly') }}</button>
            <button type="button" class="btn btn-outline-primary" data-range="yearly">{{ __('admin/financials.yearly') }}</button>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach ($kpis as $key => $k)
            {{-- opex ratio naik = memburuk; rasio lain naik = membaik --}}
            @php $good = $key === 'opex_ratio' ? $k['pts'] <= 0 : $k['pts'] >= 0; @endphp
            <div class="col-sm-6 col-xl-3">
                <div class="card fin-kpi"><div class="card-body">
                    <div class="fin-label">{{ __('admin/financials.' . $key) }}</div>
                    <div class="fin-value">{{ number_format($k['value'], 1, ',', '.') }}%</div>
                    <div class="fin-sub"><span class="{{ $good ? 'fin-up' : 'fin-down' }}">{{ __('admin/financials.pts_vs_py', ['value' => ($k['pts'] > 0 ? '+' : '') . number_format($k['pts'], 1)]) }}</span></div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h6 class="fin-card-title">{{ __('admin/financials.pl_statement') }}</h6>
            <div class="fin-card-desc mb-3">{{ $period }}</div>
            <div class="table-responsive">
                <table class="table table-hover fin-table mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('admin/financials.col_line') }}</th>
                            <th class="text-end">{{ __('admin/financials.col_actual') }}</th>
                            <th class="text-end">{{ __('admin/financials.col_budget') }}</th>
                            <th class="text-end">{{ __('admin/financials.col_vs_budget') }}</th>
                            <th class="text-end">{{ __('admin/financials.col_prior') }}</th>
                            <th class="text-end">{{ __('admin/financials.col_yoy') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($statement as [$line, $actual, $budget, $vsBudget, $prior, $yoy, $isTotal])
                            {{-- biaya (COGS, opex, D&A, bunga, pajak): naik = buruk --}}
                            @php $cost = in_array($line, ['cogs', 'opex', 'da', 'interest', 'tax'], true); @endphp
                            <tr class="{{ $isTotal ? 'fin-total' : '' }}">
                                <td>{{ __('admin/financials.lines.' . $line) }}</td>
                                <td class="text-end">{{ F::idr($actual) }}</td>
                                <td class="text-end">{{ F::idr($budget) }}</td>
                                <td class="text-end"><span class="badge {{ ($cost ? $vsBudget <= 0 : $vsBudget >= 0) ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ F::pct($vsBudget) }}</span></td>
                                <td class="text-end">{{ F::idr($prior) }}</td>
                                <td class="text-end"><span class="badge {{ ($cost ? $yoy <= 0 : $yoy >= 0) ? 'badge-soft-success' : 'badge-soft-danger' }}">{{ F::pct($yoy) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="fin-card-title">{{ __('admin/financials.revenue_trend') }}</h6>
                <div class="fin-card-desc mb-3">{{ __('admin/financials.rev_trend_desc') }}</div>
                <div class="fin-chart"><canvas id="chartRevenue"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="fin-card-title">{{ __('admin/financials.ebitda_trend') }}</h6>
                <div class="fin-card-desc mb-3">{{ __('admin/financials.actual_budget') }}</div>
                <div class="fin-chart"><canvas id="chartEbitda"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="fin-card-title">{{ __('admin/financials.gm_trend') }}</h6>
                <div class="fin-card-desc mb-3">{{ __('admin/financials.pct_revenue') }}</div>
                <div class="fin-chart"><canvas id="chartMargin"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100"><div class="card-body">
                <h6 class="fin-card-title">{{ __('admin/financials.actual_vs_py') }}</h6>
                <div class="fin-card-desc mb-3">{{ __('admin/financials.rev_compare') }}</div>
                <div class="fin-chart"><canvas id="chartCompare"></canvas></div>
            </div></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var SERIES = { monthly: @json($monthly), yearly: @json($yearly) };
    var L = {
        actual: @json(__('admin/financials.actual')),
        budget: @json(__('admin/financials.budget')),
        prior: @json(__('admin/financials.previous_year')),
        margin: @json(__('admin/financials.gross_margin'))
    };
    var charts = [];

    function col(rows, key) { return rows.map(function (r) { return r[key]; }); }
    function line(label, data, color, dashed) {
        return { label: label, data: data, borderColor: color, backgroundColor: color, borderWidth: 2, pointRadius: 0, tension: .35, borderDash: dashed ? [6, 4] : [] };
    }

    function draw(range) {
        charts.forEach(function (c) { c.destroy(); });
        var rows = SERIES[range], labels = col(rows, 'label');

        charts = [
            new Chart(document.getElementById('chartRevenue'), {
                type: 'line',
                data: { labels: labels, datasets: [
                    line(L.actual, col(rows, 'revenue'), FIN.color.primary),
                    line(L.budget, col(rows, 'budgetRevenue'), FIN.color.gold, true),
                    line(L.prior, col(rows, 'priorRevenue'), FIN.color.grey)
                ] },
                options: FIN.options()
            }),
            new Chart(document.getElementById('chartEbitda'), {
                type: 'bar',
                data: { labels: labels, datasets: [
                    { label: L.actual, data: col(rows, 'ebitda'), backgroundColor: FIN.color.primary, borderRadius: 3 },
                    { label: L.budget, data: col(rows, 'budgetEbitda'), backgroundColor: 'rgba(189, 168, 112, .6)', borderRadius: 3 }
                ] },
                options: FIN.options()
            }),
            new Chart(document.getElementById('chartMargin'), {
                type: 'line',
                data: { labels: labels, datasets: [
                    $.extend(line(L.margin, col(rows, 'grossMargin'), FIN.color.green), { fill: true, backgroundColor: 'rgba(46, 158, 106, .12)' })
                ] },
                options: FIN.options({
                    plugins: { tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + c.parsed.y.toLocaleString('id-ID', { maximumFractionDigits: 1 }) + '%'; } } } },
                    scales: { y: { beginAtZero: false, ticks: { callback: function (v) { return v + '%'; } } } }
                })
            }),
            new Chart(document.getElementById('chartCompare'), {
                type: 'bar',
                data: { labels: labels, datasets: [
                    { label: L.actual, data: col(rows, 'revenue'), backgroundColor: FIN.color.primary, borderRadius: 3 },
                    { label: L.prior, data: col(rows, 'priorRevenue'), backgroundColor: 'rgba(154, 163, 178, .6)', borderRadius: 3 }
                ] },
                options: FIN.options()
            })
        ];
    }

    $('.fin-toggle .btn').on('click', function () {
        $('.fin-toggle .btn').removeClass('btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('btn-primary');
        draw($(this).data('range'));
    });
    draw('monthly');
});
</script>
@endpush
