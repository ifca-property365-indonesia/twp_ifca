@extends('admin.template.layout2.base')
@section('title', __('admin/reports.menu'))

{{--
    Reports & Documents: library report per group + upload (banyak file) dengan versi.
    Controller: Admin\ReportController, logika versi: App\Support\ReportDocuments.
--}}
@use('App\Support\ReportDocuments', 'RD')
@php
    $accept = implode(',', array_map(fn ($e) => '.' . $e, $extensions));
    $fileHint = __('admin/reports.files_hint', [
        'ext'   => strtoupper(implode(', ', $extensions)),
        'size'  => ($maxKb / 1024) . ' MB',
        'files' => $maxFiles,
        'total' => RD::size($maxPost),
    ]);
    $icon = function ($name) {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return match (true) {
            in_array($ext, ['xls', 'xlsx', 'xlsm', 'csv'], true) => ['cil-spreadsheet', 'rpt-ico-xls'],
            $ext === 'pdf'                                     => ['cil-description', 'rpt-ico-pdf'],
            in_array($ext, ['doc', 'docx', 'txt'], true)        => ['cil-notes', 'rpt-ico-doc'],
            in_array($ext, ['ppt', 'pptx'], true)               => ['cil-screen-desktop', 'rpt-ico-ppt'],
            in_array($ext, ['jpg', 'jpeg', 'png'], true)        => ['cil-image', 'rpt-ico-img'],
            default                                            => ['cil-file', 'rpt-ico-other'],
        };
    };
    $L = [
        'next'     => __('admin/reports.next_version', ['version' => '__V__', 'count' => '__C__']),
        'total'    => __('admin/reports.total', ['size' => '__S__']),
        'tooMany'  => __('admin/reports.err_too_many', ['max' => $maxFiles]),
        'tooBig'   => __('admin/reports.err_too_big', ['size' => '__S__', 'max' => RD::size($maxPost)]),
        'noFiles'  => __('admin/reports.err_no_files'),
        'info'     => __('common.information'),
        'delTitle' => __('admin/reports.delete_title'),
        'delText'  => __('admin/reports.delete_confirm', ['name' => '__N__', 'version' => '__V__']),
        'yes'      => __('admin/reports.delete'),
        'cancel'   => __('common.cancel'),
        'error'    => __('common.error'),
    ];
@endphp

@push('styles')
<style>
    .rpt-toolbar { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; justify-content: space-between; }
    .rpt-search { position: relative; max-width: 360px; flex: 1 1 260px; }
    .rpt-search i { position: absolute; left: .7rem; top: 50%; transform: translateY(-50%); color: var(--cui-secondary-color); }
    .rpt-search input { padding-left: 2.1rem; }
    .rpt-item { padding: 1rem 1.15rem; border-top: 1px solid var(--cui-border-color-translucent); }
    .rpt-title { font-weight: 700; color: var(--cui-emphasis-color); }
    .rpt-ver { display: inline-block; font-size: .72rem; font-weight: 600; padding: .05rem .4rem; border: 1px solid var(--cui-border-color); border-radius: .3rem; color: var(--cui-secondary-color); margin-left: .35rem; }
    .rpt-meta { font-size: .78rem; color: var(--cui-secondary-color); margin: .15rem 0 .6rem; }
    .rpt-file { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .35rem .6rem; border: 1px solid var(--cui-border-color-translucent); border-radius: .4rem; background: var(--cui-tertiary-bg); margin-bottom: .35rem; font-size: .8rem; text-decoration: none; color: var(--cui-body-color); }
    .rpt-file:hover { border-color: #c7cbe8; background: #f6f7ff; color: var(--cui-body-color); }
    .rpt-file .rpt-fname { font-family: var(--cui-font-monospace); overflow-wrap: anywhere; }
    .rpt-file .rpt-fsize { color: var(--cui-secondary-color); white-space: nowrap; }
    .rpt-ico-xls { color: #1b7f4a; } .rpt-ico-pdf { color: #d0473b; } .rpt-ico-doc { color: #2f6fd0; }
    .rpt-ico-ppt { color: #d4732b; } .rpt-ico-img { color: #7a5ad0; } .rpt-ico-other { color: var(--cui-secondary-color); }
    .rpt-actions { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; margin-top: .6rem; }
    .rpt-pick { list-style: none; padding: 0; margin: .5rem 0 0; max-height: 240px; overflow-y: auto; }
    .rpt-pick li { display: flex; justify-content: space-between; align-items: center; gap: .5rem; padding: .3rem .5rem; border-bottom: 1px solid var(--cui-border-color-translucent); font-size: .8rem; }
    .rpt-pick li:last-child { border-bottom: 0; }
    .rpt-tag { font-size: .68rem; font-weight: 600; padding: .05rem .4rem; border-radius: .3rem; white-space: nowrap; }
    .rpt-tag.keep { background: #eceef2; color: #4b5563; }
    .rpt-summary { font-size: .8rem; font-weight: 600; color: var(--cui-emphasis-color); }
    .rpt-hist-ver { border: 1px solid var(--cui-border-color-translucent); border-radius: .5rem; padding: .75rem .9rem; margin-bottom: .75rem; }
    .rpt-hist-ver.latest { border-color: #c7cbe8; background: #fbfbff; }
</style>
@endpush

{{-- filter Group: di header (layar >= 992px) dan di atas judul halaman (layar lebih sempit) --}}
@php
    $groupFilter = ['filters' => ['group' => 'cil-building'], 'options' => ['group' => $groups], 'filter' => ['group' => $group]];
@endphp
@push('header-tools')
    @include('partials.header_filters', $groupFilter + ['class' => 'fin-filters-header fin-filters-lg'])
@endpush

@section('content')
<div class="page-body">
    @include('partials.header_filters', $groupFilter + ['class' => 'fin-filters-page fin-filters-lg mb-3'])
    <div class="page-head">
        <div class="page-head-row">
            <div class="page-head-content">
                <h3 class="page-title">{{ __('admin/reports.title') }}</h3>
                <div class="page-desc"><p>{{ __('admin/reports.desc') }}</p></div>
            </div>
            <div class="page-head-content">
                <button type="button" class="btn btn-primary" id="btnUploadReport"><i class="cil-cloud-upload"></i><span>{{ __('admin/reports.upload') }}</span></button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body pb-0">
            <div class="rpt-toolbar mb-3">
                <div>
                    <h6 class="fw-bold mb-0">{{ __('admin/reports.library') }}</h6>
                    <div class="small text-soft" id="rptCount">{{ trans_choice('admin/reports.count', $reports->count(), ['count' => $reports->count()]) }}</div>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="rpt-search">
                        <i class="cil-magnifying-glass"></i>
                        <input type="search" class="form-control" id="rptSearch" placeholder="{{ __('admin/reports.search') }}">
                    </div>
                </div>
            </div>
        </div>

        <div id="rptList">
            @forelse ($reports as $r)
                {{-- teks yang dicari: nama report, perusahaan & jenis bisnisnya, pengunggah, nama file --}}
                @php $searchText = mb_strtolower($r->name . ' ' . ($groups[$r->group_cd] ?? $r->group_cd) . ' ' . ($r->group_cd !== 'consolidated' ? __('admin/financials.businesses.' . $r->group_cd) : '') . ' ' . $r->uploaded_by . ' ' . $r->files->pluck('file_name')->implode(' ')); @endphp
                <div class="rpt-item" data-search="{{ $searchText }}">
                    <div>
                        <span class="rpt-title">{{ $r->name }}</span><span class="rpt-ver">v{{ $r->current_version }}</span>
                    </div>
                    <div class="rpt-meta">
                        {{ __('admin/reports.meta', [
                            'group' => $groups[$r->group_cd] ?? $r->group_cd,
                            'date'  => \Illuminate\Support\Carbon::parse($r->uploaded_at)->locale(app()->getLocale())->translatedFormat('d M Y H:i'),
                            'user'  => $r->uploaded_by ?: '-',
                        ]) }}
                    </div>
                    @foreach ($r->files as $f)
                        @php [$ico, $cls] = $icon($f->file_name); $canInline = in_array(strtolower(pathinfo($f->file_name, PATHINFO_EXTENSION)), $inline, true); @endphp
                        <a class="rpt-file" href="{{ url('admin/reports/file/' . $f->id) }}{{ $canInline ? '?inline=1' : '' }}" @if ($canInline) target="_blank" rel="noopener" @endif>
                            <span><i class="{{ $ico }} {{ $cls }} me-2"></i><span class="rpt-fname">{{ $f->file_name }}</span></span>
                            <span class="rpt-fsize">{{ RD::size($f->size) }}</span>
                        </a>
                    @endforeach
                    <div class="rpt-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-rpt-history" data-id="{{ $r->id }}"><i class="cil-history"></i> {{ __('admin/reports.view') }}</button>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ url('admin/reports/' . $r->id . '/download') }}"><i class="cil-cloud-download"></i> {{ __('admin/reports.download') }}</a>
                        <button type="button" class="btn btn-sm btn-link text-decoration-none btn-rpt-version"
                                data-id="{{ $r->id }}" data-name="{{ $r->name }}" data-version="{{ $r->current_version }}">
                            <i class="cil-cloud-upload"></i> {{ __('admin/reports.new_version') }}
                        </button>
                        <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none ms-auto btn-rpt-delete"
                                data-id="{{ $r->id }}" data-name="{{ $r->name }}" data-version="{{ $r->current_version }}">
                            <i class="cil-trash"></i> {{ __('admin/reports.delete') }}
                        </button>
                    </div>
                </div>
            @empty
                <div class="rpt-item text-center text-soft py-5"><i class="cil-folder-open d-block mb-2" style="font-size: 1.6rem;"></i>{{ __('admin/reports.empty') }}</div>
            @endforelse
            <div class="rpt-item text-center text-soft py-4 d-none" id="rptNoMatch">{{ __('admin/reports.no_match') }}</div>
        </div>
    </div>
</div>

{{-- Upload report baru --}}
<div class="modal fade" tabindex="-1" id="rptUploadModal">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="rptUploadForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title">{{ __('admin/reports.upload') }}</h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label" for="rptGroup">{{ __('admin/reports.group') }} <span class="text-danger">*</span></label>
                        {{-- tanpa Consolidated: report di-upload ke satu perusahaan --}}
                        <select id="rptGroup" name="group" class="form-select js-select">
                            @foreach ($uploadGroups as $key => $label)
                                <option value="{{ $key }}" data-sub="{{ __('admin/financials.businesses.' . $key) }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label" for="rptName">{{ __('admin/reports.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="rptName" name="name" maxlength="{{ RD::MAX_NAME }}" placeholder="{{ __('admin/reports.name_ph') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="rptFiles">{{ __('admin/reports.documents') }} <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="rptFiles" multiple accept="{{ $accept }}">
                        <div class="form-note">{{ $fileHint }}</div>
                        <ul class="rpt-pick" id="rptUploadPick"></ul>
                        <div class="rpt-summary mt-1" id="rptUploadTotal"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">{{ __('common.back') }}</button>
                <button type="submit" class="btn btn-primary" id="rptUploadSave"><i class="cil-cloud-upload"></i><span>{{ __('admin/reports.save') }}</span></button>
            </div>
        </form>
    </div>
</div>

{{-- Versi baru --}}
<div class="modal fade" tabindex="-1" id="rptVersionModal">
    <div class="modal-dialog modal-lg">
        <form class="modal-content" id="rptVersionForm" novalidate>
            <div class="modal-header">
                <h5 class="modal-title" id="rptVersionTitle"></h5>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small" id="rptVersionHint"></div>
                <label class="form-label" for="rptVersionFiles">{{ __('admin/reports.documents') }} <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="rptVersionFiles" multiple accept="{{ $accept }}">
                <div class="form-note">{{ $fileHint }}</div>
                <ul class="rpt-pick" id="rptVersionPick"></ul>
                <div class="rpt-summary mt-1" id="rptVersionNext"></div>
            </div>
            <div class="modal-footer bg-body-tertiary">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">{{ __('common.back') }}</button>
                <button type="submit" class="btn btn-primary" id="rptVersionSave"><i class="cil-cloud-upload"></i><span>{{ __('admin/reports.save') }}</span></button>
            </div>
        </form>
    </div>
</div>

{{-- Riwayat versi (isi dari admin/reports/{id}/history) --}}
<div class="modal fade" tabindex="-1" id="rptHistoryModal">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" id="rptHistoryContent"></div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function () {
    var L = @json($L);
    var MAX_FILES = {{ $maxFiles }}, MAX_POST = {{ $maxPost }};
    var BASE = @json(url('admin/reports'));
    var VERSION_HINT = @json(__('admin/reports.version_hint', ['version' => '__V__', 'next' => '__N__']));
    var VERSION_TITLE = @json(__('admin/reports.version_title', ['name' => '__N__']));
    var modal = function (id) { return coreui.Modal.getOrCreateInstance(document.getElementById(id)); };

    function size(b) {
        if (b >= 1048576) { return (b / 1048576).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' MB'; }
        if (b >= 1024) { return Math.round(b / 1024).toLocaleString('id-ID') + ' KB'; }
        return b + ' B';
    }
    function esc(s) { return $('<div>').text(s).html(); }
    function filesOf(input) { return Array.prototype.slice.call(input.files || []); }
    // cek jumlah & total ukuran sebelum dikirim (batas PHP max_file_uploads & post_max_size)
    function checkLimits(files) {
        if (!files.length) { return L.noFiles; }
        if (files.length > MAX_FILES) { return L.tooMany; }
        var total = files.reduce(function (s, f) { return s + f.size; }, 0);
        if (total > MAX_POST * 0.95) { return L.tooBig.replace('__S__', size(total)); }
        return null;
    }
    function send(url, formData, $btn, onDone) {
        $btn.prop('disabled', true);
        $.ajax({ url: url, type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json' })
            .done(function (res) {
                Swal.fire({ title: L.info, text: res.pesan, icon: 'success' }).then(onDone);
            })
            .fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.pesan) || (xhr.status + ' ' + xhr.statusText);
                Swal.fire({ title: L.error, html: esc(msg).replace(/\n/g, '<br>'), icon: 'error' });
            })
            .always(function () { $btn.prop('disabled', false); });
    }

    // pencarian (semua report selalu tampil; disaring di browser)
    $('#rptSearch').on('input', function () {
        var q = this.value.trim().toLowerCase(), shown = 0;
        $('#rptList .rpt-item[data-search]').each(function () {
            var hit = !q || this.getAttribute('data-search').indexOf(q) !== -1;
            $(this).toggleClass('d-none', !hit);
            shown += hit ? 1 : 0;
        });
        $('#rptNoMatch').toggleClass('d-none', shown > 0 || !$('#rptList .rpt-item[data-search]').length);
    });

    // ---------- upload report baru ----------
    $('#btnUploadReport').on('click', function () {
        $('#rptUploadForm')[0].reset();
        // ikut filter Group kalau perusahaan; saat Consolidated pakai perusahaan pertama
        var $g = $('#rptGroup'), current = @json($group);
        $g.val($g.find('option[value="' + current + '"]').length ? current : $g.find('option:first').val()).trigger('change.select2');
        $('#rptUploadPick').empty(); $('#rptUploadTotal').text('');
        modal('rptUploadModal').show();
    });
    $('#rptFiles').on('change', function () {
        var files = filesOf(this), total = 0;
        $('#rptUploadPick').html(files.map(function (f) {
            total += f.size;
            return '<li><span class="text-break">' + esc(f.name) + '</span><span class="text-soft text-nowrap">' + size(f.size) + '</span></li>';
        }).join(''));
        $('#rptUploadTotal').text(files.length ? L.total.replace('__S__', size(total)) : '');
    });
    $('#rptUploadForm').on('submit', function (e) {
        e.preventDefault();
        var files = filesOf($('#rptFiles')[0]), err = checkLimits(files);
        if (err) { Swal.fire({ title: L.error, text: err, icon: 'error' }); return; }
        var fd = new FormData();
        fd.append('group', $('#rptGroup').val());
        fd.append('name', $('#rptName').val());
        files.forEach(function (f) { fd.append('files[]', f); });
        send(BASE, fd, $('#rptUploadSave'), function () { window.location.reload(); });
    });

    // ---------- versi baru ----------
    var current = null;
    $(document).on('click', '.btn-rpt-version', function () {
        var $b = $(this);
        current = { id: $b.data('id'), version: parseInt($b.data('version'), 10) };
        $('#rptVersionTitle').text(VERSION_TITLE.replace('__N__', $b.data('name')));
        $('#rptVersionHint').text(VERSION_HINT.replace('__V__', current.version).replace('__N__', current.version + 1));
        $('#rptVersionForm')[0].reset();
        renderVersionPreview([]);
        modal('rptVersionModal').show();
    });
    // pratinjau: versi baru hanya berisi file yang dipilih sekarang
    function renderVersionPreview(files) {
        $('#rptVersionPick').html(files.map(function (f) {
            return '<li><span class="text-break">' + esc(f.name) + '</span><span class="text-soft text-nowrap">' + size(f.size) + '</span></li>';
        }).join(''));
        $('#rptVersionNext').text(files.length ? L.next.replace('__V__', current.version + 1).replace('__C__', files.length) : '');
    }
    $('#rptVersionFiles').on('change', function () { renderVersionPreview(filesOf(this)); });
    $('#rptVersionForm').on('submit', function (e) {
        e.preventDefault();
        var files = filesOf($('#rptVersionFiles')[0]), err = checkLimits(files);
        if (err) { Swal.fire({ title: L.error, text: err, icon: 'error' }); return; }
        var fd = new FormData();
        files.forEach(function (f) { fd.append('files[]', f); });
        send(BASE + '/' + current.id + '/version', fd, $('#rptVersionSave'), function () { window.location.reload(); });
    });

    // ---------- hapus report (semua versi & file) ----------
    $(document).on('click', '.btn-rpt-delete', function () {
        var $b = $(this);
        Swal.fire({
            title: L.delTitle,
            text: L.delText.replace('__N__', $b.data('name')).replace('__V__', $b.data('version')),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: L.yes,
            cancelButtonText: L.cancel,
            confirmButtonColor: '#d0473b',
            focusCancel: true
        }).then(function (a) {
            if (!a.value) { return; }
            $b.prop('disabled', true);
            $.ajax({ url: BASE + '/' + $b.data('id') + '/delete', type: 'POST', dataType: 'json' })
                .done(function (res) {
                    Swal.fire({ title: L.info, text: res.pesan, icon: 'success' }).then(function () { window.location.reload(); });
                })
                .fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.pesan) || (xhr.status + ' ' + xhr.statusText);
                    Swal.fire({ title: L.error, text: msg, icon: 'error' });
                    $b.prop('disabled', false);
                });
        });
    });

    // ---------- riwayat versi ----------
    $(document).on('click', '.btn-rpt-history', function () {
        $('#rptHistoryContent').html('<div class="modal-body text-center py-5"><span class="spinner-border spinner-border-sm"></span></div>');
        modal('rptHistoryModal').show();
        $('#rptHistoryContent').load(BASE + '/' + $(this).data('id') + '/history');
    });
});
</script>
@endpush
