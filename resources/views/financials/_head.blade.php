{{--
    Kepala halaman Financials: filter (group, period, currency, compare), judul, keterangan periode,
    tab 4 halaman, dan helper bersama (CSS kartu + JS format uang / warna / default Chart.js).
    Dipakai ke-4 halaman. Variabel: $title, $desc, $tab (overview | pl | bs | cf);
    dari controller: $filter, $options, $currency.
--}}
@php
    $tabs = [
        'overview' => ['url' => url($base), 'label' => __('admin/financials.tab_overview')],
        'pl'       => ['url' => url($base . '/profit-loss'), 'label' => __('admin/financials.tab_pl')],
        'bs'       => ['url' => url($base . '/balance-sheet'), 'label' => __('admin/financials.tab_bs')],
        'cf'       => ['url' => url($base . '/cash-flow'), 'label' => __('admin/financials.tab_cf')],
    ];
    $filters = [
        'group'    => 'cil-building',
        'period'   => 'cil-calendar',
        'currency' => 'cil-dollar',
        'compare'  => 'cil-swap-horizontal',
    ];
@endphp

@push('styles')
<style>
    .fin-kpi { height: 100%; }
    .fin-kpi .card-body { padding: 1rem 1.1rem; }
    .fin-kpi .fin-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--cui-secondary-color); }
    .fin-kpi .fin-value { font-size: 1.55rem; font-weight: 700; color: var(--cui-emphasis-color); line-height: 1.25; margin-top: .35rem; }
    .fin-kpi .fin-sub { font-size: .8rem; color: var(--cui-secondary-color); margin-top: .25rem; }
    .fin-up { color: #1b7f4a; font-weight: 600; }
    .fin-down { color: #b42318; font-weight: 600; }
    .fin-card-title { font-weight: 700; margin: 0; }
    .fin-card-desc { font-size: .8rem; color: var(--cui-secondary-color); }
    .fin-chart { position: relative; height: 300px; }
    .fin-chart.lg { height: 340px; }
    .fin-link-card { transition: box-shadow .15s ease, transform .15s ease; }
    .fin-link-card:hover { box-shadow: 0 .35rem 1rem rgba(31, 41, 55, .08); transform: translateY(-1px); }
    .fin-link-card .fin-icon { width: 2.25rem; height: 2.25rem; border-radius: .5rem; display: inline-flex; align-items: center; justify-content: center; background: #eef0ff; color: #4f5bd5; font-size: 1.1rem; }
    .fin-table th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--cui-secondary-color); white-space: nowrap; }
    .fin-table td, .fin-table th { vertical-align: middle; }
    .fin-table tr.fin-total td { font-weight: 700; background: var(--cui-tertiary-bg); }
    .fin-list li { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; padding: .55rem 0; border-bottom: 1px solid var(--cui-border-color-translucent); }
    .fin-list li:last-child { border-bottom: 0; }
    .fin-list .fin-total-row { font-weight: 700; border-top: 2px solid var(--cui-border-color); border-bottom: 0; }
    .fin-toggle .btn { min-width: 6rem; }
    .fin-fx { margin-top: -.5rem; font-size: .75rem; color: var(--cui-secondary-color); }
</style>
@endpush

{{-- filter: di header pada layar lebar (stack header-tools), di sini pada layar lebih sempit --}}
@push('header-tools')
    @include('partials.header_filters', ['class' => 'fin-filters-header'])
@endpush
@include('partials.header_filters', ['class' => 'fin-filters-page mb-3'])
{{-- keterangan kurs (Frankfurter API, base IDR; lihat FinancialsDemo::fxRates). Sementara disembunyikan;
     tampilkan lagi dengan FRANKFURTER_SHOW_RATE=true (config services.frankfurter.show_rate) --}}
@if ($currency['code'] !== 'IDR' && config('services.frankfurter.show_rate'))
    @php $fxRate = 'Rp ' . number_format($currency['rate'], 2, ',', '.'); @endphp
    <div class="fin-fx mb-3"><i class="cil-swap-horizontal"></i>
        @if ($currency['source'] === 'frankfurter')
            {{ __('admin/financials.fx_rate', ['code' => $currency['code'], 'rate' => $fxRate, 'date' => $currency['date'] ? \Illuminate\Support\Carbon::parse($currency['date'])->locale(app()->getLocale())->translatedFormat('d M Y') : '-']) }}
        @else
            <span class="text-warning-emphasis">{{ __('admin/financials.fx_fallback', ['code' => $currency['code'], 'rate' => $fxRate]) }}</span>
        @endif
    </div>
@endif

<div class="page-head">
    <div class="page-head-row">
        <div class="page-head-content">
            <h3 class="page-title">{{ $title }}</h3>
            <div class="page-desc"><p>{{ $desc }}</p></div>
        </div>
    </div>
</div>

<ul class="nav nav-underline-border mb-3">
    @foreach ($tabs as $key => $t)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ $t['url'] }}">{{ $t['label'] }}</a>
        </li>
    @endforeach
</ul>

@push('scripts')
<script>
    // Helper bersama halaman Financials
    window.FIN = {
        currency: @json($currency),
        color: { primary: '#4f5bd5', gold: '#bda870', green: '#2e9e6a', red: '#d0473b', grey: '#9aa3b2', light: 'rgba(79, 91, 213, .12)' },
        // nilai IDR -> mata uang terpilih, sama dengan PHP FinancialsDemo::money():
        // IDR: Rp 45,9 M (miliar) / Rp 451,9 jt (juta); lainnya: $ 2,8 M (juta) / $ 341,5 K (ribu) / $ 1,2 B (miliar)
        money: function (v, digits) {
            if (v === null || v === undefined) { return '-'; }
            var c = FIN.currency, x = v / c.rate, abs = Math.abs(x), sign = x < 0 ? '-' : '', d = digits === undefined ? 1 : digits;
            var num = function (n) { return n.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d }); };
            var units = c.code === 'IDR' ? [[1e9, 'M'], [1e6, 'jt']] : [[1e9, 'B'], [1e6, 'M'], [1e3, 'K']];
            for (var i = 0; i < units.length; i++) {
                if (abs >= units[i][0]) { return sign + c.symbol + ' ' + num(abs / units[i][0]) + ' ' + units[i][1]; }
            }
            return sign + c.symbol + ' ' + Math.round(abs).toLocaleString('id-ID');
        },
        axisMoney: function (v) { return v === 0 ? FIN.currency.symbol + ' 0' : FIN.money(v, 1); },
        // opsi dasar grafik: legend bawah, tooltip rupiah, sumbu Y rupiah
        options: function (extra) {
            return $.extend(true, {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + FIN.money(c.parsed.y); } } }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { callback: FIN.axisMoney, maxTicksLimit: 6 }, grid: { color: 'rgba(0, 0, 0, .05)' } }
                }
            }, extra || {});
        }
    };
</script>
@endpush
