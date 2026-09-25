@extends('tenant.template.base')

@section('title', __('tenant/dashboard.title'))

@push('styles')
<style>
    .news-carousel .carousel-item { cursor: pointer; }
    .news-carousel .news-slide { position: relative; height: 300px; background: #0b0f19; display: flex; justify-content: flex-end; align-items: center; padding-right: 60px; overflow: hidden; }
    .news-carousel .news-slide img { max-width: 100%; max-height: 300px; width: auto; height: auto; }
    .news-carousel .transbox { position: absolute; top: 0; bottom: 0; left: 0; width: 38%; background: rgba(0, 0, 0, .55); padding: 40px 30px 30px 50px; color: #fff; }
    .news-carousel .transbox h5 { color: #fff; font-weight: 700; }
    .news-carousel .transbox p { font-size: .85rem; }
    .news-carousel .read-more { float: right; text-decoration: underline; }
    .chart-box { position: relative; height: 320px; }
    .chart-tools .form-select, .chart-tools .select2-container { min-width: 150px; }
    @media (max-width: 767.98px) {
        .news-carousel .news-slide { padding-right: 0; justify-content: center; }
        .news-carousel .transbox { width: 100%; padding: 20px; }
        .chart-tools > * { width: 100% !important; }
    }
</style>
@endpush

@section('content')
    <div class="page-body">
        <div class="page-head">
            <div class="page-head-row">
                <div class="page-head-content">
                    <h3 class="page-title">{{ __('tenant/dashboard.title') }}</h3>
                </div>
            </div>
        </div>

        <div class="page-block">
            @if (!empty($dtnews))
                <div id="carouselExCap" class="carousel slide news-carousel mb-4 rounded overflow-hidden" data-coreui-ride="carousel">
                    <div class="carousel-inner">
                        @foreach ($dtnews as $i => $key)
                            @php
                                $string = strip_tags($key->content);
                                $cut = false;
                                if (strlen($string) > 150) {
                                    $stringCut = substr($string, 0, 150);
                                    $endPoint = strrpos($stringCut, ' ');
                                    $string = $endPoint ? substr($stringCut, 0, $endPoint) : $stringCut;
                                    $cut = true;
                                }
                            @endphp
                            <div class="carousel-item {{ $i === 0 ? 'active' : '' }}" onclick="window.location.href='{{ url('/tenant/news#news-' . $key->id) }}'">
                                <div class="news-slide">
                                    @if (!empty($key->picture))
                                        <img src="{{ \App\Support\NewsPicture::url($key->picture) }}" alt="" onerror="{{ \App\Support\NewsPicture::onError() }}">
                                    @endif
                                    <div class="transbox">
                                        <h5>{{ $key->subject }}</h5>
                                        <p>{{ $string }}@if ($cut)... <span class="read-more">{{ __('common.read_more') }}</span>@endif</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button class="carousel-control-prev" type="button" data-coreui-target="#carouselExCap" data-coreui-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">{{ __('common.previous') }}</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-coreui-target="#carouselExCap" data-coreui-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">{{ __('common.next') }}</span>
                    </button>
                </div>
            @endif

            @php $isOperational = session('Tflag') == 'O'; @endphp
            <div class="row g-3">
                <div class="{{ $isOperational ? 'col-12' : 'col-lg-8' }}">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="card-title-group">
                                <h6 class="title" id="utilityTitle">{{ __('tenant/dashboard.monthly_utility_usage') }}</h6>
                                <div class="card-tools chart-tools flex-wrap">
                                    <select class="form-select form-select-sm" name="yearcombo" id="yearcombo" style="width: 110px;">
                                        @for ($i = 0; $i < 5; $i++)
                                            @php $year = date('Y') - $i; @endphp
                                            <option value="{{ $year }}" {{ $i === 0 ? 'selected' : '' }}>{{ $year }}</option>
                                        @endfor
                                    </select>
                                    <select class="form-select form-select-sm" name="utilitycombo" id="utilitycombo" style="width: 150px;">
                                        <option value="">{{ __('tenant/dashboard.select_utility') }}</option>
                                        <option value="E">{{ __('tenant/dashboard.electric') }}</option>
                                        <option value="W">{{ __('tenant/dashboard.water') }}</option>
                                        <option value="G">{{ __('tenant/dashboard.gas') }}</option>
                                    </select>
                                    <select class="select2 form-control" name="meteridcombo" id="meteridcombo" style="width: 260px;" disabled>
                                        <option value="">{{ __('tenant/dashboard.select_meter_id') }}</option>
                                        {!! $combometerid !!}
                                    </select>
                                </div>
                            </div>
                            <ul class="nav nav-underline-border mb-3" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-coreui-toggle="tab" id="tab1" href="#tabItem1" role="tab">{{ __('tenant/dashboard.tab_area') }}</a></li>
                                <li class="nav-item"><a class="nav-link" data-coreui-toggle="tab" id="tab2" href="#tabItem2" role="tab">{{ __('tenant/dashboard.tab_bar') }}</a></li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane active" id="tabItem1" role="tabpanel">
                                    <div class="chart-box"><canvas id="areaChart"></canvas></div>
                                </div>
                                <div class="tab-pane" id="tabItem2" role="tabpanel">
                                    <div class="chart-box"><canvas id="barChart"></canvas></div>
                                </div>
                                <div class="mt-3" id="legendDiv"></div>
                            </div>
                        </div>
                    </div>
                </div>

                @unless ($isOperational)
                <div class="col-lg-4">
                    @if (!$statusPembayaran)
                        <div class="card border-danger mb-3">
                            <div class="card-body">
                                <h6 class="title fw-bold text-danger"><i class="cil-warning"></i> {{ __('tenant/dashboard.important_notification') }}</h6>
                                <a href="{{ url('/tenant/proforma') }}" class="badge text-bg-danger mt-2">{{ __('tenant/dashboard.you_have_proforma') }}</a>
                                <div class="mt-2 small text-body-secondary">{{ __('common.total') }}</div>
                                <div class="fw-bold fs-5">{!! $totalProforma !!}</div>
                            </div>
                        </div>
                    @else
                        <div class="card border-primary mb-3">
                            <div class="card-body">
                                <h6 class="title fw-bold">{{ __('tenant/dashboard.proforma_notification') }}</h6>
                                <span class="badge text-bg-primary mt-2">{{ __('tenant/dashboard.no_proforma') }}</span>
                            </div>
                        </div>
                    @endif

                    @if (!$statusInvoice)
                        <div class="card border-danger mb-3">
                            <div class="card-body">
                                <h6 class="title fw-bold text-danger"><i class="cil-warning"></i> {{ __('tenant/dashboard.invoice_notification') }}</h6>
                                <a href="{{ url('/tenant/invoice') }}" class="badge text-bg-danger mt-2">{{ __('tenant/dashboard.you_have_invoice') }}</a>
                                <div class="mt-2 small text-body-secondary">{{ __('common.total') }}</div>
                                <div class="fw-bold fs-5">{!! $totalInvoice !!}</div>
                            </div>
                        </div>
                    @else
                        <div class="card border-primary mb-3">
                            <div class="card-body">
                                <h6 class="title fw-bold">{{ __('tenant/dashboard.invoice_notification') }}</h6>
                                <span class="badge text-bg-primary mt-2">{{ __('tenant/dashboard.no_invoice') }}</span>
                            </div>
                        </div>
                    @endif
                </div>
                @endunless
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="card-title-group">
                        <h6 class="title">{{ __('tenant/dashboard.latest_ticket') }}</h6>
                        <a href="{{ url('/tenant/ticket') }}" class="btn btn-sm btn-primary"><i class="cil-plus"></i><span>{{ __('tenant/dashboard.new_ticket') }}</span></a>
                    </div>
                    <div class="table-responsive">
                        @if (!empty($list_hticket))
                            <table id="tblTicket" class="table table-bordered table-striped" role="grid" aria-describedby="tblTicket_info">
                                <thead class="table-dark">
                                    <tr role="row">
                                        <th class="text-center" style="width: 48px;">{{ __('common.col_no') }}</th>
                                        <th class="text-center">{{ __('tenant/dashboard.col_wo_number') }}</th>
                                        <th class="text-center">{{ __('common.category') }}</th>
                                        <th class="text-center">{{ __('common.description') }}</th>
                                        <th class="text-center" style="width: 110px;">{{ __('tenant/dashboard.col_reported_date') }}</th>
                                        <th class="text-center">{{ __('tenant/dashboard.col_request_by') }}</th>
                                        <th class="text-center" style="width: 80px;">{{ __('common.lot_no') }}</th>
                                        <th class="text-center">{{ __('tenant/dashboard.col_ticket_type') }}</th>
                                        <th class="text-center">{{ __('tenant/dashboard.col_ticket_status') }}</th>
                                        <th class="text-center no-export" style="width: 90px;">{{ __('common.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>{!! $list_hticket !!}</tbody>
                            </table>
                        @else
                            <div class="text-center py-5 text-body-secondary">
                                <i class="cil-tags fs-1 d-block mb-2"></i>
                                {{ __('tenant/dashboard.no_ticket') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script type="text/javascript">
    function genPDF() {
        var meteridcombo = $('#meteridcombo').val();
        var chart = ($('.nav-underline-border .active').attr('id') === 'tab1')
            ? document.getElementById('areaChart').toDataURL()
            : document.getElementById('barChart').toDataURL();

        $.post("{{ url('tenant/dash/gen') }}", { meteridcombo: meteridcombo, chart: chart }, function (data, status) {
            if (status == 'success') {
                window.open(data);
            } else {
                Swal.fire({ title: @json(__('common.information')), icon: 'error', text: @json(__('tenant/dashboard.pdf_failed')) });
            }
        });
    }

    function changeStatus(id) {
        Swal.fire({
            title: @json(__('tenant/dashboard.cancel_overtime_confirm')),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: @json(__('common.yes')),
            cancelButtonText: @json(__('common.no'))
        }).then(function (a) {
            if (!a.value) { return; }
            $.ajax({
                url: "{{ url('/tenant/dash/cancelOT') }}",
                type: 'POST',
                data: { id: id },
                dataType: 'json'
            }).done(function (res) {
                Swal.fire({ title: @json(__('common.information')), icon: res.status == 'OK' ? 'success' : 'error', text: res.pesan })
                    .then(function () { if (res.status == 'OK') { window.location.reload(); } });
            }).fail(function (xhr, textStatus, errorThrown) {
                Swal.fire({ title: @json(__('common.error')), icon: 'error', text: textStatus + ' : ' + errorThrown });
            });
        });
    }

    $(function () {
        var URL_GRAPH = "{{ url('tenant/dash/getGraphMeterId') }}";
        var URL_METER = "{{ url('tenant/dash/getMeterIdByUtility') }}";
        var charts = { area: null, bar: null };
        var MONTHS = @json(array_values(__('tenant/dashboard.months')));

        if ($('#tblTicket').length) {
            $('#tblTicket').DataTable({
                paging: false,
                dom: 'Bfrtip',
                buttons: [{
                    extend: 'pdf',
                    title: @json(__('tenant/dashboard.latest_ticket')),
                    className: 'btn btn-primary mb-2',
                    text: '<i class="cil-cloud-download"></i>&nbsp;' + @json(__('common.generate_pdf')),
                    init: function (api, node) { $(node).removeClass('dt-button'); }
                }]
            });
        }

        $('.select2').select2();

        function unitLabel() {
            var u = $('#utilitycombo').val();
            return u === 'W' ? ' m³' : (u === 'G' ? ' m³' : ' kWh');
        }

        function chartOptions(datas) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return (context.dataset.label ? context.dataset.label + ' : ' : @json(__('tenant/dashboard.usage')) + ' : ') +
                                    Number(context.raw).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + unitLabel();
                            },
                            afterLabel: function (context) {
                                return (datas.meterid && datas.meterid[context.dataIndex]) ? '(' + datas.meterid[context.dataIndex] + ')' : '';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return Number(value).toLocaleString('en-US', { maximumFractionDigits: 0 });
                            }
                        }
                    }
                }
            };
        }

        // Warna dataset mengikuti label tarif (WBP / LWBP)
        function colorize(chartdt) {
            (chartdt.datasets || []).forEach(function (ds, i) {
                var palette = ['#4f5bd5', '#bda870', '#2eb85c', '#e55353'];
                var base = palette[i % palette.length];
                if (ds.label && ds.label.indexOf('22:00 - 18:00') >= 0) { base = '#bda870'; }
                else if (ds.label && ds.label.indexOf('18:00 - 22:00') >= 0) { base = '#f9b115'; }
                ds.borderColor = ds.borderColor || base;
                ds.backgroundColor = ds.backgroundColor || base + '66';
                ds.pointBackgroundColor = base;
                ds.tension = 0.3;
                ds.fill = true;
            });
            return chartdt;
        }

        function renderCharts(datas) {
            var chartdt = colorize(datas.chartdt);

            if (charts.area) { charts.area.destroy(); }
            if (charts.bar) { charts.bar.destroy(); }

            charts.area = new Chart(document.getElementById('areaChart').getContext('2d'), {
                type: 'line', data: chartdt, options: chartOptions(datas)
            });
            charts.bar = new Chart(document.getElementById('barChart').getContext('2d'), {
                type: 'bar', data: JSON.parse(JSON.stringify(chartdt)), options: chartOptions(datas)
            });

            $('#legendDiv').empty();
        }

        function resetCharts() {
            renderCharts({
                meterid: [],
                chartdt: { labels: MONTHS, datasets: [{ label: @json(__('tenant/dashboard.monthly_usage')), data: MONTHS.map(function () { return 0; }) }] }
            });
        }

        function loadGraph() {
            var meteridcombo = $('#meteridcombo').val();
            var utility = $('#utilitycombo').val();

            if (!meteridcombo || !utility) {
                resetCharts();
                return;
            }

            $.ajax({
                type: 'POST',
                url: URL_GRAPH,
                data: { meteridcombo: meteridcombo, yearcombo: $('#yearcombo').val(), utility: utility }
            }).done(function (data) {
                var datas = (typeof data === 'string') ? JSON.parse(data) : data;
                if (!datas || !datas.chartdt) {
                    resetCharts();
                    return;
                }
                renderCharts(datas);
            }).fail(function (xhr) {
                console.log(xhr.responseText);
                resetCharts();
            });
        }

        function updateUtilityTitle() {
            var utility = $('#utilitycombo').val();
            var title = @json(__('tenant/dashboard.monthly_utility_usage'));
            if (utility === 'E') { title = @json(__('tenant/dashboard.monthly_electric_usage')); }
            else if (utility === 'W') { title = @json(__('tenant/dashboard.monthly_water_usage')); }
            else if (utility === 'G') { title = @json(__('tenant/dashboard.monthly_gas_usage')); }
            $('#utilityTitle').text(title);
        }

        $('#meteridcombo, #yearcombo').on('change', loadGraph);

        $('#utilitycombo').on('change', function () {
            updateUtilityTitle();
            var utility = $(this).val();
            var $meter = $('#meteridcombo');

            resetCharts();
            $meter.empty().append($('<option value=""></option>').text(@json(__('tenant/dashboard.select_meter_id'))));

            if (utility === '') {
                $meter.prop('disabled', true).trigger('change');
                return;
            }

            $.ajax({
                type: 'POST',
                url: URL_METER,
                data: { utility: utility },
                dataType: 'json'
            }).done(function (response) {
                if (response.status) {
                    $meter.append(response.html).prop('disabled', false);
                } else {
                    $meter.prop('disabled', true);
                }
                $meter.trigger('change');
            }).fail(function (xhr) {
                console.log(xhr.responseText);
                $meter.prop('disabled', true);
            });
        });

        // Chart.js merender ulang saat tab Bar pertama kali ditampilkan
        $('#tab2').on('shown.coreui.tab', function () {
            if (charts.bar) { charts.bar.resize(); }
        });

        resetCharts();
    });
</script>
@endpush
