@extends('admin.template.layout2.base')
@section('title', __('admin/overtime.approval_title'))

@section('content')
<div class="page-body">
    <div class="page-block">
        <div class="page-head">
            <div class="page-head-row">
                <div class="page-head-content">
                    <h3 class="page-title">{{ __('admin/overtime.approval_title') }}</h3>
                    <div class="page-desc">{{ __('admin/overtime.approval_desc') }}</div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-underline-border mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-coreui-toggle="tab" href="#tabNew" role="tab"><i class="cil-clock"></i> {{ __('admin/overtime.tab_new') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-coreui-toggle="tab" href="#tabApproved" role="tab"><i class="cil-check-circle"></i> {{ __('admin/overtime.tab_approved') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-coreui-toggle="tab" href="#tabCancelled" role="tab"><i class="cil-x-circle"></i> {{ __('admin/overtime.tab_cancelled') }}</a>
                    </li>
                </ul>

                <div class="tab-content">
                    @foreach (['new' => 'tabNew', 'approved' => 'tabApproved', 'cancelled' => 'tabCancelled'] as $tab => $pane)
                        <div class="tab-pane {{ $loop->first ? 'active' : '' }}" id="{{ $pane }}" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered w-100 ot-table" data-tab="{{ $tab }}">
                                    <thead>
                                        <tr>
                                            <th>{{ __('common.col_no') }}</th>
                                            <th>{{ __('common.tenant') }}</th>
                                            <th>{{ __('common.unit') }}</th>
                                            <th>{{ __('admin/overtime.start_overtime') }}</th>
                                            <th>{{ __('admin/overtime.end_overtime') }}</th>
                                            <th>{{ __('common.description') }}</th>
                                            <th>{{ __('common.status') }}</th>
                                            @if ($tab === 'new')<th class="no-export">{{ __('common.action') }}</th>@endif
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    var OT_STATUS = @json(__('admin/overtime.statuses'));
    var OT_TONE = { N: 'info', A: 'success', X: 'warning', Z: 'secondary' };
    var otTables = {};

    function otStatus(data) {
        var code = (data || '').trim();
        return '<span class="badge badge-soft-' + (OT_TONE[code] || 'secondary') + '">' + $('<div>').text(OT_STATUS[code] || code || '-').html() + '</span>';
    }
    function esc(v) { return $('<div>').text(v == null ? '' : v).html(); }

    function reloadAll() {
        $.each(otTables, function (_, t) { t.ajax.reload(null, false); });
    }

    function otAction(url, id, title, text) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: @json(__('common.yes')),
            cancelButtonText: @json(__('common.no'))
        }).then(function (a) {
            if (!a.value) { return; }
            $.ajax({ url: url, type: 'POST', data: { id: id }, dataType: 'json' })
                .done(function (res) {
                    Swal.fire(@json(__('common.information')), res.pesan, res.status === 'OK' ? 'success' : 'error');
                    reloadAll();
                })
                .fail(function (xhr, textStatus, errorThrown) {
                    Swal.fire(@json(__('common.error')), textStatus + ' : ' + errorThrown, 'error');
                });
        });
    }

    $(function () {
        $('.ot-table').each(function () {
            var tab = $(this).data('tab');
            var columns = [
                { data: 'row_number', searchable: false },
                { data: 'tenant_no', render: function (d, t, row) { return esc(d) + (row.entity_desc ? '<div class="form-note mt-0">' + esc(row.entity_desc) + '</div>' : ''); } },
                { data: 'lot_no', render: esc },
                { data: 'start_overtime', render: function (d) { return FormatDateTimeNew(d); } },
                { data: 'end_overtime', render: function (d) { return FormatDateTimeNew(d); } },
                { data: 'description', render: esc },
                { data: 'status', render: otStatus }
            ];
            if (tab === 'new') {
                columns.push({
                    data: 'id', orderable: false, searchable: false, className: 'text-nowrap',
                    render: function (id) {
                        return '<button type="button" class="btn btn-sm btn-primary btn-approve" data-id="' + id + '"><i class="cil-check"></i><span>' + esc(@json(__('admin/overtime.approve'))) + '</span></button> '
                            + '<button type="button" class="btn btn-sm btn-outline-danger btn-cancel" data-id="' + id + '"><i class="cil-x"></i><span>' + esc(@json(__('common.cancel'))) + '</span></button>';
                    }
                });
            }
            otTables[tab] = $(this).DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: "{{ url('/admin/overtime/data') }}/" + tab, type: 'POST' },
                columns: columns,
                order: [],
                ordering: false
            });
        });

        // tabel di tab tersembunyi baru diukur ulang saat tab dibuka
        $('a[data-coreui-toggle="tab"]').on('shown.coreui.tab', function () {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        $(document).on('click', '.btn-approve', function () {
            otAction("{{ url('/admin/overtime/approve') }}", $(this).data('id'), @json(__('admin/overtime.approve_title')), @json(__('admin/overtime.approve_confirm')));
        });
        $(document).on('click', '.btn-cancel', function () {
            otAction("{{ url('/admin/overtime/cancel') }}", $(this).data('id'), @json(__('admin/overtime.cancel_title')), @json(__('admin/overtime.cancel_confirm')));
        });
    });
</script>
@endsection
