@php
    // Semua nilai sudah disiapkan Admin\LoginController::createSession (dan diperbarui
    // Admin\AccountController::updateprofile); view tidak perlu query.
    $username  = Session::get('Tsuname');                                        // administrator.name
    $loginName = Session::get('Tsdisplay_name') ?: $username;                     // all_login.name
    $useremail = Session::get('Tsemail');
    $pict      = \App\Support\ProfilePicture::url(Session::get('Tspict'));      // kosong / file tidak ada -> defaultUser.png
@endphp
<header class="header header-sticky p-0 mb-0">
    <div class="container-fluid border-bottom px-3 px-lg-4">
        <button class="header-toggler" type="button" aria-label="Toggle navigation"
                onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()">
            <i class="cil-menu"></i>
        </button>
        <div class="header-app-info ms-2 ms-lg-0">
            <span class="sub-text">{{ __('admin.header.sub_title') }}</span>
            <span class="lead-text">{{ __('admin.header.title') }}</span>
        </div>
        <ul class="header-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link user-toggle py-0" data-coreui-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                    <div class="user-avatar"><img src="{{ $pict }}" alt="" onerror="{{ \App\Support\ProfilePicture::onError() }}"></div>
                    <div class="user-info d-none d-sm-block">
                        <div class="user-name">{{ $loginName }}</div>
                        <div class="user-role">{{ $useremail }}</div>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-user pt-0">
                    <div class="user-card">
                        <div class="user-avatar lg"><img src="{{ $pict }}" alt="" onerror="{{ \App\Support\ProfilePicture::onError() }}"></div>
                        <div>
                            <span class="sub-text">{{ $loginName }}</span>
                            <span class="sub-text">{{ $useremail }}</span>
                        </div>
                    </div>
                    @include('partials.portal_switch', ['current' => 'admin'])
                    @include('partials.language_switch', ['current' => 'admin'])
                    <a class="dropdown-item" href="#" id="profile"><i class="cil-user"></i> {{ __('admin.header.view_profile') }}</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ url('/admin/logout') }}"><i class="cil-account-logout"></i> {{ __('admin.header.sign_out') }}</a>
                </div>
            </li>
        </ul>
    </div>
</header>
<script type="text/javascript">
    $('#profile').on('click', function (e) {
        e.preventDefault();
        $('#modaldialog').removeClass('modal-md').addClass('modal-lg');
        $('#modaltitle').text(@json(__('admin.header.edit_profile')));
        $('#modalbody').load("{{ url('admin/account/profile') }}");
        $('#modal').data('Id', "{{ $useremail }}");
        $('#modalfooter').hide();
        $('#modal').modal('show');
    });
</script>
