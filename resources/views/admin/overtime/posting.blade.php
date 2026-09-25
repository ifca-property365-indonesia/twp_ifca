@extends('admin.template.layout2.base')
@section('title', __('admin/overtime.posting_title'))

@section('content')
<div class="page-body">
    <div class="page-block">
        <div class="page-head">
            <div class="page-head-row">
                <div class="page-head-content">
                    <h3 class="page-title">{{ __('admin/overtime.posting_title') }}</h3>
                    <div class="page-desc">{{ __('admin/overtime.posting_desc') }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form id="frmPosting" novalidate autocomplete="off">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label" for="entity">{{ __('admin/overtime.entity') }} <span class="text-danger">*</span></label>
                            <select name="entity" id="entity" class="form-control select2" data-placeholder="{{ __('admin/overtime.ph_entity') }}">
                                <option value=""></option>
                                @foreach ($entities as $e)
                                    <option value="{{ trim($e->entity_cd) }}">{{ trim($e->entity_cd) }} - {{ $e->entity_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="project">{{ __('admin/overtime.project') }} <span class="text-danger">*</span></label>
                            <select name="project" id="project" class="form-control select2" data-placeholder="{{ __('admin/overtime.ph_project') }}">
                                <option value=""></option>
                                @foreach ($projects as $p)
                                    <option value="{{ trim($p->project_no) }}" data-entity="{{ trim($p->entity_cd) }}">{{ trim($p->project_no) }} - {{ $p->descs }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="post_date">{{ __('admin/overtime.post_date') }} <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <div class="form-icon form-icon-left"><i class="cil-calendar"></i></div>
                                <input type="text" id="post_date" name="post_date" class="form-control date-picker" data-date-format="dd/mm/yyyy" value="{{ date('d/m/Y') }}" placeholder="{{ __('common.select_date') }}">
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label" for="start">{{ __('admin/overtime.period_from') }} <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <div class="form-icon form-icon-left"><i class="cil-calendar"></i></div>
                                <input type="text" id="start" name="start" class="form-control date-picker" data-date-format="dd/mm/yyyy" placeholder="{{ __('common.select_date') }}">
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label" for="end">{{ __('admin/overtime.period_to') }} <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <div class="form-icon form-icon-left"><i class="cil-calendar"></i></div>
                                <input type="text" id="end" name="end" class="form-control date-picker" data-date-format="dd/mm/yyyy" placeholder="{{ __('common.select_date') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="remarks">{{ __('common.remarks') }} <span class="text-danger">*</span></label>
                            <input type="text" id="remarks" name="remarks" class="form-control" maxlength="100">
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered w-100" id="tblPosting">
                        <thead>
                            <tr>
                                <th>{{ __('common.col_no') }}</th>
                                <th>{{ __('admin/overtime.business_id') }}</th>
                                <th>{{ __('admin/overtime.debtor_name') }}</th>
                                <th class="no-export">{{ __('common.action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // dd/mm/yyyy -> yyyy-mm-dd (kosong kalau tidak lengkap)
    function toYmd(v) {
        var p = (v || '').split('/');
        return p.length === 3 ? p[2] + '-' + p[1] + '-' + p[0] : '';
    }
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

        // tanggal akhir periode tidak boleh sebelum tanggal awal
        $('#start').on('change', function () {
            var s = $(this).val() ? $(this).datepicker('getDate') : null;
            $('#end').datepicker('setStartDate', s || false);
            if (s && $('#end').val() && $('#end').datepicker('getDate') < s) { $('#end').datepicker('update', s); }
        });

        // error dari server ditampilkan lewat dataSrc di bawah, bukan alert bawaan DataTables
        $.fn.dataTable.ext.errMode = 'none';
        var tbl = $('#tblPosting').DataTable({
            processing: true,
            serverSide: true,
            ordering: false,
            ajax: {
                url: "{{ url('/admin/overtime/posting/data') }}",
                type: 'POST',
                data: function (d) {
                    d.entity = $('#entity').val();
                    d.project = $('#project').val();
                    d.date_start = toYmd($('#start').val());
                    d.date_end = toYmd($('#end').val());
                },
                // pesan dari server (mis. tabel billing IFCA belum tersedia) sebagai SweetAlert
                dataSrc: function (json) {
                    if (json.error) { Swal.fire(@json(__('common.information')), json.error, 'warning'); }
                    return json.data || [];
                }
            },
            columns: [
                { data: 'row_number' },
                { data: 'business_id', render: esc },
                { data: 'debtor_name', render: esc },
                {
                    data: 'bill_debtor_acct', className: 'text-nowrap',
                    render: function (d, t, row) {
                        return '<button type="button" class="btn btn-sm btn-primary btn-post" data-debtor="' + esc(d) + '" data-name="' + esc(row.debtor_name) + '" data-business="' + esc(row.business_id) + '"><i class="cil-send"></i><span>' + esc(@json(__('admin/overtime.post'))) + '</span></button>';
                    }
                }
            ]
        });
        $('#entity, #project, #start, #end').on('change', function () { tbl.ajax.reload(); });

        $(document).on('click', '.btn-post', function () {
            var $b = $(this);
            var required = [
                ['#entity', @json(__('admin/overtime.ph_entity'))],
                ['#project', @json(__('admin/overtime.ph_project'))],
                ['#post_date', @json(__('admin/overtime.post_date'))],
                ['#start', @json(__('admin/overtime.period_from'))],
                ['#end', @json(__('admin/overtime.period_to'))],
                ['#remarks', @json(__('common.remarks'))]
            ];
            for (var i = 0; i < required.length; i++) {
                if (!$.trim($(required[i][0]).val())) {
                    Swal.fire(@json(__('common.information')), @json(__('admin/overtime.please_fill')).replace(':field', required[i][1]), 'warning');
                    return;
                }
            }
            Swal.fire({
                title: @json(__('admin/overtime.post_title')),
                text: @json(__('admin/overtime.post_confirm')).replace(':name', $b.data('name')).replace(':id', $b.data('business')),
                icon: 'warning',
                showCancelButton: true,
                allowOutsideClick: false,
                confirmButtonText: @json(__('common.yes')),
                cancelButtonText: @json(__('common.no'))
            }).then(function (a) {
                if (!a.value) { return; }
                $b.prop('disabled', true);
                var data = $('#frmPosting').serializeArray();
                data.push({ name: 'bill_debtor_acct', value: $b.data('debtor') });
                $.ajax({ url: "{{ url('/admin/overtime/posting/save') }}", type: 'POST', data: data, dataType: 'json' })
                    .done(function (res) {
                        Swal.fire(@json(__('common.information')), res.pesan, res.status === 'OK' ? 'success' : 'error');
                        if (res.status === 'OK') { $('#remarks').val(''); tbl.ajax.reload(); }
                    })
                    .fail(function (xhr, textStatus, errorThrown) {
                        Swal.fire(@json(__('common.error')), textStatus + ' : ' + errorThrown, 'error');
                    })
                    .always(function () { $b.prop('disabled', false); });
            });
        });
    });
</script>
@endsection
