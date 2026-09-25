@php
    // Semua nilai sudah disiapkan Tenant\LoginController::createSession (dan diperbarui
    // Tenant\AccountController::updateprofile); view tidak perlu query.
    $portalType = Session::get('Tflag') === 'O' ? __('tenant.header.operational') : '';
    $username   = Session::get('Tdisplay_name') ?: Session::get('TCompany');   // all_login.name
    $contact    = Session::get('Tuname');                                        // tenant.contact_name
    $useremail  = Session::get('Tenemail');
    $pict       = Session::get('Tpict');
    $pict       = ($pict && !str_ends_with($pict, '/')) ? $pict : url('images/default/defaultUser.png');   // data lama bisa berisi '.../images/user/' tanpa nama file
@endphp
<header class="header header-sticky p-0 mb-0">
    <div class="container-fluid border-bottom px-3 px-lg-4">
        <button class="header-toggler" type="button" aria-label="Toggle navigation"
                onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
            <i class="cil-menu"></i>
        </button>
        <div class="header-app-info ms-2 ms-lg-0">
            <span class="sub-text">{{ __('tenant.header.sub_title') }}</span>
            <span class="lead-text">{{ __('tenant.header.title') }}{{ $portalType ? " ({$portalType})" : '' }}</span>
        </div>
        <ul class="header-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link user-toggle py-0" data-coreui-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <div class="user-avatar"><img src="{{ $pict }}" alt=""></div>
                    <div class="user-info d-none d-sm-block">
                        <div class="user-name">{{ $username }}</div>
                        <div class="user-role">{{ $contact }}</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-user pt-0">
                    <div class="user-card">
                        <div class="user-avatar lg"><img src="{{ $pict }}" alt=""></div>
                        <div>
                            <span class="lead-text">{{ $username }}</span>
                            <span class="sub-text">{{ $contact }}</span>
                            <span class="sub-text">{{ $useremail }}</span>
                        </div>
                    </div>
                    @include('partials.portal_switch', ['current' => 'tenant'])
                    @include('partials.language_switch', ['current' => 'tenant'])
                    <a class="dropdown-item" href="#" id="profile"><i class="cil-user"></i> {{ __('tenant.header.view_profile') }}</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ url('tenant/logout') }}"><i class="cil-account-logout"></i> {{ __('tenant.header.sign_out') }}</a>
                </div>
            </li>
        </ul>
    </div>
</header>
<script type="text/javascript">
    $('#profile').on('click', function (e) {
        e.preventDefault();
        $('#modaldialog').removeClass('modal-md').addClass('modal-lg');
        $('#modaltitle').text(@json(__('tenant.header.edit_profile')));
        $('#modalbody').load("{{ url('tenant/account/profile') }}");
        $('#modal').data('Id', "{{ $useremail }}");
        $('#modalfooter').hide();
        $('#modal').modal('show');
    });
</script>
