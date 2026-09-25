@extends('admin.template.layout2.base')
@section('title', __('admin/overtime.layout_title'))

@section('content')
<div class="page-body">
    <div class="page-block">
        <div class="page-head">
            <div class="page-head-row">
                <div class="page-head-content">
                    <h3 class="page-title">{{ __('admin/overtime.layout_title') }}</h3>
                    <div class="page-desc">{{ __('admin/overtime.layout_desc') }}</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="entity">{{ __('admin/overtime.entity') }} <span class="text-danger">*</span></label>
                        <select id="entity" class="form-control select2" data-placeholder="{{ __('admin/overtime.ph_entity') }}">
                            <option value=""></option>
                            @foreach ($entities as $e)
                                <option value="{{ trim($e->entity_cd) }}">{{ trim($e->entity_cd) }} - {{ $e->entity_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="project">{{ __('admin/overtime.project') }} <span class="text-danger">*</span></label>
                        <select id="project" class="form-control select2" data-placeholder="{{ __('admin/overtime.ph_project') }}">
                            <option value=""></option>
                            @foreach ($projects as $p)
                                <option value="{{ trim($p->project_no) }}" data-entity="{{ trim($p->entity_cd) }}">{{ trim($p->project_no) }} - {{ $p->descs }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <hr>

                <h6 class="fw-semibold mb-1">{{ __('admin/overtime.layout_batch') }}</h6>
                <div class="form-note mb-2">{{ __('admin/overtime.layout_batch_hint', ['ext' => strtoupper(implode(', ', $extensions)), 'size' => $maxSize]) }}</div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="file" id="batchFiles" class="form-control" style="max-width: 420px" multiple
                           accept="{{ implode(',', array_map(fn ($e) => '.' . $e, $extensions)) }}">
                    <button type="button" class="btn btn-primary" id="btnUpload"><i class="cil-cloud-upload"></i><span>{{ __('common.upload') }}</span></button>
                </div>
                <ul class="list-unstyled small mt-2 mb-0" id="uploadResult"></ul>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered w-100" id="tblLayout">
                        <thead>
                            <tr>
                                <th>{{ __('common.col_no') }}</th>
                                <th>{{ __('admin/overtime.layout_level') }}</th>
                                <th>{{ __('admin/overtime.layout_zone') }}</th>
                                <th>{{ __('common.remarks') }}</th>
                                <th>{{ __('admin/overtime.layout_picture') }}</th>
                                <th>{{ __('admin/overtime.layout_image') }}</th>
                                <th class="no-export">{{ __('common.action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- file untuk tombol Ganti per baris --}}
<input type="file" id="rowFile" class="d-none" accept="{{ implode(',', array_map(fn ($e) => '.' . $e, $extensions)) }}">

<script type="text/javascript">
    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

    $(function () {
        $('.select2').select2({ width: '100%' });

        // project mengikuti entity yang dipilih
        var $allProjects = $('#project option').clone();
        $('#entity').on('change', function () {
            var entity = $(this).val();
            var $p = $('#project').empty().append($allProjects.filter(function () {
                return !this.value || !entity || $(this).data('entity') == entity;
            }).clone());
            if ($p.find('option[value!=""]').length === 1) { $p.val($p.find('option[value!=""]').val()); }
            $p.trigger('change');
        });

        var tbl = $('#tblLayout').DataTable({
            ordering: false,
            ajax: {
                url: "{{ url('/admin/overtime/layout/data') }}",
                type: 'POST',
                data: function (d) {
                    d.entity = $('#entity').val();
                    d.project = $('#project').val();
                }
            },
            columns: [
                { data: 'row_number' },
                { data: 'level_no', render: esc },
                { data: 'zone_cd', render: esc },
                { data: 'remarks', render: esc },
                { data: 'picture', render: esc },
                {
                    data: 'image_url',
                    render: function (url) {
                        return url
                            ? '<a href="' + esc(url) + '" target="_blank"><img src="' + esc(url) + '" alt="" style="max-height:80px;max-width:160px" class="border rounded"></a>'
                            : '<span class="text-body-secondary">' + esc(@json(__('admin/overtime.layout_missing'))) + '</span>';
                    }
                },
                {
                    data: 'picture', className: 'text-nowrap',
                    render: function (picture, t, row) {
                        var html = '<button type="button" class="btn btn-sm btn-primary btn-replace" data-picture="' + esc(picture) + '"><i class="cil-cloud-upload"></i><span>' + esc(row.image_url ? @json(__('admin/overtime.layout_replace')) : @json(__('common.upload'))) + '</span></button>';
                        if (row.image_url) {
                            html += ' <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-picture="' + esc(picture) + '"><i class="cil-trash"></i><span>' + esc(@json(__('common.delete'))) + '</span></button>';
                        }
                        return html;
                    }
                }
            ]
        });
        $('#entity, #project').on('change', function () { $('#uploadResult').empty(); tbl.ajax.reload(); });

        function hasProject() {
            if ($('#entity').val() && $('#project').val()) { return true; }
            Swal.fire(@json(__('common.information')), @json(__('admin/overtime.layout_choose_ep')), 'warning');
            return false;
        }

        // kirim file ke server; picture terisi = tombol Ganti per baris
        function upload(files, picture, $btn) {
            var fd = new FormData();
            fd.append('entity', $('#entity').val());
            fd.append('project', $('#project').val());
            if (picture) { fd.append('picture', picture); }
            $.each(files, function (i, f) { fd.append('files[]', f); });

            $btn.prop('disabled', true);
            $.ajax({ url: "{{ url('/admin/overtime/layout/upload') }}", type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
                .done(function (res) {
                    var $r = $('#uploadResult').empty();
                    $.each(res.results || [], function (i, r) {
                        $r.append('<li class="' + (r.ok ? 'text-success' : 'text-danger') + '"><i class="' + (r.ok ? 'cil-check' : 'cil-x') + '"></i> ' + esc(r.file) + ' &mdash; ' + esc(r.message) + '</li>');
                    });
                    Swal.fire(@json(__('common.information')), res.pesan, res.status === 'OK' ? 'success' : 'error');
                    $('#batchFiles, #rowFile').val('');
                    tbl.ajax.reload(null, false);
                })
                .fail(function (xhr, textStatus, errorThrown) {
                    Swal.fire(@json(__('common.error')), textStatus + ' : ' + errorThrown, 'error');
                })
                .always(function () { $btn.prop('disabled', false); });
        }

        $('#btnUpload').on('click', function () {
            if (!hasProject()) { return; }
            var files = $('#batchFiles')[0].files;
            if (!files.length) {
                Swal.fire(@json(__('common.information')), @json(__('admin/overtime.layout_no_files')), 'warning');
                return;
            }
            upload(files, null, $(this));
        });

        var rowPicture = null;
        $(document).on('click', '.btn-replace', function () {
            rowPicture = $(this).data('picture');
            $('#rowFile').val('').trigger('click');
        });
        $('#rowFile').on('change', function () {
            if (this.files.length && rowPicture) { upload(this.files, rowPicture, $('#btnUpload')); }
        });

        $(document).on('click', '.btn-delete', function () {
            var picture = $(this).data('picture');
            Swal.fire({
                title: @json(__('admin/overtime.layout_delete_title')),
                text: @json(__('admin/overtime.layout_delete_confirm')).replace(':picture', picture),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: @json(__('common.yes')),
                cancelButtonText: @json(__('common.no'))
            }).then(function (a) {
                if (!a.value) { return; }
                $.post("{{ url('/admin/overtime/layout/delete') }}", { entity: $('#entity').val(), project: $('#project').val(), picture: picture }, null, 'json')
                    .done(function (res) {
                        Swal.fire(@json(__('common.information')), res.pesan, res.status === 'OK' ? 'success' : 'error');
                        tbl.ajax.reload(null, false);
                    });
            });
        });
    });
</script>
@endsection
