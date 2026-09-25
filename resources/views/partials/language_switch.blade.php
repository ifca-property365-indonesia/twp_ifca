{{--
    Pilihan bahasa di dropdown user (header admin & tenant), tepat sebelum View Profile.
    Dipakai: @include('partials.language_switch', ['current' => 'admin' | 'tenant'])
    Label diambil dari lang/{locale}/{admin,tenant}.php; nama bahasa ditulis dalam bahasanya sendiri.
    Bendera: images/flags/{kode bahasa}.svg (SVG, karena emoji bendera tidak tampil di Windows).
--}}
<div class="dropdown-header bg-body-tertiary fw-semibold text-body-secondary small">{{ __($current . '.header.language') }}</div>
@foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $name)
    <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() === $code ? 'active' : '' }}"
       href="{{ url('/language/' . $code) }}" lang="{{ $code }}"
       @if (app()->getLocale() === $code) aria-current="true" @endif>
        <img class="lang-flag" src="{{ url('images/flags/' . $code . '.svg') }}" alt="" width="22" height="15"> {{ $name }}
        @if (app()->getLocale() === $code)<i class="cil-check-alt ms-auto me-0"></i>@endif
    </a>
@endforeach
<div class="dropdown-divider"></div>
