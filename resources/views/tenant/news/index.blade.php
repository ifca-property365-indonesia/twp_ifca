@extends('tenant.template.base')

@section('title', __('tenant/news.title'))

@section('content')
    @php
        $badges = ['success', 'primary', 'warning', 'danger'];
        $youtubeId = function ($link) {
            $q = [];
            parse_str((string) parse_url($link, PHP_URL_QUERY), $q);
            if (!empty($q['v'])) {
                return $q['v'];
            }
            // format youtu.be/<id>
            $path = trim((string) parse_url($link, PHP_URL_PATH), '/');
            return $path !== '' ? basename($path) : '';
        };
    @endphp
    <div class="page-body">
        <div class="page-head">
            <div class="page-head-row">
                <div class="page-head-content">
                    <h3 class="page-title">{{ __('tenant/news.title') }}</h3>
                    <div class="page-desc">{{ __('tenant/news.page_desc') }}</div>
                </div>
            </div>
        </div>

        <div class="page-block">
            @if (!empty($datanewsfeed) && count($datanewsfeed) > 0)
                <ul class="timeline-list">
                    @foreach ($datanewsfeed as $newsfeed)
                        <li class="timeline-item" id="news-{{ $newsfeed->id }}">
                            <div class="timeline-status bg-{{ $badges[$newsfeed->status] ?? 'secondary' }}"></div>
                            <div class="card timeline-card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">{{ $newsfeed->subject }}</h5>
                                    @if (!empty($newsfeed->picture))
                                        <div class="mb-3">
                                            <img src="{{ \App\Support\NewsPicture::url($newsfeed->picture) }}" alt="" class="img-fluid rounded border" onerror="{{ \App\Support\NewsPicture::onError() }}">
                                        </div>
                                    @endif
                                    @if (!empty($newsfeed->youtube_link) && $youtubeId($newsfeed->youtube_link) !== '')
                                        <div class="ratio ratio-16x9 mb-3 rounded overflow-hidden">
                                            <iframe src="https://www.youtube.com/embed/{{ $youtubeId($newsfeed->youtube_link) }}" title="{{ $newsfeed->subject }}" allowfullscreen></iframe>
                                        </div>
                                    @endif
                                    <div class="news-content">{!! $newsfeed->content !!}</div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="card">
                    <div class="card-body text-center py-5 text-body-secondary">
                        <i class="cil-newspaper fs-1 d-block mb-2"></i>
                        {{ __('tenant/news.no_news') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
