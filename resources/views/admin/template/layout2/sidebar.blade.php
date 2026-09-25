@php
    // Menu aktif mengikuti URL saat ini.
    $newsOpen     = request()->is('admin/news*');
    $surveyOpen   = request()->is('admin/usersurvey*');
    $historyOpen  = request()->is('admin/history*') && !request()->is('admin/history/overtime*');
    $overtimeOpen = request()->is('admin/overtime*') || request()->is('admin/history/overtime*');
    $passwordOpen = request()->is('admin/account/reset*') || request()->is('admin/systemspec/defaultpass*');
    $financialsOpen = request()->is('admin/financials*');
    $isExact = function ($path) {
        return rtrim(request()->path(), '/') === trim($path, '/');
    };
@endphp
<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
    <div class="sidebar-header border-bottom">
        <div class="sidebar-brand">
            <a href="{{ url('/admin/dash') }}" class="d-flex align-items-center gap-2 text-decoration-none text-white">
                <img src="{{ url('/images/logo/IFCA.png') }}" alt="IFCA">
                <span class="fw-bold">{{ __('admin.menu.brand') }}</span>
            </a>
        </div>
        <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close"
                onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"></button>
    </div>
    <ul class="sidebar-nav" data-coreui="navigation">
        <li class="nav-title">{{ __('admin.menu.dashboards') }}</li>
        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/dash*') ? 'active' : '' }}" href="{{ url('/admin/dash') }}">
                <i class="nav-icon cil-speedometer"></i> {{ __('admin.menu.dashboard') }}
            </a>
        </li>

        <li class="nav-title">{{ __('admin.menu.menu') }}</li>

        {{-- News feed --}}
        <li class="nav-group {{ $newsOpen ? 'show' : '' }}">
            <a class="nav-link nav-group-toggle" href="#"><i class="nav-icon cil-newspaper"></i> {{ __('admin.menu.news_feed') }}</a>
            <ul class="nav-group-items compact">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/news/form/*') ? 'active' : '' }}" href="{{ url('/admin/news/form/A') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.create_news') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $isExact('admin/news') ? 'active' : '' }}" href="{{ url('/admin/news') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.list_news') }}
                    </a>
                </li>
            </ul>
        </li>

        {{-- Online survey --}}
        <li class="nav-group {{ $surveyOpen ? 'show' : '' }}">
            <a class="nav-link nav-group-toggle" href="#"><i class="nav-icon cil-task"></i> {{ __('admin.menu.online_survey') }}</a>
            <ul class="nav-group-items compact">
                <li class="nav-item">
                    <a class="nav-link {{ $isExact('admin/usersurvey') ? 'active' : '' }}" href="{{ url('/admin/usersurvey/') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.survey_questions') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/usersurvey/results*') ? 'active' : '' }}" href="{{ url('/admin/usersurvey/results') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.survey_results') }}
                    </a>
                </li>
            </ul>
        </li>

        @if(Session::get('Tsuname') == 'Admin Management')
        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/management*') ? 'active' : '' }}" href="{{ url('/admin/management') }}">
                <i class="nav-icon cil-bar-chart"></i> {{ __('admin.menu.graph_management') }}
            </a>
        </li>
        @endif

        {{-- Financials: klik judul = Overview; submenu = tab lain (Profit & Loss, Balance Sheet, Cash Flow) --}}
        <li class="nav-group {{ $financialsOpen ? 'show' : '' }}">
            <a class="nav-link nav-group-toggle {{ $isExact('admin/financials') ? 'active' : '' }}" id="navFinancials" href="{{ url('/admin/financials') }}"><i class="nav-icon cil-chart-line"></i> {{ __('admin/financials.menu') }}</a>
            <ul class="nav-group-items compact">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/financials/profit-loss*') ? 'active' : '' }}" href="{{ url('/admin/financials/profit-loss') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin/financials.tab_pl') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/financials/balance-sheet*') ? 'active' : '' }}" href="{{ url('/admin/financials/balance-sheet') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin/financials.tab_bs') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/financials/cash-flow*') ? 'active' : '' }}" href="{{ url('/admin/financials/cash-flow') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin/financials.tab_cf') }}
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->is('admin/permit*') ? 'active' : '' }}" href="{{ url('/admin/permit/index') }}">
                <i class="nav-icon cil-clipboard"></i> {{ __('admin.menu.letter_permit') }}
            </a>
        </li>

        {{-- Overtime --}}
        <li class="nav-group {{ $overtimeOpen ? 'show' : '' }}">
            <a class="nav-link nav-group-toggle" href="#"><i class="nav-icon cil-clock"></i> {{ __('admin.menu.overtime') }}</a>
            <ul class="nav-group-items compact">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/overtime/approval*') ? 'active' : '' }}" href="{{ url('/admin/overtime/approval') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.overtime_approval') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/overtime/posting*') ? 'active' : '' }}" href="{{ url('/admin/overtime/posting') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.overtime_posting') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/overtime/layout*') ? 'active' : '' }}" href="{{ url('/admin/overtime/layout') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.overtime_layout') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/history/overtime*') ? 'active' : '' }}" href="{{ url('/admin/history/overtime') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.overtime_history') }}
                    </a>
                </li>
            </ul>
        </li>

        {{-- History --}}
        <li class="nav-group {{ $historyOpen ? 'show' : '' }}">
            <a class="nav-link nav-group-toggle" href="#"><i class="nav-icon cil-history"></i> {{ __('admin.menu.history') }}</a>
            <ul class="nav-group-items compact">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/history/ticket*') ? 'active' : '' }}" href="{{ url('/admin/history/ticket') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.ticket') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/history/users*') ? 'active' : '' }}" href="{{ url('/admin/history/users') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.log_user') }}
                    </a>
                </li>
            </ul>
        </li>

        {{-- Password --}}
        <li class="nav-group {{ $passwordOpen ? 'show' : '' }}">
            <a class="nav-link nav-group-toggle" href="#"><i class="nav-icon cil-lock-locked"></i> {{ __('admin.menu.password') }}</a>
            <ul class="nav-group-items compact">
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/account/reset*') ? 'active' : '' }}" href="{{ url('/admin/account/reset') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.password_reset') }}
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->is('admin/systemspec/defaultpass*') ? 'active' : '' }}" href="{{ url('/admin/systemspec/defaultpass') }}">
                        <span class="nav-icon"><span class="nav-icon-bullet"></span></span> {{ __('admin.menu.default_password') }}
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</div>
<script>
    // Judul grup Financials selalu membuka Overview, tanpa membuka/menutup grup; grup otomatis terbuka
    // di halaman Financials. CoreUI memasang handler toggle grup di fase capture pada .sidebar-nav,
    // jadi klik ditangkap lebih dulu di fase capture document lalu dihentikan sebelum sampai ke CoreUI.
    document.addEventListener('click', function (e) {
        var link = e.target.closest('#navFinancials');
        if (!link) { return; }
        e.preventDefault();
        e.stopPropagation();
        window.location.href = link.href;
    }, true);
</script>
