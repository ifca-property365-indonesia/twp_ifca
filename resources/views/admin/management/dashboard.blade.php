@extends('admin.template.layout2.base')

@section('title', __('admin/management.title'))

@push('head-scripts')
    <script src="{{ url('assets/vendor/highcharts/highcharts.js') }}"></script>
@endpush

@section('content')
<div class="page-body">
    <div class="page-head">
        <div class="page-head-row">
            <div class="page-head-content">
                <h3 class="page-title">{{ __('admin/management.title') }}</h3>
                <div class="page-desc"><p>{{ __('admin/management.desc') }}</p></div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-bold">{{ __('admin/management.aging_ap_graphic') }}</div>
        <div class="card-body">
            <div id="apChart" style="height: 460px;"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header fw-bold">{{ __('admin/management.aging_ar_graphic') }}</div>
        <div class="card-body">
            <div id="arChart" style="height: 460px;"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">{{ __('admin/management.revenue') }}</span>
            <div style="width: 140px;"><select id="yearFilterRevenue" class="form-select form-select-sm js-select"></select></div>
        </div>
        <div class="card-body">
            <div id="revenueChart" style="height: 400px;"></div>
            <div id="dataTableContainerRevenue" class="table-responsive mt-2"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">{{ __('admin/management.expense') }}</span>
            <div style="width: 140px;"><select id="yearFilterExpense" class="form-select form-select-sm js-select"></select></div>
        </div>
        <div class="card-body">
            <div id="expenseChart" style="height: 400px;"></div>
            <div id="dataTableContainerExpense" class="table-responsive mt-2"></div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>

var MGMT_LANG = @json(__('admin/management.chart'));

$(document).ready(function () {

    let currentYear = new Date().getFullYear();

    // Tangkap kedua elemen dropdown
    let yearFilterRevenue = $('#yearFilterRevenue');
    let yearFilterExpense = $('#yearFilterExpense');

    // 1. Generate opsi tahun untuk KEDUA dropdown
    for (let i = 0; i < 20; i++) {
        let y = currentYear - i;
        
        // Buat elemen option baru
        let optionHtml = `<option value="${y}">${y}</option>`;
        
        // Masukkan ke masing-masing dropdown
        yearFilterRevenue.append(optionHtml);
        yearFilterExpense.append(optionHtml);
    }
    // tampilan Select2 ikut opsi yang baru diisi
    yearFilterRevenue.add(yearFilterExpense).trigger('change.select2');

    // 2. Event Listener khusus untuk Chart Revenue
    yearFilterRevenue.on('change', function() {
        let selectedYear = $(this).val();
        loadRevenueChart(selectedYear); // Hanya meload ulang Revenue
    });

    // 3. Event Listener khusus untuk Chart Expense
    yearFilterExpense.on('change', function() {
        let selectedYear = $(this).val();
        loadExpenseChart(selectedYear); // Hanya meload ulang Expense
    });

    // 4. Load chart yang tidak butuh filter tahun
    loadApChart();
    loadArChart();

    // 5. Load kedua chart pertama kali dengan tahun saat ini
    loadRevenueChart(currentYear);
    loadExpenseChart(currentYear);

});

function formatCurrency(value)
{
    value = Number(value);

    if (value >= 1000000000000) {
        return (value / 1000000000000).toFixed(2) + 'T';
    }

    if (value >= 1000000000) {
        return (value / 1000000000).toFixed(2) + 'B';
    }

    if (value >= 1000000) {
        return (value / 1000000).toFixed(2) + 'M';
    }

    if (value >= 1000) {
        return (value / 1000).toFixed(2) + 'K';
    }

    return value.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function renderArTable(response)
{
    var html = '';

    for(var i = 0; i < response.length; i += 5)
    {
        var rowColor = (Math.floor(i / 5) % 2 === 0)
            ? '#f8f9fc'
            : '#eef2ff';

        html += '<tr>';

        for(var j = 0; j < 5; j++)
        {
            var idx = i + j;

            if(idx < response.length)
            {
                html += '<td style="background:'+ rowColor +'; border:1px solid #d3d3d3;">'
                        + response[idx].debtor_acct +
                        '</td>';

                html += '<td style="background:'+ rowColor +'; border:1px solid #d3d3d3; text-align:right;">'
                        + formatCurrency(response[idx].amount) +
                        '</td>';
            }
            else
            {
                html += '<td style="background:'+ rowColor +'; border:1px solid #d3d3d3;"></td>';
                html += '<td style="background:'+ rowColor +'; border:1px solid #d3d3d3;"></td>';
            }
        }

        html += '</tr>';
    }

    $('#arTable tbody').html(html);
}

function loadArChart()
{
    $.ajax({
        url: "<?php echo e(url('admin/management/ar-aging')); ?>",
        type: "GET",
        dataType: "json",

        success: function(response)
        {
            renderArTable(response);

            var categories = [];
            var amounts = [];

            $.each(response, function(i, row) {

                categories.push(row.name);

                // convert ke Billion (Bn)
                amounts.push(
                    parseFloat(row.amount) / 1000000000
                );

            });

            Highcharts.chart('arChart', {

                accessibility: {
                    enabled: false // <--- Tambahkan kode ini untuk menghilangkan warning
                },
                
                chart: {
                    type: 'bar'
                },

                title: {
                    text: MGMT_LANG.ar_profile
                },

                xAxis: {
                    categories: categories,
                    title: {
                        text: null
                    }
                },

                yAxis: {
                    min: 0,
                    title: {
                        text: null
                    },
                    labels: {
                        formatter: function() {
                            return this.value.toFixed(3);
                        }
                    }
                },

                legend: {
                    enabled: false
                },

                credits: {
                    enabled: false
                },

                tooltip: {
                    pointFormatter: function() {
                        return '<b>' + Highcharts.numberFormat(this.y, 3) + '</b>';
                    }
                },

                plotOptions: {
                    bar: {
                        dataLabels: {
                            enabled: true,
                            formatter: function() {
                                return Highcharts.numberFormat(this.y, 3);
                            }
                        }
                    }
                },

                series: [{
                    name: 'AR',
                    color: '#2f6f8f',
                    data: amounts
                }]
            });
        }
    });
}

function renderApTable(response)
{
    var html = '';

    for(var i = 0; i < response.length; i += 5)
    {
        var rowColor = (Math.floor(i / 5) % 2 === 0)
            ? '#f8f9fc'
            : '#eef2ff';

        html += '<tr>';

        for(var j = 0; j < 5; j++)
        {
            var idx = i + j;

            if(idx < response.length)
            {
                html += '<td style="background:'+ rowColor +'; border:1px solid #d3d3d3;">'
                        + response[idx].creditor_acct +
                        '</td>';

                html += '<td style="background:'+ rowColor +'; border:1px solid #d3d3d3; text-align:right;">'
                        + formatCurrency(response[idx].amount) +
                        '</td>';
            }
            else
            {
                html += '<td style="background:'+ rowColor +'; border:1px solid #d3d3d3;"></td>';
                html += '<td style="background:'+ rowColor +'; border:1px solid #d3d3d3;"></td>';
            }
        }

        html += '</tr>';
    }

    $('#apTable tbody').html(html);
}

function loadApChart()
{
    $.ajax({
        url: "<?php echo e(url('admin/management/ap-aging')); ?>",
        type: "GET",
        dataType: "json",

        success: function(response)
        {
            renderApTable(response);

            var categories = [];
            var amounts = [];

            $.each(response, function(i, row) {

                categories.push(row.name);

                // convert ke Billion (Bn)
                amounts.push(
                    parseFloat(row.amount) / 1000000000
                );

            });

            Highcharts.chart('apChart', {

                accessibility: {
                    enabled: false // <--- Tambahkan kode ini untuk menghilangkan warning
                },
                
                chart: {
                    type: 'bar'
                },

                title: {
                    text: MGMT_LANG.ap_profile
                },

                xAxis: {
                    categories: categories,
                    title: {
                        text: null
                    }
                },

                yAxis: {
                    min: 0,
                    title: {
                        text: null
                    },
                    labels: {
                        formatter: function() {
                            return this.value.toFixed(3);
                        }
                    }
                },

                legend: {
                    enabled: false
                },

                credits: {
                    enabled: false
                },

                tooltip: {
                    pointFormatter: function() {
                        return '<b>' + Highcharts.numberFormat(this.y, 3) + '</b>';
                    }
                },

                plotOptions: {
                    bar: {
                        dataLabels: {
                            enabled: true,
                            formatter: function() {
                                return Highcharts.numberFormat(this.y, 3);
                            }
                        }
                    }
                },

                series: [{
                    name: 'AP',
                    color: '#2f6f8f',
                    data: amounts
                }]
            });
        }
    });
}

function loadRevenueChart(year) {
    $.ajax({
        url: "<?php echo e(url('admin/management/revenue-data')); ?>",
        type: "GET",
        data: { year: year }, //baru tambah
        dataType: "json",
        success: function(response) {
            var categories = MGMT_LANG.months.slice();

            var actuals = [];
            var budgets = [];

            $.each(response, function(i, row){
                actuals.push(parseFloat(row.actual || 0));
                budgets.push(parseFloat(row.budget || 0));
            });

            // 1. Inisialisasi Highcharts
            Highcharts.chart('revenueChart', {
                accessibility: {
                    enabled: false // <--- Tambahkan kode ini untuk menghilangkan warning
                },
                chart: {
                    type: 'column',
                    marginLeft: 220, // Disinkronkan dengan lebar kolom pertama tabel
                    marginRight: 15,
                    marginBottom: 20 // Kurangi margin bawah agar menempel ke tabel
                },
                title: {
                    text: MGMT_LANG.revenue_year.replace(':year', year) // <--- Akan berubah otomatis jadi REVENUE 2025, dsb.
                },
                xAxis: {
                    categories: categories,
                    labels: { enabled: false }, // Sembunyikan label X bawaan
                    tickLength: 0,
                    lineWidth: 0
                },
                yAxis: {
                    min: 0,
                    title: { text: null },
                    labels: {
                        formatter: function () {
                            // Format 30,000.0 sesuai gambar 2
                            return Highcharts.numberFormat(this.value, 1, '.', ','); 
                        }
                    }
                },
                credits: {
                    enabled: false
                },
                legend: {
                    enabled: false // Legend dimatikan karena informasinya ada di tabel
                },
                tooltip: {
                    shared: true,
                    pointFormatter: function() {
                        return '<span style="color:' + this.color + '">●</span> ' +
                            this.series.name + ': <b>' +
                            Highcharts.numberFormat(this.y, 1, '.', ',') +
                            '</b><br/>';
                    }
                },
                plotOptions: {
                    column: {
                        pointPadding: 0,   // Membuat bar saling menempel (tidak ada jarak dalam 1 grup)
                        groupPadding: 0.1, // Jarak antar bulan dibuat lebih sempit
                        borderWidth: 0,
                        dataLabels: { enabled: false }
                    }
                },
                series: [
                    {
                        name: MGMT_LANG.revenue_actual,
                        data: actuals,
                        color: '#4F81BD',
                    },
                    {
                        name: MGMT_LANG.revenue_budget,
                        data: budgets,
                        color: '#C0504D',
                    }
                ]
            });

            // 2. Build HTML Data Table Dinamis
            let tableHTML = `<table style="width: 100%; table-layout: fixed; border-collapse: collapse; font-family: 'Segoe UI', sans-serif; font-size: 12px; text-align: center; color: #666;">`;

            // Baris 1: Header Bulan
            tableHTML += `<tr><td style="width: 220px; border: none;"></td>`; // Kosong di pojok kiri (lebar sama dengan marginLeft chart)
            $.each(categories, function(i, cat) {
                tableHTML += `<td style="border: 1px solid #ccc; padding: 5px;">${cat}</td>`;
            });
            tableHTML += `</tr>`;

            // Baris 2: Data Actual
            tableHTML += `<tr>
                <td style="border: 1px solid #ccc; text-align: left; padding: 5px 10px;">
                    <span style="color:#4F81BD; margin-right:5px;">■</span> ${MGMT_LANG.revenue_actual}
                </td>`;
            $.each(actuals, function(i, val) {
                // Menampilkan kosong jika 0, atau format angka "21,428.8"
                let txt = val > 0 ? Highcharts.numberFormat(val, 1, '.', ',') : '';
                tableHTML += `<td style="border: 1px solid #ccc; padding: 5px;">${txt}</td>`;
            });
            tableHTML += `</tr>`;

            // Baris 3: Data Budget
            tableHTML += `<tr>
                <td style="border: 1px solid #ccc; text-align: left; padding: 5px 10px;">
                    <span style="color:#C0504D; margin-right:5px;">■</span> ${MGMT_LANG.revenue_budget}
                </td>`;
            $.each(budgets, function(i, val) {
                let txt = val > 0 ? Highcharts.numberFormat(val, 1, '.', ',') : '';
                tableHTML += `<td style="border: 1px solid #ccc; padding: 5px;">${txt}</td>`;
            });
            tableHTML += `</tr></table>`;

            // Render tabel ke dalam container
            $('#dataTableContainerRevenue').html(tableHTML);
        }
    });
}

function loadExpenseChart(year) {
    $.ajax({
        url: "<?php echo e(url('admin/management/expense-data')); ?>",
        type: "GET",
        data: { year: year }, //baru tambah
        dataType: "json",
        success: function(response) {
            var categories = MGMT_LANG.months.slice();

            var actuals = [];
            var budgets = [];

            $.each(response, function(i, row){
                actuals.push(parseFloat(row.actual || 0));
                budgets.push(parseFloat(row.budget || 0));
            });

            // 1. Inisialisasi Highcharts
            Highcharts.chart('expenseChart', {
                accessibility: {
                    enabled: false // <--- Tambahkan kode ini untuk menghilangkan warning
                },
                chart: {
                    type: 'column',
                    marginLeft: 220, // Disinkronkan dengan lebar kolom pertama tabel
                    marginRight: 15,
                    marginBottom: 20 // Kurangi margin bawah agar menempel ke tabel
                },
                title: {
                    text: MGMT_LANG.expense_year.replace(':year', year) // <--- Akan berubah otomatis jadi REVENUE 2025, dsb.
                },
                xAxis: {
                    categories: categories,
                    labels: { enabled: false }, // Sembunyikan label X bawaan
                    tickLength: 0,
                    lineWidth: 0
                },
                yAxis: {
                    min: 0,
                    title: { text: null },
                    labels: {
                        formatter: function () {
                            // Format 30,000.0 sesuai gambar 2
                            return Highcharts.numberFormat(this.value, 1, '.', ','); 
                        }
                    }
                },
                credits: {
                    enabled: false
                },
                legend: {
                    enabled: false // Legend dimatikan karena informasinya ada di tabel
                },
                tooltip: {
                    shared: true,
                    pointFormatter: function() {
                        return '<span style="color:' + this.color + '">●</span> ' +
                            this.series.name + ': <b>' +
                            Highcharts.numberFormat(this.y, 1, '.', ',') +
                            '</b><br/>';
                    }
                },
                plotOptions: {
                    column: {
                        pointPadding: 0,   // Membuat bar saling menempel (tidak ada jarak dalam 1 grup)
                        groupPadding: 0.1, // Jarak antar bulan dibuat lebih sempit
                        borderWidth: 0,
                        dataLabels: { enabled: false }
                    }
                },
                series: [
                    {
                        name: MGMT_LANG.expense_actual,
                        data: actuals,
                        color: '#4F81BD',
                    },
                    {
                        name: MGMT_LANG.expense_budget,
                        data: budgets,
                        color: '#C0504D',
                    }
                ]
            });

            // 2. Build HTML Data Table Dinamis
            let tableHTML = `<table style="width: 100%; table-layout: fixed; border-collapse: collapse; font-family: 'Segoe UI', sans-serif; font-size: 12px; text-align: center; color: #666;">`;

            // Baris 1: Header Bulan
            tableHTML += `<tr><td style="width: 220px; border: none;"></td>`; // Kosong di pojok kiri (lebar sama dengan marginLeft chart)
            $.each(categories, function(i, cat) {
                tableHTML += `<td style="border: 1px solid #ccc; padding: 5px;">${cat}</td>`;
            });
            tableHTML += `</tr>`;

            // Baris 2: Data Actual
            tableHTML += `<tr>
                <td style="border: 1px solid #ccc; text-align: left; padding: 5px 10px;">
                    <span style="color:#4F81BD; margin-right:5px;">■</span> ${MGMT_LANG.expense_actual}
                </td>`;
            $.each(actuals, function(i, val) {
                // Menampilkan kosong jika 0, atau format angka "21,428.8"
                let txt = val > 0 ? Highcharts.numberFormat(val, 1, '.', ',') : '';
                tableHTML += `<td style="border: 1px solid #ccc; padding: 5px;">${txt}</td>`;
            });
            tableHTML += `</tr>`;

            // Baris 3: Data Budget
            tableHTML += `<tr>
                <td style="border: 1px solid #ccc; text-align: left; padding: 5px 10px;">
                    <span style="color:#C0504D; margin-right:5px;">■</span> ${MGMT_LANG.expense_budget}
                </td>`;
            $.each(budgets, function(i, val) {
                let txt = val > 0 ? Highcharts.numberFormat(val, 1, '.', ',') : '';
                tableHTML += `<td style="border: 1px solid #ccc; padding: 5px;">${txt}</td>`;
            });
            tableHTML += `</tr></table>`;

            // Render tabel ke dalam container
            $('#dataTableContainerExpense').html(tableHTML);
        }
    });
}

</script>
@endpush
