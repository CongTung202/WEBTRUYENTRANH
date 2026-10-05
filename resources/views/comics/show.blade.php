@extends('layouts.app')

@section('title', $comic->title . ' - Đọc Full Chapter Chất Lượng Cao | GTSCHUNDER')
@section('meta_description', Str::limit(strip_tags($comic->description), 150))

@section('content')
<div class="container">

    <!-- Comic Hero Info Box -->
    <div class="comic-detail-hero">
        <!-- Blurred Background Cover/Banner (Facebook Profile Style) -->
        <div class="comic-hero-backdrop-container">
            <img src="{{ $comic->banner_url }}" alt="{{ $comic->title }}" class="comic-hero-backdrop-img" onerror="this.src='{{ $comic->cover_url }}'">
            <div class="comic-hero-backdrop-overlay"></div>
        </div>

        <div class="comic-detail-wrapper">
            <!-- Cover with Zoom Trigger -->
            <div>
                <div class="comic-cover-container" id="coverContainer" title="Zoom ảnh bìa">
                    <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" class="comic-detail-cover" id="comicCoverImg" onerror="this.src='{{ asset('images/default.png') }}'">
                    <div class="comic-cover-zoom-hint">
                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                    </div>
                </div>
            </div>

            <!-- Details -->
            <div class="comic-detail-info">
                <h1>{{ $comic->title }}</h1>
                @if($comic->other_names)
                    <p class="comic-alt-names">Tên khác: {{ $comic->other_names }}</p>
                @endif

                <div class="comic-genres-list">
                    @foreach($comic->genres as $genre)
                        <a href="{{ route('comics.index', ['genre' => $genre->slug]) }}" class="genre-tag">
                            {{ $genre->name }}
                        </a>
                    @endforeach
                </div>

                <div class="comic-meta-grid">
                    <div class="comic-meta-item">
                        <span class="comic-meta-label">Tác giả:</span>
                        <span style="color: #fff; font-weight: 600;">{{ $comic->author ?: 'Đang cập nhật' }}</span>
                    </div>
                    <div class="comic-meta-item">
                        <span class="comic-meta-label">Tình trạng:</span>
                        <span class="badge {{ $comic->status === 'completed' ? 'badge-completed' : 'badge-ongoing' }}">
                            {{ $comic->status_label }}
                        </span>
                    </div>
                    <div class="comic-meta-item">
                        <span class="comic-meta-label">Lượt xem:</span>
                        <span style="color: #fff; font-weight: 600;">{{ number_format($comic->views) }}</span>
                    </div>
                </div>

                <!-- Rating Box -->
                <div class="rating-box">
                    <div class="star-rating" id="starRatingWidget" data-comic-id="{{ $comic->id }}">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="star {{ ($userRating && $i <= $userRating) || (!$userRating && $i <= round($comic->rating_score)) ? 'active' : '' }}" data-score="{{ $i }}">&#9733;</span>
                        @endfor
                    </div>
                    <span style="font-weight: 700; color: #fff; font-size: 0.95rem;" id="ratingScoreDisplay">{{ number_format($comic->rating_score, 1) }}</span>
                    <span style="color: var(--text-dim); font-size: 0.85rem;" id="ratingCountDisplay">({{ $comic->rating_count }} đánh giá)</span>
                </div>

                <!-- Action Buttons -->
                <div class="comic-action-buttons">
                    @if($firstChapter)
                        <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $firstChapter->chapter_number]) }}" class="btn btn-primary btn-lg">
                            Đọc từ đầu
                        </a>
                    @endif

                    @if($lastReadHistory && $lastReadHistory->chapter)
                        <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $lastReadHistory->chapter->chapter_number]) }}" class="btn btn-accent btn-lg">
                            Đọc tiếp Chap {{ $lastReadHistory->chapter->formatted_number }}
                        </a>
                    @endif

                    @if($latestChapter)
                        <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $latestChapter->chapter_number]) }}" class="btn btn-outline btn-lg">
                            Chap mới nhất ({{ $latestChapter->formatted_number }})
                        </a>
                    @endif

                    <!-- Bookmark Button -->
                    <button class="btn btn-outline btn-lg" id="bookmarkBtn" data-comic-id="{{ $comic->id }}" style="{{ $isBookmarked ? 'background: rgba(239, 68, 68, 0.15); border-color: #ef4444; color: #f87171;' : '' }}">
                        <span id="bookmarkText">{{ $isBookmarked ? 'Đang theo dõi' : 'Theo dõi truyện' }}</span>
                    </button>

                    <!-- Copy Link Button -->
                    <button type="button" class="btn btn-outline btn-lg" id="copyComicLinkBtn" title="Sao chép link truyện">
                        <i class="fa-regular fa-copy" id="copyIcon"></i>
                        <span id="copyLinkText">Sao chép link</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Synopsis -->
    <div class="comic-synopsis">
        <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 12px;">
            Tóm Tắt Nội Dung
        </h3>
        <p>{!! nl2br(e($comic->description ?: 'Nội dung truyện đang được cập nhật...')) !!}</p>
    </div>

    <!-- Chapters List -->
    <div class="chapter-list-section">
        <div class="chapter-filter-bar">
            <h3 style="font-size: 1.2rem; font-weight: 800;">
                Danh Sách Chapter 
                <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-dim);">({{ $comic->chapters->count() }} chap)</span>
            </h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <input type="text" id="chapterSearch" placeholder="Tìm số chapter..." class="chapter-search-input">
                <button class="btn btn-outline btn-sm" id="toggleSortBtn" title="Đảo thứ tự sắp xếp">
                    Đảo thứ tự
                </button>
            </div>
        </div>

        @if($comic->chapters->isEmpty())
            <p style="color: var(--text-dim); text-align: center; padding: 30px;">Chưa có chapter nào được đăng.</p>
        @else
            <div class="chapters-grid-view" id="chaptersContainer">
                @foreach($comic->chapters as $chap)
                    <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $chap->chapter_number]) }}" class="chapter-row-btn" data-chap-num="{{ $chap->chapter_number }}">
                        <span>Chap {{ $chap->formatted_number }}</span>
                        <span style="font-size: 0.75rem; color: var(--text-dim);">{{ $chap->created_at->format('d/m/Y') }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Comments Section -->
    <div class="comments-section">
        <h3 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 20px;">
            Bình Luận 
            <span style="font-size: 0.9rem; font-weight: normal; color: var(--text-dim);" id="commentsCount">({{ $comic->comments->count() }})</span>
        </h3>

        @auth
            <form id="commentForm" class="comment-input-box">
                @csrf
                <input type="hidden" name="comic_id" value="{{ $comic->id }}">
                <textarea name="content" id="commentContent" class="comment-textarea" placeholder="Chia sẻ cảm nghĩ của bạn về bộ truyện này... (Tôn trọng cộng đồng, không spoil nhé)"></textarea>
                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" id="submitCommentBtn">
                        Gửi bình luận
                    </button>
                </div>
            </form>
        @else
            <div style="background: var(--bg-input); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px; text-align: center; margin-bottom: 24px;">
                <p style="color: var(--text-muted); font-size: 0.92rem;">
                    Vui lòng <a href="{{ route('login') }}" style="color: var(--primary-light); font-weight: 700; text-decoration: underline;">Đăng nhập</a> hoặc <a href="{{ route('register') }}" style="color: var(--primary-light); font-weight: 700; text-decoration: underline;">Đăng ký</a> để tham gia thảo luận cùng cộng đồng độc giả!
                </p>
            </div>
        @endauth

        <div class="comment-list" id="commentList">
            @forelse($comic->comments as $comment)
                <div class="comment-card" id="comment-{{ $comment->id }}">
                    <img src="{{ $comment->user->avatar_url }}" alt="{{ $comment->user->name }}" class="comment-user-avatar">
                    <div class="comment-body">
                        <div class="comment-header">
                            <span class="comment-username">{{ $comment->user->name }}</span>
                            <span class="comment-time">{{ $comment->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="comment-content">{{ $comment->content }}</p>
                        <div class="comment-actions">
                            <button class="comment-action-btn like-comment-btn" data-comment-id="{{ $comment->id }}">
                                <span class="like-count">{{ $comment->likes }}</span> Thích
                            </button>
                            @if(auth()->check() && (auth()->id() === $comment->user_id || auth()->user()->isAdmin()))
                                <button class="comment-action-btn delete-comment-btn" data-comment-id="{{ $comment->id }}" style="color: var(--accent-red);">
                                    Xóa
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p id="noCommentsText" style="color: var(--text-dim); text-align: center; padding: 20px;">Hãy là người đầu tiên bình luận cho bộ truyện này!</p>
            @endforelse
        </div>
    </div>

    <!-- Related Comics -->
    @if($relatedComics->isNotEmpty())
        <div style="margin-bottom: 40px;">
            <div class="section-header">
                <h3 class="section-title">
                    Truyện Cùng Thể Loại
                </h3>
            </div>
            <div class="comics-grid">
                @foreach($relatedComics as $related)
                    <div class="comic-card">
                        <div class="comic-thumb-wrap">
                            <a href="{{ route('comics.show', $related->slug) }}" class="comic-thumb-link">
                                <img src="{{ $related->cover_url }}" alt="{{ $related->title }}" class="comic-thumb" loading="lazy" onerror="this.src='{{ asset('images/default.png') }}'">
                            </a>
                            <span class="comic-status-badge badge {{ $related->status === 'completed' ? 'badge-completed' : 'badge-ongoing' }}">
                                {{ $related->status_label }}
                            </span>
                            <div class="comic-thumb-overlay">
                                <a href="{{ route('comics.show', $related->slug) }}" class="comic-title-in-thumb" title="{{ $related->title }}">
                                    {{ $related->title }}
                                </a>
                                @if($related->latestChapter)
                                    <a href="{{ route('reader.read', ['comicSlug' => $related->slug, 'chapterNumber' => $related->latestChapter->chapter_number]) }}" class="comic-thumb-chap-row">
                                        <span class="chap-num">Chap {{ $related->latestChapter->formatted_number }}</span>
                                        <span class="chap-time">{{ $related->updated_time_formatted }}</span>
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
        </div>
    @endif

    <!-- Image Zoom Lightbox Modal -->
    <div id="imageLightboxModal" class="image-lightbox-modal" style="display: none;">
        <div class="lightbox-backdrop" id="lightboxBackdrop"></div>
        <div class="lightbox-dialog">
            <div class="lightbox-header">
                <h4 class="lightbox-title">{{ $comic->title }}</h4>
                <div class="lightbox-controls">
                    <button type="button" class="lightbox-btn" id="lightboxZoomIn" title="Phóng to"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                    <button type="button" class="lightbox-btn" id="lightboxZoomOut" title="Thu nhỏ"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                    <button type="button" class="lightbox-btn" id="lightboxReset" title="Kích thước mặc định"><i class="fa-solid fa-arrows-rotate"></i></button>
                    <button type="button" class="lightbox-close-btn" id="lightboxClose" title="Đóng (Esc)"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div class="lightbox-body" id="lightboxBody">
                <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" id="lightboxImg" class="lightbox-image" onerror="this.src='{{ asset('images/default.png') }}'">
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="app-toast-container" id="appToastContainer"></div>

</div>
@endsection

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Interactive Star Rating
    const starWidget = document.getElementById('starRatingWidget');
    if (starWidget) {
        const stars = starWidget.querySelectorAll('.star');
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const score = this.dataset.score;
                const comicId = starWidget.dataset.comicId;

                fetch(`/truyen/${comicId}/rate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ score: score })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    stars.forEach(s => {
                        s.classList.toggle('active', parseInt(s.dataset.score) <= parseInt(score));
                    });
                    document.getElementById('ratingScoreDisplay').innerText = data.rating_score.toFixed(1);
                    document.getElementById('ratingCountDisplay').innerText = `(${data.rating_count} đánh giá)`;
                })
                .catch(err => console.error(err));
            });
        });
    }

    // Bookmark Toggle AJAX
    const bookmarkBtn = document.getElementById('bookmarkBtn');
    if (bookmarkBtn) {
        bookmarkBtn.addEventListener('click', function() {
            const comicId = this.dataset.comicId;
            fetch(`/truyen/${comicId}/bookmark`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    return;
                }
                const text = document.getElementById('bookmarkText');
                if (data.bookmarked) {
                    bookmarkBtn.style.background = 'rgba(239, 68, 68, 0.15)';
                    bookmarkBtn.style.borderColor = '#ef4444';
                    bookmarkBtn.style.color = '#f87171';
                    text.innerText = 'Đang theo dõi';
                } else {
                    bookmarkBtn.style.background = '';
                    bookmarkBtn.style.borderColor = '';
                    bookmarkBtn.style.color = '';
                    text.innerText = 'Theo dõi truyện';
                }
            });
        });
    }

    // Chapter Search & Sort
    const chapterSearch = document.getElementById('chapterSearch');
    if (chapterSearch) {
        chapterSearch.addEventListener('input', function() {
            const val = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.chapter-row-btn');
            rows.forEach(r => {
                const text = r.querySelector('span').innerText.toLowerCase();
                r.style.display = text.includes(val) ? 'flex' : 'none';
            });
        });
    }

    let isAsc = false;
    const toggleSortBtn = document.getElementById('toggleSortBtn');
    if (toggleSortBtn) {
        toggleSortBtn.addEventListener('click', function() {
            const container = document.getElementById('chaptersContainer');
            const rows = Array.from(container.children);
            rows.reverse();
            rows.forEach(r => container.appendChild(r));
            isAsc = !isAsc;
            toggleSortBtn.innerText = isAsc ? 'Thứ tự: Cũ nhất' : 'Thứ tự: Mới nhất';
        });
    }

    // Comment Form Submission
    const commentForm = document.getElementById('commentForm');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const content = document.getElementById('commentContent').value.trim();
            if (!content) return;

            const submitBtn = document.getElementById('submitCommentBtn');
            submitBtn.disabled = true;

            fetch('{{ route('comments.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    comic_id: '{{ $comic->id }}',
                    content: content
                })
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                if (data.error) {
                    alert(data.error);
                    return;
                }

                document.getElementById('commentContent').value = '';
                const noComments = document.getElementById('noCommentsText');
                if (noComments) noComments.remove();

                const newCard = document.createElement('div');
                newCard.className = 'comment-card';
                newCard.id = `comment-${data.comment.id}`;
                newCard.innerHTML = `
                    <img src="${data.comment.user.avatar_url}" alt="${data.comment.user.name}" class="comment-user-avatar">
                    <div class="comment-body">
                        <div class="comment-header">
                            <span class="comment-username">${data.comment.user.name}</span>
                            <span class="comment-time">Vừa xong</span>
                        </div>
                        <p class="comment-content">${data.comment.content}</p>
                        <div class="comment-actions">
                            <button class="comment-action-btn like-comment-btn" data-comment-id="${data.comment.id}">
                                <span class="like-count">0</span> Thích
                            </button>
                            <button class="comment-action-btn delete-comment-btn" data-comment-id="${data.comment.id}" style="color: var(--accent-red);">
                                Xóa
                            </button>
                        </div>
                    </div>
                `;
                document.getElementById('commentList').prepend(newCard);
            })
            .catch(err => {
                submitBtn.disabled = false;
                console.error(err);
            });
        });
    }

    // Like & Delete Comment Delegation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.like-comment-btn')) {
            const btn = e.target.closest('.like-comment-btn');
            const commentId = btn.dataset.commentId;
            fetch(`/comment/${commentId}/like`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    btn.querySelector('.like-count').innerText = data.likes;
                    btn.style.color = 'var(--primary-light)';
                }
            });
        }

        if (e.target.closest('.delete-comment-btn')) {
            if (!confirm('Bạn có chắc chắn muốn xóa bình luận này?')) return;
            const btn = e.target.closest('.delete-comment-btn');
            const commentId = btn.dataset.commentId;
            fetch(`/comment/${commentId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const el = document.getElementById(`comment-${commentId}`);
                    if (el) el.remove();
                }
            });
        }
    });

    // Image Zoom Lightbox Logic
    const coverContainer = document.getElementById('coverContainer');
    const lightboxModal = document.getElementById('imageLightboxModal');
    const lightboxBackdrop = document.getElementById('lightboxBackdrop');
    const lightboxClose = document.getElementById('lightboxClose');
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxZoomIn = document.getElementById('lightboxZoomIn');
    const lightboxZoomOut = document.getElementById('lightboxZoomOut');
    const lightboxReset = document.getElementById('lightboxReset');

    let currentScale = 1;

    function openLightbox() {
        if (!lightboxModal) return;
        currentScale = 1;
        if (lightboxImg) {
            lightboxImg.style.transform = 'scale(1)';
            lightboxImg.classList.remove('is-zoomed');
        }
        lightboxModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        if (!lightboxModal) return;
        lightboxModal.style.display = 'none';
        document.body.style.overflow = '';
        currentScale = 1;
        if (lightboxImg) {
            lightboxImg.style.transform = 'scale(1)';
            lightboxImg.classList.remove('is-zoomed');
        }
    }

    if (coverContainer) {
        coverContainer.addEventListener('click', openLightbox);
    }

    if (lightboxBackdrop) lightboxBackdrop.addEventListener('click', closeLightbox);
    if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && lightboxModal && lightboxModal.style.display !== 'none') {
            closeLightbox();
        }
    });

    if (lightboxImg) {
        lightboxImg.addEventListener('click', function() {
            if (currentScale === 1) {
                currentScale = 1.7;
            } else {
                currentScale = 1;
            }
            lightboxImg.style.transform = `scale(${currentScale})`;
            lightboxImg.classList.toggle('is-zoomed', currentScale > 1);
        });
    }

    if (lightboxZoomIn) {
        lightboxZoomIn.addEventListener('click', function() {
            currentScale = Math.min(currentScale + 0.3, 3);
            if (lightboxImg) {
                lightboxImg.style.transform = `scale(${currentScale})`;
                lightboxImg.classList.toggle('is-zoomed', currentScale > 1);
            }
        });
    }

    if (lightboxZoomOut) {
        lightboxZoomOut.addEventListener('click', function() {
            currentScale = Math.max(currentScale - 0.3, 0.7);
            if (lightboxImg) {
                lightboxImg.style.transform = `scale(${currentScale})`;
                lightboxImg.classList.toggle('is-zoomed', currentScale > 1);
            }
        });
    }

    if (lightboxReset) {
        lightboxReset.addEventListener('click', function() {
            currentScale = 1;
            if (lightboxImg) {
                lightboxImg.style.transform = 'scale(1)';
                lightboxImg.classList.remove('is-zoomed');
            }
        });
    }

    // Copy Comic Link Logic
    const copyBtn = document.getElementById('copyComicLinkBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', function() {
            const url = window.location.href;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(url)
                    .then(onCopySuccess)
                    .catch(() => fallbackCopy(url));
            } else {
                fallbackCopy(url);
            }
        });
    }

    function fallbackCopy(text) {
        const temp = document.createElement('textarea');
        temp.value = text;
        temp.style.position = 'fixed';
        temp.style.left = '-9999px';
        document.body.appendChild(temp);
        temp.select();
        try {
            document.execCommand('copy');
            onCopySuccess();
        } catch (e) {
            alert('Không thể sao chép liên kết.');
        }
        document.body.removeChild(temp);
    }

    function onCopySuccess() {
        const copyBtn = document.getElementById('copyComicLinkBtn');
        const copyText = document.getElementById('copyLinkText');
        const copyIcon = document.getElementById('copyIcon');

        if (copyText) copyText.innerText = 'Đã sao chép!';
        if (copyIcon) copyIcon.className = 'fa-solid fa-check';
        if (copyBtn) copyBtn.classList.add('btn-copy-success');

        showToast('Đã sao chép link truyện vào bộ nhớ tạm!');

        setTimeout(() => {
            if (copyText) copyText.innerText = 'Sao chép link';
            if (copyIcon) copyIcon.className = 'fa-regular fa-copy';
            if (copyBtn) copyBtn.classList.remove('btn-copy-success');
        }, 2500);
    }

    function showToast(message) {
        const container = document.getElementById('appToastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'app-toast';
        toast.innerHTML = `<i class="fa-solid fa-circle-check"></i> <span>${message}</span>`;
        container.appendChild(toast);

        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
</script>
@endpush
