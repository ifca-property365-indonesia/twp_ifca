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
        {{-- Financials: klik judul = Overview; submenu = tab lain (sama dengan admin) --}}
        <li class="nav-group {{ $financialsOpen ? 'show' : '' }}">
            <a class="nav-link nav-group-toggle {{ rtrim(request()->path(), '/') === 'tenant/financials' ? 'active' : '' }}" id="navFinancials" href="{{ url('/tenant/financials') }}">
                <i class="nav-icon cil-chart-line"></i> {{ __('admin/financials.menu') }}
            </a>
            <ul class="nav-group-items compact">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('tenant/financials/profit-loss*') ? 'active' : '' }}" href="{{ url('/tenant/financials/profit-loss') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin/financials.tab_pl') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('tenant/financials/balance-sheet*') ? 'active' : '' }}" href="{{ url('/tenant/financials/balance-sheet') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin/financials.tab_bs') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('tenant/financials/cash-flow*') ? 'active' : '' }}" href="{{ url('/tenant/financials/cash-flow') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin/financials.tab_cf') }}
                    </a>
                </li>
            </ul>
        </li>
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
@unless ($financialsOpen)
<script>
    // Dari halaman lain, judul grup Financials langsung membuka Overview. Di halaman Financials
    // sendiri tidak dipasang: klik judul hanya membuka/menutup grup (perilaku CoreUI), tanpa reload.
    document.getElementById('navFinancials').addEventListener('click', function (e) {
        e.stopPropagation();
        window.location.href = this.href;
    });
</script>
@endunless
