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
    .fin-filters { display: flex; flex-wrap: wrap; gap: .5rem; }
    .fin-filter { display: flex; align-items: center; gap: .4rem; margin: 0; padding: .3rem .1rem .3rem .65rem; border: 1px solid var(--cui-border-color); border-radius: .5rem; background: var(--cui-body-bg); cursor: pointer; }
    .fin-filter:focus-within { border-color: #4f5bd5; box-shadow: 0 0 0 .2rem rgba(79, 91, 213, .15); }
    .fin-filter > i { color: var(--cui-secondary-color); }
    .fin-filter-label { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--cui-secondary-color); white-space: nowrap; }
    .fin-filter .form-select { width: auto; border: 0; box-shadow: none; background-color: transparent; font-weight: 600; color: var(--cui-emphasis-color); padding-left: .15rem; padding-right: 1.75rem; background-position: right .4rem center; cursor: pointer; }
    /* Select2 di dalam kotak filter: tanpa bingkai sendiri, lebar mengikuti teks pilihan */
    .fin-filter .select2.select2-container { width: auto !important; }
    .fin-filter .select2.select2-container--bootstrap-5 .select2-selection.select2-selection--single { min-height: 0; padding: .25rem 1.75rem .25rem .15rem; border: 0; box-shadow: none; background-color: transparent; background-position: right .4rem center; font-size: .875rem; font-weight: 600; color: var(--cui-emphasis-color); cursor: pointer; }
    .fin-filter .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { padding: 0; color: var(--cui-emphasis-color); white-space: nowrap; }
    /* panel dropdown filter: turun sedikit agar tidak menempel ke bingkai kotak filter */
    .select2-container--bootstrap-5 .select2-dropdown.fin-dropdown.select2-dropdown--below { margin-top: .75rem; }
    .select2-container--bootstrap-5 .select2-dropdown.fin-dropdown.select2-dropdown--above { margin-top: -.75rem; }
    /* Group: "Consolidated" dipisah garis dari daftar perusahaan */
    .select2-container--bootstrap-5 .select2-dropdown.fin-dropdown-group .select2-results__options .select2-results__option:first-child { position: relative; margin-bottom: .6rem; overflow: visible; }
    .select2-container--bootstrap-5 .select2-dropdown.fin-dropdown-group .select2-results__options .select2-results__option:first-child::after { content: ""; position: absolute; left: 0; right: 0; bottom: calc(-.3rem - 1px); border-top: 1px solid #e3e6ef; }
    @media (max-width: 575.98px) {
        .fin-filter { flex: 1 1 100%; }
        .fin-filter .form-select, .fin-filter .select2.select2-container { flex: 1; min-width: 0; width: 100% !important; }
    }
</style>
@endpush

<form method="get" action="{{ url()->current() }}" class="fin-filters mb-3" id="finFilters">
    @foreach ($filters as $name => $icon)
        <div class="fin-filter">
            <i class="{{ $icon }}"></i>
            <span class="fin-filter-label">{{ __('admin/financials.f_' . $name) }}</span>
            <select name="{{ $name }}" class="form-select form-select-sm js-select" data-dropdown-class="fin-dropdown fin-dropdown-{{ $name }}">
                @foreach ($options[$name] as $key => $label)
                    <option value="{{ $key }}" @selected($filter[$name] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endforeach
</form>

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
    // Filter: ganti pilihan langsung memuat ulang halaman (filter disimpan di session)
    $('#finFilters select').on('change', function () { this.form.submit(); });
    // klik ikon / judul kotak filter juga membuka dropdown-nya
    $('#finFilters .fin-filter').on('click', function (e) {
        if ($(e.target).closest('.select2-container').length) { return; }
        $(this).find('select').select2('open');
    });

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
