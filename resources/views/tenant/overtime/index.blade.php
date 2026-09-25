@extends('tenant.template.base')
@section('title', __('tenant/overtime.title'))

@php
    $defaults = \App\Support\OvertimeHours::DEFAULTS;
@endphp

@section('content')
<div class="page-body">
    <div class="page-head">
        <div class="page-head-row">
            <div class="page-head-content">
                <h3 class="page-title">{{ __('tenant/overtime.title') }}</h3>
                <div class="page-desc">{{ __('tenant/overtime.page_desc') }}</div>
            </div>
            <div class="page-head-content">
                <a href="{{ url('/tenant/history/overtime') }}" class="btn btn-outline-secondary"><i class="cil-history"></i><span>{{ __('tenant/overtime.history') }}</span></a>
            </div>
        </div>
    </div>

    <div class="page-block">
        <div class="card">
            <div class="card-body">
                <div class="alert alert-warning d-flex align-items-center gap-2 d-none" id="cutoffInfo">
                    <i class="cil-clock"></i><div>{{ __('tenant/overtime.cutoff', ['time' => \App\Support\OvertimeHours::SAME_DAY_CUTOFF]) }}</div>
                </div>

                <form id="frmOvertime" method="post" action="" novalidate autocomplete="off">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="id_tenancy">{{ __('common.tenant') }} <span class="text-danger">*</span></label>
                            <select name="id_tenancy" id="id_tenancy" class="form-control select2" data-placeholder="{{ __('tenant/overtime.ph_tenant') }}">
                                <option value=""></option>
                                @foreach ($tenancies as $t)
                                    <option value="{{ $t->id }}" @if ($tenancies->count() === 1) selected @endif>{{ $t->tenant_no }}{{ $t->entity_desc ? ' - ' . $t->entity_desc : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="lot_no">{{ __('common.unit') }} <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2 align-items-start">
                                <div class="flex-grow-1" style="min-width: 0">
                                    <select name="lot_no[]" id="lot_no" class="form-control select2" multiple data-placeholder="{{ __('tenant/overtime.ph_unit') }}"></select>
                                </div>
                                <button type="button" class="btn btn-outline-secondary text-nowrap" id="btnLayout"><i class="cil-map"></i><span>{{ __('tenant/overtime.view_layout') }}</span></button>
                            </div>
                            <div class="form-note">{{ __('tenant/overtime.unit_note') }}</div>
                        </div>

                        <div class="col-sm-4">
                            <label class="form-label" for="overtime_date">{{ __('tenant/overtime.date') }} <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <div class="form-icon form-icon-left"><i class="cil-calendar"></i></div>
                                <input type="text" id="overtime_date" name="overtime_date" class="form-control" data-date-format="dd/mm/yyyy" value="{{ date('d/m/Y') }}" placeholder="{{ __('common.select_date') }}" readonly>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <label class="form-label" for="start">{{ __('common.start_time') }} <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <div class="form-icon form-icon-left"><i class="cil-clock"></i></div>
                                <select id="start" name="start" class="form-select ps-5"></select>
                            </div>
                        </div>
                        <div class="col-6 col-sm-4">
                            <label class="form-label" for="end">{{ __('common.end_time') }} <span class="text-danger">*</span></label>
                            <div class="form-control-wrap">
                                <div class="form-icon form-icon-left"><i class="cil-clock"></i></div>
                                <select id="end" name="end" class="form-select ps-5"></select>
                            </div>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="form-note mt-0" id="hoursInfo"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="description">{{ __('common.description') }} <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="255" placeholder="{{ __('tenant/overtime.ph_description') }}"></textarea>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <div class="fw-semibold mb-1"><i class="cil-info"></i> {{ __('tenant/overtime.disclaimer') }}</div>
                                <div>{{ __('tenant/overtime.overtime_hours') }}</div>
                                <ul class="mb-1">
                                    <li>{{ __('tenant/overtime.hours_weekday', ['start' => $defaults['D'][1], 'end' => $defaults['D'][0]]) }}</li>
                                    <li>{{ __('tenant/overtime.hours_saturday', ['start' => $defaults['E'][1], 'end' => $defaults['E'][0]]) }}</li>
                                    <li>{{ __('tenant/overtime.hours_holiday') }}</li>
                                </ul>
                                <div>{{ __('tenant/overtime.charge_note') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="submit" id="btnSave" class="btn btn-primary"><i class="cil-send"></i><span>{{ __('common.submit') }}</span></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
$(function () {
    var URL_LOTS = "{{ url('tenant/overtime/lots') }}";
    var URL_HOURS = "{{ url('tenant/overtime/hours') }}";
    var L = {
        workHours: @json(__('tenant/overtime.work_hours_info')),
        holiday: @json(__('tenant/overtime.holiday_info')),
        minDuration: @json(__('tenant/overtime.min_duration'))
    };
    var pad = function (h) { return (h < 10 ? '0' : '') + h + ':00'; };
    var closedToday = false;

    $('.select2').select2({ width: '100%' });

    // ---------------- tanggal: mulai hari ini, Minggu tidak bisa dipilih ----------------
    $('#overtime_date').datepicker({
        format: 'dd/mm/yyyy',
        startDate: new Date(),
        daysOfWeekDisabled: '0',
        autoclose: true
    }).on('changeDate', loadHours);

    // ---------------- jam: mulai setelah jam kerja, selesai maks. 24:00 ----------------
    function fillEnd() {
        var startHour = parseInt(($('#start').val() || '0').substr(0, 2), 10);
        var current = $('#end').val();
        var $end = $('#end').empty();
        for (var h = startHour + 1; h <= 24; h++) {
            $end.append(new Option(pad(h), pad(h)));
        }
        $end.val($end.find('option[value="' + current + '"]').length ? current : pad(startHour + 1));
    }

    function loadHours() {
        var date = $('#overtime_date').val();
        $.getJSON(URL_HOURS, { date: date, id_tenancy: $('#id_tenancy').val() }, function (res) {
            if (res.status !== 'OK') { return; }
            var $start = $('#start').empty();
            for (var h = res.first_hour; h <= 23; h++) {
                $start.append(new Option(pad(h), pad(h)));
            }
            fillEnd();
            $('#hoursInfo').html(res.holiday
                ? '<i class="cil-calendar"></i> ' + $('<div>').text(L.holiday).html()
                : '<i class="cil-info"></i> ' + $('<div>').text(L.workHours.replace(':begin', res.work.begin).replace(':end', res.work.end)).html());

            // lembur untuk hari ini lewat batas jam -> tidak bisa diajukan
            closedToday = !!res.closed;
            $('#cutoffInfo').toggleClass('d-none', !closedToday);
            $('#btnSave').prop('disabled', closedToday);
        });
    }
    $('#start').on('change', fillEnd);

    // ---------------- unit sesuai tenant ----------------
    $('#id_tenancy').on('change', function () {
        var id = $(this).val();
        $('#lot_no').empty().trigger('change');
        if (id) {
            $.get(URL_LOTS + '/' + id, function (html) {
                $('#lot_no').html(html).trigger('change');
                if ($('#lot_no option').length === 1) {
                    $('#lot_no option').prop('selected', true).parent().trigger('change');
                }
            });
        }
        loadHours();
    }).trigger('change');

    // ---------------- denah lantai ----------------
    $('#btnLayout').on('click', function () {
        $('#modaltitlelg').text(@json(__('tenant/overtime.view_layout')));
        $('#modalbodylg').html('<p class="mb-0">' + @json(__('common.loading')) + '</p>');
        $('#modallg').modal('show');
        $.post("{{ url('tenant/overtime/layout') }}", { id_tenancy: $('#id_tenancy').val(), lot_no: $('#lot_no').val() || [] })
            .done(function (html) { $('#modalbodylg').html(html); })
            .fail(function () { $('#modalbodylg').html('<p class="text-danger mb-0">' + @json(__('tenant/overtime.layout_failed')) + '</p>'); });
    });

    // ---------------- validasi & simpan ----------------
    $.validator.addMethod('minDuration', function () {
        var s = parseInt(($('#start').val() || '').substr(0, 2), 10);
        var e = parseInt(($('#end').val() || '').substr(0, 2), 10);
        return isNaN(s) || isNaN(e) || e - s >= 1;
    }, L.minDuration);

    var validator = $('#frmOvertime').validate({
        ignore: [],
        rules: {
            id_tenancy: { required: true },
            'lot_no[]': { required: true },
            overtime_date: { required: true },
            start: { required: true },
            end: { required: true, minDuration: true },
            description: { required: true }
        },
        errorElement: 'div',
        errorClass: 'invalid-feedback d-block',
        highlight: function (el) { $(el).addClass('is-invalid'); },
        unhighlight: function (el) { $(el).removeClass('is-invalid'); },
        errorPlacement: function (error, element) {
            var $after = element.hasClass('select2-hidden-accessible') ? element.next('.select2') : element;
            if (element.attr('id') === 'lot_no') { $after = element.closest('.d-flex'); }
            if (element.closest('.form-control-wrap').length) { $after = element.closest('.form-control-wrap'); }
            error.insertAfter($after);
        }
    });
    $('.select2').on('change', function () { $(this).valid(); });

    $('#frmOvertime').on('submit', function (e) {
        e.preventDefault();
        if (closedToday || !$(this).valid()) { return; }

        Swal.fire({
            title: @json(__('common.are_you_sure')),
            text: @json(__('tenant/overtime.confirm_charge')),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: @json(__('tenant/overtime.continue')),
            cancelButtonText: @json(__('common.cancel'))
        }).then(function (a) {
            if (!a.value) { return; }
            $('#btnSave').prop('disabled', true);
            $.ajax({
                url: "{{ url('tenant/overtime/save') }}",
                type: 'POST',
                data: $('#frmOvertime').serialize(),
                dataType: 'json'
            }).done(function (res) {
                Swal.fire({ title: @json(__('common.information')), icon: res.status === 'OK' ? 'success' : 'error', text: res.pesan })
                    .then(function () {
                        if (res.status === 'OK') { window.location.href = "{{ url('/tenant/history/overtime') }}"; }
                    });
            }).fail(function (xhr, textStatus, errorThrown) {
                Swal.fire({ title: @json(__('common.error')), icon: 'error', text: textStatus + ' : ' + errorThrown });
            }).always(function () {
                $('#btnSave').prop('disabled', closedToday);
            });
        });
    });
});
</script>
@endpush
