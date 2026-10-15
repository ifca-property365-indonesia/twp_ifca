{{-- Isi dialog riwayat versi (Admin\ReportController@history), dimuat ke #rptHistoryContent --}}
@use('App\Support\ReportDocuments', 'RD')
<div class="modal-header">
    <h5 class="modal-title">{{ __('admin/reports.history_title', ['name' => $report->name]) }}</h5>
    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
</div>
<div class="modal-body">
    <div class="small text-soft mb-3">{{ $groups[$report->group_cd] ?? $report->group_cd }}</div>
    @foreach ($report->versions as $v)
        @php $latest = (int) $v->version === (int) $report->current_version; @endphp
        <div class="rpt-hist-ver {{ $latest ? 'latest' : '' }}">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div>
                    <span class="fw-bold">{{ __('admin/reports.version_label', ['version' => $v->version]) }}</span>
                    @if ($latest)<span class="badge badge-soft-primary ms-1">{{ __('admin/reports.latest') }}</span>@endif
                    <div class="small text-soft">{{ __('admin/reports.uploaded_by', [
                        'date' => \Illuminate\Support\Carbon::parse($v->uploaded_at)->locale(app()->getLocale())->translatedFormat('d M Y H:i'),
                        'user' => $v->uploaded_by ?: '-',
                    ]) }} · {{ trans_choice('admin/reports.files_count', $v->files->count(), ['count' => $v->files->count()]) }}</div>
                </div>
                <a class="btn btn-sm btn-outline-secondary" href="{{ url('admin/reports/' . $report->id . '/download') }}?version={{ $v->version }}"><i class="cil-cloud-download"></i> {{ __('admin/reports.download_zip') }}</a>
            </div>
            @foreach ($v->files as $f)
                @php $canInline = in_array(strtolower(pathinfo($f->file_name, PATHINFO_EXTENSION)), $inline, true); @endphp
                <a class="rpt-file" href="{{ url('admin/reports/file/' . $f->id) }}{{ $canInline ? '?inline=1' : '' }}" @if ($canInline) target="_blank" rel="noopener" @endif>
                    <span><span class="rpt-fname">{{ $f->file_name }}</span>
                        @if ((int) $f->from_version !== (int) $v->version)<span class="rpt-tag keep ms-2">{{ __('admin/reports.from_version', ['version' => $f->from_version]) }}</span>@endif
                    </span>
                    <span class="rpt-fsize">{{ RD::size($f->size) }}</span>
                </a>
            @endforeach
        </div>
    @endforeach
</div>
<div class="modal-footer bg-body-tertiary">
    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">{{ __('common.close') }}</button>
</div>
