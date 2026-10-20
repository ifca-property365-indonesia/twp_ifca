{{--
    Filter Financials (group, period, currency, compare). Dipakai financials._head dua kali:
    di header (layar lebar, .fin-filters-header) dan di atas judul halaman (layar lebih sempit, .fin-filters-page).
    Variabel: $filters (nama => ikon), $options, $filter, $class (kelas tambahan form)
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
