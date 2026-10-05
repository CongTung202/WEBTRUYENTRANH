@extends('layouts.app')

@section('title', 'Lịch Sử Đọc Truyện - GTSCHUNDER')

@section('content')
<div class="container" style="padding-top: 30px; padding-bottom: 50px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 1px solid var(--border-color);">
        <h2 style="font-size: 1.4rem; font-weight: 800;">
            Lịch Sử Đọc Truyện
        </h2>

        @if(auth()->check() && $histories->isNotEmpty())
            <form action="{{ route('history.clear') }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa toàn bộ lịch sử đọc truyện?');">
                @csrf
                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--accent-red); border-color: rgba(239, 68, 68, 0.3);">
                    Xóa tất cả lịch sử
                </button>
            </form>
        @endif
    </div>

    @if(!auth()->check())
        <div style="text-align: center; padding: 60px 20px; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
            <i class="fa-solid fa-user-lock" style="font-size: 3rem; color: var(--text-dim); margin-bottom: 14px;"></i>
            <h3 style="font-size: 1.2rem; margin-bottom: 8px;">Đăng nhập để đồng bộ lịch sử đọc truyện</h3>
            <p style="color: var(--text-dim); font-size: 0.92rem; margin-bottom: 20px;">Lịch sử đọc sẽ được lưu trên đám mây, giúp bạn đọc tiếp mọi lúc trên điện thoại và máy tính.</p>
            <a href="{{ route('login') }}" class="btn btn-primary">Đăng nhập tài khoản</a>
        </div>
    @elseif($histories->isEmpty())
        <div style="text-align: center; padding: 70px 20px; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
            <i class="fa-regular fa-clock" style="font-size: 3.5rem; color: var(--text-dim); margin-bottom: 16px;"></i>
            <h3 style="font-size: 1.2rem; margin-bottom: 8px;">Bạn chưa đọc bộ truyện nào</h3>
            <p style="color: var(--text-dim); font-size: 0.92rem; margin-bottom: 24px;">Hãy khám phá kho truyện phong phú và chọn một bộ yêu thích để bắt đầu!</p>
            <a href="{{ route('comics.index') }}" class="btn btn-primary">Bắt đầu đọc truyện</a>
        </div>
    @else
        <div class="comics-grid">
            @foreach($histories as $h)
                @if($h->comic && $h->chapter)
                    <div class="comic-card">
                        <div class="comic-thumb-wrap">
                            <a href="{{ route('reader.read', ['comicSlug' => $h->comic->slug, 'chapterNumber' => $h->chapter->chapter_number]) }}" class="comic-thumb-link">
                                <img src="{{ $h->comic->cover_url }}" alt="{{ $h->comic->title }}" class="comic-thumb" loading="lazy" onerror="this.src='{{ asset('images/default.png') }}'">
                            </a>
                            <div class="comic-thumb-overlay">
                                <a href="{{ route('comics.show', $h->comic->slug) }}" class="comic-title-in-thumb" title="{{ $h->comic->title }}">
                                    {{ $h->comic->title }}
                                </a>
                                <a href="{{ route('reader.read', ['comicSlug' => $h->comic->slug, 'chapterNumber' => $h->chapter->chapter_number]) }}" class="comic-thumb-chap-row" style="background: var(--primary); color: #fff;">
                                    <span class="chap-num">Đọc tiếp Chap {{ $h->chapter->formatted_number }}</span>
                                    <span class="chap-time" style="color: #e2e8f0;">{{ $h->last_read_at->diffForHumans(null, true, true) }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="pagination-wrapper">
            {{ $histories->links() }}
        </div>
    @endif

</div>
@endsection
