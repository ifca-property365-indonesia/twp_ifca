{{--
    Filter kotak berikon (GET, ganti pilihan = muat ulang halaman; gaya & perilaku di app.css / app.js).
    Dipakai dua kali per halaman: di header (stack header-tools, .fin-filters-header) dan di atas judul
    halaman untuk layar lebih sempit (.fin-filters-page). Financials: group/period/currency/compare;
    Reports & Documents: group saja (+ .fin-filters-lg agar di header mulai 992px).
    Variabel: $filters (nama => ikon), $options (nama => [kunci => label]), $filter (nama => terpilih), $class
--}}
<form method="get" action="{{ url()->current() }}" class="fin-filters {{ $class ?? '' }}">
    @foreach ($filters as $name => $icon)
        <div class="fin-filter">
            <i class="{{ $icon }}"></i>
            <span class="fin-filter-label">{{ __('admin/financials.f_' . $name) }}</span>
            <select name="{{ $name }}" class="form-select form-select-sm js-select" data-dropdown-class="fin-dropdown fin-dropdown-{{ $name }}" aria-label="{{ __('admin/financials.f_' . $name) }}">
                @foreach ($options[$name] as $key => $label)
                    <option value="{{ $key }}" @selected($filter[$name] === $key) @if ($name === 'group' && $key !== 'consolidated') data-sub="{{ __('admin/financials.businesses.' . $key) }}" @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endforeach
</form>
