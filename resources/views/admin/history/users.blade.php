@extends('admin.template.layout2.base')
@section('title', __('admin/history.log_user_history'))

@section('content')
<div class="page-body">
    <div>
        <div class="page-block">
            <div class="page-head">
                <div class="page-head-row">
                    <div class="page-head-content">
                        <h3 class="page-title">{{ __('admin/history.log_user_history') }}</h3>
                    </div>
                    <div class="page-head-content">
                        <button type="button" class="btn btn-pdf" id="btngenpdf"><i class="cil-cloud-download"></i><span>{{ __('common.generate_pdf') }}</span></button>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row g-3 align-items-end mb-3">
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label" for="start">{{ __('admin/history.login_date_from') }}</label>
                            <div class="form-control-wrap">
                                <div class="form-icon form-icon-left"><i class="cil-calendar"></i></div>
                                <input type="text" id="start" name="start" class="form-control date-picker" data-date-format="dd/mm/yyyy" value="" placeholder="{{ __('common.select_date') }}" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label" for="end">{{ __('admin/history.login_date_to') }}</label>
                            <div class="form-control-wrap">
                                <div class="form-icon form-icon-left"><i class="cil-calendar"></i></div>
                                <input type="text" id="end" name="end" class="form-control date-picker" data-date-format="dd/mm/yyyy" value="" placeholder="{{ __('common.select_date') }}" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-2">
                            <label class="form-label" for="account">{{ __('admin/history.log_account') }}</label>
                            <select id="account" class="form-select">
                                <option value="">{{ __('admin/history.log_account_all') }}</option>
                                <option value="administrator">{{ __('admin/history.log_type_admin') }}</option>
                                <option value="tenant">{{ __('admin/history.log_type_tenant') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-4 col-md-2">
                            <button type="button" class="btn btn-primary w-100" id="btnsearch"><i class="cil-search"></i><span>{{ __('common.search') }}</span></button>
                        </div>
                    </div>
<div class="table-responsive">
                        <table class="table table-hover table-bordered w-100" id="tbllog">
                            <thead>
                                <tr>
                                    <th class="sorting_asc">{{ __('admin/history.col_no') }}</th>
                                    <th>{{ __('admin/history.login_date') }}</th>
                                    <th>{{ __('admin/history.user_name') }}</th>
                                    <th>{{ __('admin/history.log_contact_name') }}</th>
                                    <th>{{ __('admin/history.log_email') }}</th>
                                    <th>{{ __('admin/history.log_business_id') }}</th>
                                    <th>{{ __('admin/history.log_tenant_no') }}</th>
                                    <th>{{ __('admin/history.log_account_type') }}</th>
                                    <th>{{ __('admin/history.log_ip') }}</th>
                                    <th>{{ __('admin/history.log_device') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div><!-- . -->
        </div> <!-- page-block -->
    </div>
</div>

<script type="text/javascript">
  var tbluser;
  $(function() {
    $('.date-picker').datepicker('setEndDate', new Date());
    $('.select2').select2();
    tbluser = $('#tbllog').DataTable({
          processing: true,
          serverSide: true,              
          ajax: {
            "url" : "{{ url('/admin/history/data/users') }}",
            "type": "POST",
            data: {
              "_token": "{{ csrf_token() }}",
                        "account": function () { return $('#account').val(); },
                        "date_end": function(d){
                            var a = $('#end').val();

                            var date = new Date(parseInt(a.substr(0,10)));
                            var year =a.substr(6,4);
                            var month=a.substr(3,2);
                            var day =a.substr(0,2);
                                       
                            var aa1 = year+"/"+month+"/"+day;
                            var b ="";
                            if(aa1 == "//"){
                                return b;
                            }{
                                return aa1;
                            }
                       
                        },
                        "date_start": function(d){
                            var a = $('#start').val();
                            var date = new Date(parseInt(a.substr(0,10)));
                            var year =a.substr(6,4);
                            var month=a.substr(3,2);
                            var day =a.substr(0,2);
                                       
                            var aa1 = year+"/"+month+"/"+day;
                            var b ="";
                            if(aa1 == "//"){
                                return b;
                            }{
                                return aa1;
                            }
                        },
                        
              }
            },
          columns: [
            {data: "row_number",name:"row_number", searchable:false},
            {data:"logintime",name:"logintime",
                render:function (data,type,row) {
                    return FormatDateTimeNew(data); 
                }
            },
            {data:"name",name:"name", sortable: true, render: escapeHtml},
            {data:"contact_name",name:"contact_name", sortable: true, render: escapeHtml},
            {data:"email",name:"email", sortable: true, render: escapeHtml},
            {data:"business_no",name:"business_no", sortable: true, render: escapeHtml},
            {data:"tenant_no",name:"tenant_no", sortable: true, render: escapeHtml},
            {data:"account_type",name:"account_type", sortable: true, render: function (d, t, row) {
                if (t !== 'display' || !d) { return d || ''; }
                var cls = row.tableforeign === 'administrator' ? 'bg-dark' : ($.trim(row.flag) === 'F' ? 'bg-primary' : 'bg-secondary');
                return '<span class="badge ' + cls + '">' + escapeHtml(d) + '</span>';
            }},
            {data:"ipaddress",name:"ipaddress", sortable: true, render: escapeHtml},
            // ringkasan browser / OS; User-Agent lengkap di tooltip
            {data:"device",name:"device", sortable: true, render: function (d, t, row) {
                if (t !== 'display') { return d || ''; }
                return d ? '<span title="' + escapeHtml(row.user_agent) + '">' + escapeHtml(d) + '</span>' : '-';
            }}
          ]
      });
   
    });

    $('#btnsearch').click(function(){
        var date_end = $('#end').val();
        var date_start = $('#start').val();

        if (date_start!='' && date_end=='')
        {
            Swal.fire(@json(__('common.warning')), @json(__('admin/history.choose_end_date')), 'warning');
            return;
        }
        tbluser.ajax.reload(null,true);
    });
    $('#btngenpdf').click(function(){
        var date_end = $('#end').val();
        var date_start = $('#start').val();

        if (date_start!='' && date_end=='')
        {
            Swal.fire(@json(__('common.warning')), @json(__('admin/history.choose_end_date')), 'warning');
            return;
        }
        var debtor = $('#debtor').val();
        var site_url = '{{ url("admin/history/dlpdf")}}';
            $.post(site_url,
                {type:"log",date_start:date_start,date_end:date_end,account:$('#account').val(),debtor_acct:(debtor == 'all' ? '' : debtor),"_token": "{{ csrf_token() }}" },
                function(data,status) {
                    console.log(data,status);
                    if(status=='success'){
                        window.open(data);
                    }else{
                        Swal.fire({
                                    title: @json(__('common.information')),
                                    icon:"error",
                                    text: @json(__('admin/history.pdf_failed'))
                                });
                    }
            });
    
      });
    
</script>

@endsection

