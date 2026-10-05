@extends('layouts.app')

@section('title', 'Tủ Truyện Theo Dõi - GTSCHUNDER')

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 50px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
        <h2 style="font-size: 1.4rem; font-weight: 800; display: flex; align-items: center; gap: 10px;">
            Tủ Truyện Đang Theo Dõi
            <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-dim);">({{ $bookmarks->total() }} bộ)</span>
        </h2>
    </div>

    @if($bookmarks->isEmpty())
        <div style="text-align: center; padding: 70px 20px; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
            <i class="fa-regular fa-bookmark" style="font-size: 3.5rem; color: var(--text-dim); margin-bottom: 16px;"></i>
            <h3 style="font-size: 1.2rem; margin-bottom: 8px;">Tủ truyện của bạn đang trống</h3>
            <p style="color: var(--text-dim); font-size: 0.92rem; margin-bottom: 24px;">Hãy nhấn nút "Theo dõi" ở các bộ truyện bạn yêu thích để dễ dàng cập nhật chapter mới nhất!</p>
            <a href="{{ route('comics.index') }}" class="btn btn-primary">Khám phá truyện tranh ngay</a>
        </div>
    @else
        <div class="comics-grid">
            @foreach($bookmarks as $b)
                @if($b->comic)
                    <div class="comic-card">
                        <div class="comic-thumb-wrap">
                            <a href="{{ route('comics.show', $b->comic->slug) }}" class="comic-thumb-link">
                                <img src="{{ $b->comic->cover_url }}" alt="{{ $b->comic->title }}" class="comic-thumb" loading="lazy" onerror="this.src='{{ asset('images/default.png') }}'">
                            </a>
                            <span class="comic-status-badge badge {{ $b->comic->status === 'completed' ? 'badge-completed' : 'badge-ongoing' }}">
                                {{ $b->comic->status_label }}
                            </span>
                            <div class="comic-thumb-overlay">
                                <a href="{{ route('comics.show', $b->comic->slug) }}" class="comic-title-in-thumb" title="{{ $b->comic->title }}">
                                    {{ $b->comic->title }}
                                </a>
                                @if($b->comic->latestChapter)
                                    <a href="{{ route('reader.read', ['comicSlug' => $b->comic->slug, 'chapterNumber' => $b->comic->latestChapter->chapter_number]) }}" class="comic-thumb-chap-row">
                                        <span class="chap-num">Chap {{ $b->comic->latestChapter->formatted_number }}</span>
                                        <span class="chap-time">{{ $b->comic->updated_time_formatted }}</span>
                                    </a>
                                @else
                                    <div class="comic-thumb-chap-row" style="opacity: 0.7; pointer-events: none;">
                                        <span class="chap-num">Đang cập nhật</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="pagination-wrapper">
            {{ $bookmarks->links() }}
        </div>
    @endif

</div>
@endsection
