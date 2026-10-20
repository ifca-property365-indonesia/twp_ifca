@php
    // Menu aktif mengikuti URL saat ini.
    $isOperational = session('Tflag') == 'O';
    $historyOpen = request()->is('tenant/history*');
    $financialsOpen = request()->is('tenant/financials*');
@endphp
<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
    <div class="sidebar-header border-bottom">
        <div class="sidebar-brand sidebar-brand-tenant">
            <a href="https://www.ifca.co.id" target="_blank" rel="noopener noreferrer">
                <img src="{{ url('/images/logo/IFCA.png') }}" alt="IFCA">
            </a>
        </div>
        {{-- desktop: kecilkan / lebarkan sidebar (mode ikon), diingat per browser (app.js) --}}
        <button class="sidebar-collapse d-none d-lg-inline-flex" type="button" aria-expanded="true"
                aria-label="{{ __('common.sidebar_collapse') }}" title="{{ __('common.sidebar_collapse') }}"
                data-label-collapse="{{ __('common.sidebar_collapse') }}" data-label-expand="{{ __('common.sidebar_expand') }}">
            <i class="cil-chevron-left"></i>
        </button>
        <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close"
                onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"></button>
    </div>
    <ul class="sidebar-nav" data-coreui="navigation">
        <li class="nav-title">{{ __('tenant.menu.dashboard') }}</li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('tenant/dash*') ? 'active' : '' }}" href="{{ url('/tenant/dash') }}">
                <i class="nav-icon cil-speedometer"></i> {{ __('tenant.menu.dashboard') }}
            </a>
        </li>

        <li class="nav-title">{{ __('tenant.menu.menu') }}</li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('tenant/ticket*') ? 'active' : '' }}" href="{{ url('/tenant/ticket') }}">
                <i class="nav-icon cil-tags"></i> {{ __('tenant.menu.ticket') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('tenant/overtime*') ? 'active' : '' }}" href="{{ url('/tenant/overtime') }}">
                <i class="nav-icon cil-clock"></i> {{ __('tenant.menu.overtime') }}
            </a>
        </li>
        @unless($isOperational)
        <li class="nav-item">
            <a class="nav-link {{ request()->is('tenant/proforma*') ? 'active' : '' }}" href="{{ url('/tenant/proforma') }}">
                <i class="nav-icon cil-description"></i> {{ __('tenant.menu.proforma_invoice') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('tenant/invoice*') ? 'active' : '' }}" href="{{ url('/tenant/invoice') }}">
                <i class="nav-icon cil-wallet"></i> {{ __('tenant.menu.invoice_outstanding') }}
            </a>
        </li>
        @endunless
        {{-- Financials (tenant): satu menu tanpa submenu; Profit & Loss / Balance Sheet / Cash Flow lewat tab di halaman.
             Sementara disembunyikan: config financials.tenant_enabled (FINANCIALS_TENANT=true untuk menampilkan) --}}
        @if (config('financials.tenant_enabled'))
        <li class="nav-item">
            <a class="nav-link {{ $financialsOpen ? 'active' : '' }}" href="{{ url('/tenant/financials') }}">
                <i class="nav-icon cil-chart-line"></i> {{ __('admin/financials.menu') }}
            </a>
        </li>
        @endif
        <li class="nav-group {{ $historyOpen ? 'show' : '' }}">
            <a class="nav-link nav-group-toggle" href="#">
                <i class="nav-icon cil-history"></i> {{ __('tenant.menu.history') }}
            </a>
            <ul class="nav-group-items compact">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('tenant/history/ticket*') ? 'active' : '' }}" href="{{ url('/tenant/history/ticket') }}" id="ht">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('tenant.menu.history_ticket') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('tenant/history/overtime*') ? 'active' : '' }}" href="{{ url('/tenant/history/overtime') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('tenant.menu.history_overtime') }}
                    </a>
                </li>
                @unless($isOperational)
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('tenant/history/invoice*') ? 'active' : '' }}" href="{{ url('/tenant/history/invoice') }}" id="hb">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('tenant.menu.history_invoice') }}
                    </a>
                </li>
                @endunless
            </ul>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('tenant/news*') ? 'active' : '' }}" href="{{ url('/tenant/news') }}">
                <i class="nav-icon cil-newspaper"></i> {{ __('tenant.menu.news') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('tenant/usersurvey*') || request()->is('tenant/online_survey*') ? 'active' : '' }}" href="{{ url('/tenant/usersurvey/index') }}">
                <i class="nav-icon cil-task"></i> {{ __('tenant.menu.online_survey') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('tenant/permit*') ? 'active' : '' }}" href="{{ url('/tenant/permit/index') }}">
                <i class="nav-icon cil-clipboard"></i> {{ __('tenant.menu.letter_permit') }}
            </a>
        </li>
    </ul>
</div>
<script>
    // sidebar sempit tersimpan (app.js): pasang sebelum halaman tampil supaya tidak berkedip
    try {
        if (window.innerWidth >= 992 && localStorage.getItem('twp.sidebarNarrow') === '1') {
            // tanpa animasi saat memuat halaman (animasi hanya saat tombol panah diklik)
            document.getElementById('sidebar').classList.add('sidebar-narrow', 'sidebar-no-anim');
            window.addEventListener('load', function () {
                setTimeout(function () { document.getElementById('sidebar').classList.remove('sidebar-no-anim'); }, 50);
            });
        }
    } catch (e) {}
</script>
