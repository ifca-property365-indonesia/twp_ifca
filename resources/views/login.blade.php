@extends('layouts.auth')

@section('title', __('shared/login.page_title'))

@section('content')
    <h1 class="login-title">{{ __('shared/login.log_in') }}</h1>

    @if (session('alert'))
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="cil-warning me-2"></i><div>{{ session('alert') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="cil-warning me-2"></i><div>{{ $errors->first() }}</div>
        </div>
    @endif

    <form action="{{ url('/login') }}" method="POST" id="formlogin" novalidate autocomplete="on">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="email">{{ __('common.email') }}</label>
            <div class="form-control-wrap">
                <div class="form-icon form-icon-left"><i class="cil-envelope-closed"></i></div>
                <input type="email" class="form-control form-control-lg" name="email" id="email"
                       placeholder="{{ __('shared/login.ph_email') }}" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>        </div>

        {{-- muncul otomatis kalau email adalah tenant (diisi via /login/businesses) --}}
        <div class="mb-3" id="bsn-group" style="display:none">
            <label class="form-label" for="bsn">{{ __('shared/login.business_name') }}</label>
            <div class="form-control-wrap">
                <div class="form-icon form-icon-left"><i class="cil-building"></i></div>
                <select class="form-select" name="bsn" id="bsn" data-old="{{ old('bsn') }}"></select>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label" for="password">{{ __('common.password') }}</label>
            <div class="password-wrap">
                <input type="password" class="form-control form-control-lg" name="password" id="password"
                       placeholder="{{ __('shared/login.ph_password') }}" required autocomplete="current-password">
                <span class="toggle-password" data-target="password" title="{{ __('shared/login.toggle_password') }}"><i class="cil-lock-locked"></i></span>
            </div>
        </div>

        <button type="submit" class="btn btn-lg btn-primary w-100">{{ __('shared/login.log_in') }}</button>
    </form>

    {{-- Akun demo: hanya kalau LOGIN_DEMO_ACCOUNTS=true di .env (config/demo_accounts.php) --}}
    @if (config('demo_accounts.enabled') && config('demo_accounts.accounts'))
        <div class="login-demo mt-3">
            <div class="fw-semibold">{{ __('shared/login.demo_title') }}</div>
            <div class="login-demo-note">
                {!! __('shared/login.demo_password', ['password' => '<code>' . e(config('demo_accounts.password')) . '</code>']) !!}
                · {{ __('shared/login.demo_hint') }}
            </div>
            @foreach (config('demo_accounts.accounts') as $acc)
                <button type="button" class="login-demo-item" data-email="{{ $acc['email'] }}">
                    <span class="font-monospace">{{ $acc['email'] }}</span>
                    <span class="fw-semibold">{{ $acc['role'] }}</span>
                </button>
            @endforeach
        </div>
    @endif
@endsection

@push('scripts')
<script>
    // Setelah email diisi: cek apakah email tenant -> tampilkan dropdown Business Name.
    // Password baru bisa diisi setelah pengecekan email selesai.
    (function () {
        var emailEl = document.getElementById('email');
        var group = document.getElementById('bsn-group');
        var select = document.getElementById('bsn');
        var passwordEl = document.getElementById('password');
        var passwordPlaceholder = passwordEl.placeholder;
        var lastEmail = null;
        var timer = null;
        var demoPassword = null;   // diisi saat klik akun demo, dipakai setelah render()

        function lockPassword(checking) {
            passwordEl.disabled = true;
            passwordEl.value = '';
            passwordEl.placeholder = checking ? @json(__('shared/login.checking_email')) : @json(__('shared/login.enter_email_first'));
        }
        function unlockPassword() {
            passwordEl.disabled = false;
            passwordEl.placeholder = passwordPlaceholder;
        }
        lockPassword(false);

        // Respons server: admin (true/false) dan daftar tenants (id, name).
        // Email admin selalu masuk sebagai admin (pindah ke tenant lewat menu di header),
        // jadi dropdown business hanya muncul untuk email tenant biasa.
        // Status email (tidak terdaftar / expired) sengaja tidak dikirim: baru diberitahukan
        // setelah password benar (PortalLoginController::inactiveMessage).
        function render(data) {
            var tenants = data.admin ? [] : (data.tenants || []);
            var list = tenants.slice();

            unlockPassword();
            if (demoPassword !== null) { passwordEl.value = demoPassword; demoPassword = null; }
            select.innerHTML = '';
            if (!tenants.length) {
                group.style.display = 'none';
                select.required = false;
                return;
            }
            var old = select.getAttribute('data-old');
            if (list.length > 1) {
                var ph = document.createElement('option');
                ph.value = '';
                ph.textContent = @json(__('shared/login.choose'));
                select.appendChild(ph);
            }
            list.forEach(function (b) {
                var opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = b.name;
                if (String(b.id) === String(old)) opt.selected = true;
                select.appendChild(opt);
            });
            select.required = list.length > 1;
            group.style.display = '';
        }

        var EMPTY = { admin: false, tenants: [] };
        function load() {
            var email = emailEl.value.trim();
            if (email === lastEmail) return;
            lastEmail = email;
            if (email.indexOf('@') < 0) {
                // belum berupa email: sembunyikan dropdown, password tetap terkunci
                select.innerHTML = '';
                group.style.display = 'none';
                select.required = false;
                lockPassword(false);
                return;
            }
            lockPassword(true);
            $.getJSON("{{ url('/login/businesses') }}", { email: email })
                .done(function (data) { if (emailEl.value.trim() === email) render(data); })
                .fail(function () { if (emailEl.value.trim() === email) render(EMPTY); });
        }

        emailEl.addEventListener('blur', load);
        emailEl.addEventListener('input', function () {
            lockPassword(false);   // email berubah -> harus dicek ulang
            lastEmail = null;
            clearTimeout(timer);
            timer = setTimeout(load, 500);
        });
        if (emailEl.value) load();

        // Akun demo: klik -> isi email, muat business, lalu isi password (setelah render(),
        // karena email yang berubah mengosongkan password lewat lockPassword())
        var DEMO_PASSWORD = @json(config('demo_accounts.enabled') ? config('demo_accounts.password') : null);
        document.querySelectorAll('.login-demo-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                clearTimeout(timer);
                demoPassword = DEMO_PASSWORD;
                emailEl.value = btn.getAttribute('data-email');
                lastEmail = null;
                load();
            });
        });
    })();
</script>
@endpush
