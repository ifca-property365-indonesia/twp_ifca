@extends('admin.template.layout2.base')
@section('title', __('admin/users.menu'))

{{--
    Admin Users: daftar user administrator (mgr.all_login) + tambah user, termasuk grup baru (mgr.administrator).
    Controller: Admin\AdminUserController, logika: App\Support\AdminUsers.
--}}
@use('App\Support\AdminUsers', 'AU')

@push('styles')
<style>
    .usr-toolbar { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; justify-content: space-between; }
    .usr-search { position: relative; max-width: 320px; flex: 1 1 240px; }
    .usr-search i { position: absolute; left: .7rem; top: 50%; transform: translateY(-50%); color: var(--cui-secondary-color); }
    .usr-search input { padding-left: 2.1rem; }
    .usr-newgroup { border: 1px dashed #c7cbe8; border-radius: .5rem; background: #fbfbff; padding: .75rem .85rem; }
</style>
@endpush

@section('content')
<div class="page-body">
    <div class="page-head">
        <div class="page-head-row">
            <div class="page-head-content">
                <h3 class="page-title">{{ __('admin/users.title') }}</h3>
                <div class="page-desc"><p>{{ __('admin/users.desc') }}</p></div>
            </div>
            <div class="page-head-content">
                <button type="button" class="btn btn-primary" id="btnAddUser"><i class="cil-user-plus"></i><span>{{ __('admin/users.add') }}</span></button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="usr-toolbar mb-3">
                <div class="small text-soft">{{ trans_choice('admin/users.count', $users->count(), ['count' => $users->count()]) }}</div>
                <div class="usr-search">
                    <i class="cil-magnifying-glass"></i>
                    <input type="search" class="form-control" id="usrSearch" placeholder="{{ __('admin/users.search') }}">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 3rem;">#</th>
                            <th>{{ __('admin/users.col_name') }}</th>
                            <th>{{ __('admin/users.col_email') }}</th>
                            <th>{{ __('admin/users.col_phone') }}</th>
                            <th>{{ __('admin/users.col_group') }}</th>
                            <th class="text-end">{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody id="usrRows">
                        @forelse ($users as $i => $u)
                            <tr data-search="{{ mb_strtolower($u->name . ' ' . $u->email . ' ' . $u->group_name . ' ' . $u->handphone) }}">
                                <td class="text-soft">{{ $i + 1 }}</td>
                                <td class="fw-semibold">{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>{{ $u->handphone ?: '-' }}</td>
                                <td><span class="badge badge-soft-primary">{{ $u->group_name ?? '#' . $u->idforeign }}</span></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-usr-email"
                                            data-id="{{ $u->id }}" data-name="{{ $u->name }}" data-email="{{ $u->email }}"
                                            data-tenants="{{ $u->tenant_accounts }}" data-self="{{ strtolower($u->email) === strtolower((string) Session::get('Tsemail')) ? 1 : 0 }}">
                                        <i class="cil-pencil"></i> {{ __('admin/users.edit_email') }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-soft py-4">{{ __('admin/users.empty') }}</td></tr>
                        @endforelse
                        <tr class="d-none" id="usrNoMatch"><td colspan="6" class="text-center text-soft py-4">{{ __('admin/users.no_match') }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Tambah user --}}
<div class="modal fade" tabindex="-1" id="usrModal">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="usrForm" novalidate autocomplete="off">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('admin/users.add') }}</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="usrName">{{ __('admin/users.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="usrName" name="name" maxlength="{{ AU::MAX_NAME }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="usrPhone">{{ __('admin/users.handphone') }}</label>
                        <input type="text" class="form-control" id="usrPhone" name="handphone" maxlength="{{ AU::MAX_PHONE }}" inputmode="tel">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="usrEmail">{{ __('admin/users.email') }} <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="usrEmail" name="email" maxlength="{{ AU::MAX_EMAIL }}" autocomplete="off">
                        <div class="form-note">{{ __('admin/users.email_note') }}</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="usrGroup">{{ __('admin/users.group') }} <span class="text-danger">*</span></label>
                        <select id="usrGroup" name="group" class="form-select js-select">
                            @foreach ($groups as $g)
                                <option value="{{ $g->id }}" data-sub="{{ $g->admincall }}">{{ $g->name }}</option>
                            @endforeach
                            <option value="new">{{ __('admin/users.new_group') }}</option>
                        </select>
                    </div>
                    <div class="col-12 d-none" id="usrNewGroup">
                        <div class="usr-newgroup">
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label" for="usrGroupName">{{ __('admin/users.group_name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="usrGroupName" name="group_name" maxlength="{{ AU::MAX_NAME }}" placeholder="{{ __('admin/users.group_name_ph') }}">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label" for="usrGroupCode">{{ __('admin/users.group_code') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control font-monospace" id="usrGroupCode" name="group_code" maxlength="{{ AU::MAX_CODE }}">
                                </div>
                            </div>
                            <div class="form-note mt-2">{{ __('admin/users.group_code_note') }} {{ __('admin/users.new_group_note') }}</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ __('admin/users.password') }} <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="password_mode" id="usrPwDefault" value="default" checked>
                            <label class="form-check-label" for="usrPwDefault">{!! __('admin/users.pw_default', ['password' => '<code>' . e($defaultPassword) . '</code>']) !!}</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="password_mode" id="usrPwCustom" value="custom">
                            <label class="form-check-label" for="usrPwCustom">{{ __('admin/users.pw_custom') }}</label>
                        </div>
                    </div>
                    @if ($mailEnabled)
                        <div class="col-12"><div class="alert alert-info py-2 small mb-0"><i class="cil-envelope-closed"></i> {{ __('admin/users.mail_note') }}</div></div>
                    @endif
                    <div class="col-12 d-none" id="usrPwFields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="usrPassword">{{ __('admin/users.password_new') }} <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="usrPassword" name="password" autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="usrPassword2">{{ __('admin/users.password_confirm') }} <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="usrPassword2" name="password_confirmation" autocomplete="new-password">
                            </div>
                        </div>
                        <div class="form-note">{{ __('admin/users.password_note', ['min' => AU::MIN_PASSWORD]) }}</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">{{ __('common.back') }}</button>
                <button type="submit" class="btn btn-primary" id="usrSave"><i class="cil-save"></i><span>{{ __('admin/users.save') }}</span></button>
            </div>
        </form>
    </div>
</div>

{{-- Edit email user administrator --}}
<div class="modal fade" tabindex="-1" id="usrEmailModal">
    <div class="modal-dialog">
        <form class="modal-content" id="usrEmailForm" novalidate autocomplete="off">
            <div class="modal-header">
                <h5 class="modal-title" id="usrEmailTitle"></h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small d-none" id="usrEmailSelf"><i class="cil-info"></i> {{ __('admin/users.email_self_note') }}</div>
                <div class="mb-3">
                    <label class="form-label">{{ __('admin/users.email_current') }}</label>
                    <input type="text" class="form-control" id="usrEmailOld" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="usrEmailNew">{{ __('admin/users.email_new') }} <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="usrEmailNew" name="email" maxlength="100" autocomplete="off">
                    <div class="form-note">{{ __('admin/users.email_new_note') }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="usrEmailPassword">{{ __('admin/users.admin_password') }} <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="usrEmailPassword" name="current_password" autocomplete="current-password">
                    <div class="form-note">{{ __('admin/users.admin_password_note', ['email' => (string) Session::get('Tsemail')]) }}</div>
                </div>
                <div class="form-check d-none" id="usrEmailTenantsWrap">
                    <input class="form-check-input" type="checkbox" id="usrEmailTenants" name="with_tenants" value="1">
                    <label class="form-check-label" for="usrEmailTenants" id="usrEmailTenantsLabel"></label>
                </div>
            </div>
            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">{{ __('common.back') }}</button>
                <button type="submit" class="btn btn-primary" id="usrEmailSave"><i class="cil-save"></i><span>{{ __('admin/users.save') }}</span></button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var URL_STORE = @json(url('admin/users'));
    var L = { info: @json(__('common.information')), error: @json(__('common.error')) };
    var modal = coreui.Modal.getOrCreateInstance(document.getElementById('usrModal'));
    var esc = function (s) { return $('<div>').text(s).html(); };
    // kode grup otomatis dari nama, sampai kodenya diubah manual
    var codeTouched = false;
    var slug = function (s) { return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '').slice(0, {{ AU::MAX_CODE }}); };

    function toggleParts() {
        $('#usrNewGroup').toggleClass('d-none', $('#usrGroup').val() !== 'new');
        $('#usrPwFields').toggleClass('d-none', !$('#usrPwCustom').is(':checked'));
    }

    $('#btnAddUser').on('click', function () {
        $('#usrForm')[0].reset();
        codeTouched = false;
        $('#usrGroup').val($('#usrGroup option:first').val()).trigger('change.select2');
        toggleParts();
        modal.show();
    });
    $('#usrGroup').on('change', function () {
        toggleParts();
        if (this.value === 'new') { setTimeout(function () { $('#usrGroupName').trigger('focus'); }, 50); }
    });
    $('input[name=password_mode]').on('change', toggleParts);
    $('#usrGroupName').on('input', function () { if (!codeTouched) { $('#usrGroupCode').val(slug(this.value)); } });
    $('#usrGroupCode').on('input', function () { codeTouched = true; });

    $('#usrForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#usrSave').prop('disabled', true);
        $.ajax({ url: URL_STORE, type: 'POST', data: $(this).serialize(), dataType: 'json' })
            .done(function (res) {
                Swal.fire({ title: L.info, text: res.pesan, icon: 'success' }).then(function () { window.location.reload(); });
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.pesan) || (xhr.status + ' ' + xhr.statusText);
                Swal.fire({ title: L.error, html: esc(msg).replace(/\n/g, '<br>'), icon: 'error' });
            })
            .always(function () { $btn.prop('disabled', false); });
    });

    // ---------- edit email ----------
    var EMAIL_TITLE = @json(__('admin/users.edit_email_title', ['name' => '__N__']));
    var TENANTS_LABEL = @json(__('admin/users.email_with_tenants', ['count' => '__C__']));
    var emailModal = coreui.Modal.getOrCreateInstance(document.getElementById('usrEmailModal'));
    var emailUserId = null;
    $(document).on('click', '.btn-usr-email', function () {
        var $b = $(this), tenants = parseInt($b.data('tenants'), 10) || 0;
        emailUserId = $b.data('id');
        $('#usrEmailForm')[0].reset();
        $('#usrEmailTitle').text(EMAIL_TITLE.replace('__N__', $b.data('name')));
        $('#usrEmailOld').val($b.data('email'));
        $('#usrEmailSelf').toggleClass('d-none', String($b.data('self')) !== '1');
        $('#usrEmailTenantsWrap').toggleClass('d-none', tenants === 0);
        $('#usrEmailTenantsLabel').text(TENANTS_LABEL.replace('__C__', tenants));
        emailModal.show();
        setTimeout(function () { $('#usrEmailNew').trigger('focus'); }, 200);
    });
    $('#usrEmailForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#usrEmailSave').prop('disabled', true);
        $.ajax({ url: URL_STORE + '/' + emailUserId + '/email', type: 'POST', data: $(this).serialize(), dataType: 'json' })
            .done(function (res) {
                Swal.fire({ title: L.info, text: res.pesan, icon: 'success' }).then(function () { window.location.reload(); });
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.pesan) || (xhr.status + ' ' + xhr.statusText);
                Swal.fire({ title: L.error, html: esc(msg).replace(/\n/g, '<br>'), icon: 'error' });
            })
            .always(function () { $btn.prop('disabled', false); });
    });

    // pencarian
    $('#usrSearch').on('input', function () {
        var q = this.value.trim().toLowerCase(), shown = 0;
        $('#usrRows tr[data-search]').each(function () {
            var hit = !q || this.getAttribute('data-search').indexOf(q) !== -1;
            $(this).toggleClass('d-none', !hit);
            shown += hit ? 1 : 0;
        });
        $('#usrNoMatch').toggleClass('d-none', shown > 0 || !$('#usrRows tr[data-search]').length);
    });
});
</script>
@endpush
