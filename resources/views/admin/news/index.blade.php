@extends('admin.template.layout2.base')
@section('title', __('admin/news.news_and_promo'))
@push('styles')
<style>
    .news-thumb { width: 72px; height: 54px; object-fit: cover; border-radius: .25rem; border: 1px solid var(--cui-border-color); }
</style>
@endpush
@section('content')
<div class="page-body">
    <div>
        <div class="page-block">
            <div class="page-head">
                <div class="page-head-row">
                    <div class="page-head-content">
                        <h3 class="page-title">{{ __('admin/news.news_and_promo') }}</h3>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered w-100" id="tblgroup">
                            <thead>
                            <tr>
                                <th style="padding-right: 20px;padding-left: 10px;">{{ __('admin/news.col_no') }}</th>
                                <th>{{ __('admin/news.content_type') }}</th>
                                <th>{{ __('admin/news.col_picture') }}</th>
                                <th width="40%">{{ __('common.title') }}</th>
                                <th>{{ __('common.start_date') }}</th>
                                <th>{{ __('common.end_date') }}</th>
                                <th>{{ __('common.status') }}</th>
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
    // gambar News yang hilang / gagal dimuat -> logo IFCA
    var fallback = @json(\App\Support\NewsPicture::fallbackUrl());
    var tblgroupp;
    $(function() {
        tblgroupp = $('#tblgroup').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
            "url" : "{{ url('/admin/news/all') }}",
            "type": "POST",
            data: {
                "_token": "{{ csrf_token() }}"
            }
        },
        columns: [
            { data: 'row_number', name: 'row_number' },
            { data:"content_type", name:"content_type", sortable: false},
            // thumbnail gambar / ikon YouTube; klik gambar = lihat ukuran penuh
            { data:"picture_url", name:"picture", sortable: false, searchable: false,
                render: function (data, type, row) {
                    if (type !== 'display') { return data || ''; }
                    if (row.attach_type === 'Y') {
                        return row.youtube_link
                            ? '<a href="' + $('<div>').text(row.youtube_link).html() + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger"><i class="cil-media-play"></i><span>YouTube</span></a>'
                            : '-';
                    }
                    var src = $('<div>').text(data || fallback).html();
                    return '<a href="' + src + '" target="_blank" rel="noopener"><img src="' + src + '" alt="" class="news-thumb" onerror="this.onerror=null;this.src=fallback;this.style.objectFit=\'contain\'"></a>';
                }
            },
            { data:"subject",name:"subject"},
            {
                data: "start_date",
                name: "start_date",
                sortable: true,
                render: function(data, type, row) {
                    if (!data) return '';

                    const date = new Date(data);

                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();

                    return `${day}-${month}-${year}`;
                }
            },
            {
                data: "end_date",
                name: "end_date",
                sortable: true,
                render: function(data, type, row) {
                    if (!data) return '';

                    const date = new Date(data);

                    const day = String(date.getDate()).padStart(2, '0');
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const year = date.getFullYear();

                    return `${day}-${month}-${year}`;
                }
            },
            {
                // sama dengan yang tampil di tenant (start_date <= hari ini <= end_date, per tanggal):
                // belum mulai = Terjadwal, dalam periode = Aktif, lewat = Kedaluwarsa
                data: "end_date",
                name: "status",
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    // "yyyy-mm-dd hh:mm:ss" -> "yyyymmdd" (dibandingkan sebagai teks, aman di semua browser)
                    var day = function (v) { return v ? String(v).substr(0, 10).replace(/-/g, '') : ''; };
                    var now = new Date();
                    var today = now.getFullYear() + String(now.getMonth() + 1).padStart(2, '0') + String(now.getDate()).padStart(2, '0');
                    var start = day(row.start_date), end = day(row.end_date);

                    if (start && today < start) {
                        return '<span class="badge text-bg-warning">' + @json(__('admin/news.scheduled')) + '</span>';
                    }
                    if (end && today > end) {
                        return '<span class="badge text-bg-danger">' + @json(__('admin/news.expired')) + '</span>';
                    }
                    return '<span class="badge text-bg-success">' + @json(__('admin/news.active')) + '</span>';
                }
            }
          ],
          dom: '<"toolbar group">frtip'
      });
      $("div.group").html(
        '<button id="addgroup" class="btn btn-sm btn-primary">' + @json(__('common.add')) + '</button>&nbsp;'+
        '<button id="editgroup" class="btn btn-sm btn-info">' + @json(__('common.edit')) + '</button>&nbsp;'+
        '<button id="deletegroup" class="btn btn-sm btn-danger">' + @json(__('common.delete')) + '</button>&nbsp;'

      );
      tblgroupp.on('click', 'tr', function() {
          if ($(this).hasClass('selected')) {
              $(this).removeClass('selected');
          } else {

            tblgroupp.$('tr.selected').removeClass('selected');
              $(this).addClass('selected');
          }
      });

      $('#addgroup').click(function(){
        window.location.href="{{url('/admin/news/form/A')}}";

      })

      $('#editgroup').click(function(){
        var rows = tblgroupp.rows('.selected').indexes();
        if (rows.length < 1) {
            Swal.fire(@json(__('common.information')),@json(__('admin/news.select_row')),"warning");
            return;
        }
        var data = tblgroupp.rows(rows).data();
        var id = data[0].id;
        var site_url = "{{url('/admin/news/form')}}"+'/E/'+id;

        window.location.href=site_url;

    })

        $('#deletegroup').click(function(){
            var rows = tblgroupp.rows('.selected').indexes();
            if (rows.length < 1) {
                Swal.fire(@json(__('common.information')),@json(__('admin/news.select_row')),"warning");
                return;
            }
            var data = tblgroupp.rows(rows).data();
            var id = data[0].id;

            Swal.fire({
                title: @json(__('common.are_you_sure')),
                text: @json(__('admin/news.cannot_revert')),
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: @json(__('admin/news.yes_delete'))
            })
            .then(function(a){
                if (a.value==true) {
                    Delete(id);
                }else{
                }
            })
        })
    });

    function Delete(id) {
        $.ajax({
            url : "{{ url('/admin/news/delete') }}",
            type:"POST",
            data: { id: id,"_token": "{{ csrf_token() }}" },
            dataType:"json",
            success:function(event, data){
                Swal.fire(@json(__('common.information')),event.pesan,"success");
                tblgroupp.ajax.reload(null,true);
            },
            error: function(jqXHR, textStatus, errorThrown){
                Swal.fire(@json(__('common.information')),@json(__('admin/news.request_error')).replace(':status', textStatus).replace(':error', errorThrown),"warning");
            }
        });
    }

</script>

@endsection

