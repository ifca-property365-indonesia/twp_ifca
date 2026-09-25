{{--
    Kepala halaman Financials: judul, keterangan periode, tab 4 halaman, dan helper bersama
    (CSS kartu + JS format rupiah / warna / default Chart.js). Dipakai ke-4 halaman.
    Variabel: $title, $desc, $tab (overview | pl | bs | cf)
--}}
@php
    $tabs = [
        'overview' => ['url' => url($base), 'label' => __('admin/financials.tab_overview')],
        'pl'       => ['url' => url($base . '/profit-loss'), 'label' => __('admin/financials.tab_pl')],
        'bs'       => ['url' => url($base . '/balance-sheet'), 'label' => __('admin/financials.tab_bs')],
        'cf'       => ['url' => url($base . '/cash-flow'), 'label' => __('admin/financials.tab_cf')],
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
</style>
@endpush

<div class="page-head">
    <div class="page-head-row">
        <div class="page-head-content">
            <h3 class="page-title">{{ $title }}</h3>
            <div class="page-desc"><p>{{ $desc }}</p></div>
        </div>
        <div class="page-head-content">
            <span class="badge badge-soft-warning"><i class="cil-info"></i> {{ __('admin/financials.demo_note') }}</span>
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
        color: { primary: '#4f5bd5', gold: '#bda870', green: '#2e9e6a', red: '#d0473b', grey: '#9aa3b2', light: 'rgba(79, 91, 213, .12)' },
        // Rp 45,9 M (miliar) / Rp 451,9 jt (juta), sama dengan PHP FinancialsDemo::idr()
        idr: function (v, digits) {
            if (v === null || v === undefined) { return '-'; }
            var abs = Math.abs(v), sign = v < 0 ? '-' : '', d = digits === undefined ? 1 : digits;
            var num = function (x) { return x.toLocaleString('id-ID', { minimumFractionDigits: d, maximumFractionDigits: d }); };
            if (abs >= 1e9) { return sign + 'Rp ' + num(abs / 1e9) + ' M'; }
            if (abs >= 1e6) { return sign + 'Rp ' + num(abs / 1e6) + ' jt'; }
            return sign + 'Rp ' + abs.toLocaleString('id-ID');
        },
        axisIdr: function (v) { return v === 0 ? 'Rp 0' : FIN.idr(v, 1); },
        // opsi dasar grafik: legend bawah, tooltip rupiah, sumbu Y rupiah
        options: function (extra) {
            return $.extend(true, {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                    tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + FIN.idr(c.parsed.y); } } }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { callback: FIN.axisIdr, maxTicksLimit: 6 }, grid: { color: 'rgba(0, 0, 0, .05)' } }
                }
            }, extra || {});
        }
    };
</script>
@endpush
