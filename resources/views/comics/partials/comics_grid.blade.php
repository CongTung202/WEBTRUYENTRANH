@if($comics->isEmpty())
    <div style="text-align: center; padding: 60px 20px; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
        <h4 style="font-size: 1.2rem; margin-bottom: 8px;">Không tìm thấy truyện phù hợp</h4>
        <p style="color: var(--text-dim); font-size: 0.9rem;">Hãy thử chọn các thể loại khác hoặc xóa bớt tiêu chí lọc.</p>
    </div>
@else
    <div class="comics-grid">
        @foreach($comics as $comic)
            <div class="comic-card">
                <div class="comic-thumb-wrap">
                    <a href="{{ route('comics.show', $comic->slug) }}" class="comic-thumb-link">
                        <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" class="comic-thumb" loading="lazy" onerror="this.src='{{ asset('images/default.png') }}'">
                    </a>
                    <span class="comic-status-badge badge {{ $comic->status === 'completed' ? 'badge-completed' : 'badge-ongoing' }}">
                        {{ $comic->status_label }}
                    </span>
                    <div class="comic-thumb-overlay">
                        <a href="{{ route('comics.show', $comic->slug) }}" class="comic-title-in-thumb" title="{{ $comic->title }}">
                            {{ $comic->title }}
                        </a>
                        @if($comic->latestChapter)
                            <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $comic->latestChapter->chapter_number]) }}" class="comic-thumb-chap-row">
                                <span class="chap-num">Chap {{ $comic->latestChapter->formatted_number }}</span>
                                <span class="chap-time">{{ $comic->updated_time_formatted }}</span>
                            </a>
                        @else
                            <div class="comic-thumb-chap-row" style="opacity: 0.7; pointer-events: none;">
                                <span class="chap-num">Đang cập nhật</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="pagination-wrapper" style="margin-top: 30px;">
        {{ $comics->links() }}
    </div>
@endif
