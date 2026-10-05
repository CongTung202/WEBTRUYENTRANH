@extends('layouts.app')

@section('title', 'Đọc ' . $comic->title . ' ' . $chapter->display_name . ' - GTSCHUNDER')
@section('body_class', 'reader-body')
@section('hide_navbar', 'true')

@section('content')
<!-- Reader Floating Header -->
<header class="reader-header" id="readerHeader">
    <div style="display: flex; align-items: center; gap: 14px;">
        <a href="{{ route('comics.show', $comic->slug) }}" class="btn btn-outline btn-sm" title="Trở về trang truyện">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <div class="reader-comic-title">
            <span style="color: var(--text-dim);">{{ $comic->title }}</span>
            <span style="color: var(--primary-light); margin-left: 6px; font-weight: 800;">Chap {{ $chapter->formatted_number }}</span>
        </div>
    </div>

    <div class="reader-controls">
        <!-- Quick Chapter Switcher -->
        <select class="reader-select" id="chapterSwitcher">
            @foreach($allChapters as $c)
                <option value="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $c->chapter_number]) }}" {{ $c->id === $chapter->id ? 'selected' : '' }}>
                    Chap {{ $c->formatted_number }}{{ $c->title ? ' - ' . Str::limit($c->title, 20) : '' }}
                </option>
            @endforeach
        </select>

        <!-- Width Options -->
        <select class="reader-select" id="widthOption" title="Độ rộng khung đọc">
            <option value="width-narrow">Hẹp (680px)</option>
            <option value="width-standard" selected>Vừa (840px)</option>
            <option value="width-wide">Rộng (1000px)</option>
            <option value="width-full">Toàn màn hình</option>
        </select>

        <!-- Prev / Next Chapter -->
        @if($prevChapter)
            <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $prevChapter->chapter_number]) }}" class="btn btn-outline btn-sm" title="Chapter trước (Phím ←)">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
        @else
            <button class="btn btn-outline btn-sm" disabled style="opacity: 0.4;"><i class="fa-solid fa-chevron-left"></i></button>
        @endif

        @if($nextChapter)
            <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $nextChapter->chapter_number]) }}" class="btn btn-primary btn-sm" title="Chapter sau (Phím →)">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        @else
            <button class="btn btn-outline btn-sm" disabled style="opacity: 0.4;"><i class="fa-solid fa-chevron-right"></i></button>
        @endif
    </div>
</header>

<!-- Main Webtoon Reading Area -->
<div class="reader-content-area" id="readerContainer">
    @if($chapter->pages->isEmpty())
        <div style="text-align: center; padding: 100px 20px; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color); margin: 40px auto; max-width: 600px;">
            <i class="fa-solid fa-image" style="font-size: 3rem; color: var(--text-dim); margin-bottom: 12px;"></i>
            <h3 style="font-size: 1.2rem; margin-bottom: 8px;">Chapter này chưa có trang ảnh nào</h3>
            <p style="color: var(--text-dim); font-size: 0.9rem; margin-bottom: 20px;">Vui lòng quay lại sau hoặc liên hệ Admin để cập nhật.</p>
            <a href="{{ route('comics.show', $comic->slug) }}" class="btn btn-primary">Quay lại truyện</a>
        </div>
    @else
        @foreach($chapter->pages as $page)
            <div class="webtoon-image-page">
                <img 
                    src="{{ $page->image_url }}" 
                    alt="Trang {{ $page->page_number }} - {{ $comic->title }} Chap {{ $chapter->formatted_number }}" 
                    class="webtoon-img" 
                    loading="lazy" 
                    onerror="this.onerror=null;this.src='{{ asset('images/default.png') }}'"
                >
            </div>
        @endforeach
    @endif

    <!-- Bottom Navigation -->
    <div class="reader-bottom-nav">
        @if($prevChapter)
            <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $prevChapter->chapter_number]) }}" class="btn btn-outline btn-lg">
                Chap {{ $prevChapter->formatted_number }}
            </a>
        @endif

        <a href="{{ route('comics.show', $comic->slug) }}" class="btn btn-primary btn-lg">
            Danh sách Chap
        </a>

        @if($nextChapter)
            <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $nextChapter->chapter_number]) }}" class="btn btn-accent btn-lg">
                Chap {{ $nextChapter->formatted_number }}
            </a>
        @endif
    </div>

    <!-- Chapter Comments -->
    <div class="comments-section" style="margin-top: 40px;">
        <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 20px;">
            Thảo Luận Chapter {{ $chapter->formatted_number }}
        </h3>

        @auth
            <form id="chapterCommentForm" class="comment-input-box">
                @csrf
                <input type="hidden" name="comic_id" value="{{ $comic->id }}">
                <input type="hidden" name="chapter_id" value="{{ $chapter->id }}">
                <textarea name="content" id="chapterCommentText" class="comment-textarea" placeholder="Bình luận về chapter này..."></textarea>
                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" id="chapterCommentBtn">
                        Gửi bình luận
                    </button>
                </div>
            </form>
        @else
            <div style="background: var(--bg-input); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px; text-align: center; margin-bottom: 20px;">
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    Vui lòng <a href="{{ route('login') }}" style="color: var(--primary-light); font-weight: 700; text-decoration: underline;">Đăng nhập</a> để bình luận.
                </p>
            </div>
        @endauth

        <div class="comment-list" id="chapterCommentList">
            @forelse($chapter->comments as $comment)
                <div class="comment-card" id="comment-{{ $comment->id }}">
                    <img src="{{ $comment->user->avatar_url }}" alt="{{ $comment->user->name }}" class="comment-user-avatar">
                    <div class="comment-body">
                        <div class="comment-header">
                            <span class="comment-username">{{ $comment->user->name }}</span>
                            <span class="comment-time">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="comment-content">{{ $comment->content }}</p>
                    </div>
                </div>
            @empty
                <p id="noChapterComments" style="color: var(--text-dim); text-align: center; padding: 20px;">Chưa có bình luận nào cho chapter này.</p>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Chapter Switcher Select
    const switcher = document.getElementById('chapterSwitcher');
    if (switcher) {
        switcher.addEventListener('change', function() {
            window.location.href = this.value;
        });
    }

    // Width Adjuster
    const widthSelect = document.getElementById('widthOption');
    const readerContainer = document.getElementById('readerContainer');
    if (widthSelect && readerContainer) {
        const savedWidth = localStorage.getItem('reader_width') || 'width-standard';
        readerContainer.classList.remove('width-narrow', 'width-standard', 'width-wide', 'width-full');
        readerContainer.classList.add(savedWidth);
        widthSelect.value = savedWidth;

        widthSelect.addEventListener('change', function() {
            readerContainer.classList.remove('width-narrow', 'width-standard', 'width-wide', 'width-full');
            readerContainer.classList.add(this.value);
            localStorage.setItem('reader_width', this.value);
        });
    }

    // Keyboard Shortcuts (Arrow Left/Right)
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        @if($prevChapter)
            if (e.key === 'ArrowLeft') {
                window.location.href = "{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $prevChapter->chapter_number]) }}";
            }
        @endif
        @if($nextChapter)
            if (e.key === 'ArrowRight') {
                window.location.href = "{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $nextChapter->chapter_number]) }}";
            }
        @endif
    });

    // Auto-hide Topbar on Scroll Down, Show on Scroll Up
    let lastScrollTop = 0;
    const header = document.getElementById('readerHeader');
    window.addEventListener('scroll', function() {
        let st = window.pageYOffset || document.documentElement.scrollTop;
        if (st > lastScrollTop && st > 100) {
            header.classList.add('hide');
        } else {
            header.classList.remove('hide');
        }
        lastScrollTop = st <= 0 ? 0 : st;
    }, false);

    // Chapter Comment Submit
    const chapCommentForm = document.getElementById('chapterCommentForm');
    if (chapCommentForm) {
        chapCommentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const text = document.getElementById('chapterCommentText').value.trim();
            if (!text) return;

            const btn = document.getElementById('chapterCommentBtn');
            btn.disabled = true;

            const formData = new FormData(chapCommentForm);

            fetch(`{{ route('comments.store') }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                if (data.error) {
                    alert(data.error);
                    return;
                }
                document.getElementById('chapterCommentText').value = '';
                const emptyMsg = document.getElementById('noChapterComments');
                if (emptyMsg) emptyMsg.remove();

                const newCard = document.createElement('div');
                newCard.className = 'comment-card';
                newCard.innerHTML = `
                    <img src="${data.comment.user_avatar}" alt="${data.comment.user_name}" class="comment-user-avatar">
                    <div class="comment-body">
                        <div class="comment-header">
                            <span class="comment-username">${data.comment.user_name}</span>
                            <span class="comment-time">${data.comment.created_at}</span>
                        </div>
                        <p class="comment-content">${data.comment.content}</p>
                    </div>
                `;
                document.getElementById('chapterCommentList').prepend(newCard);
            })
            .catch(err => {
                btn.disabled = false;
                console.error(err);
            });
        });
    }
</script>
@endpush
@endsection
