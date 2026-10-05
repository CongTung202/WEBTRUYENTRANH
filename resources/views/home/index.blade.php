@extends('layouts.app')

@section('title', 'GTSCHUNDER - Đọc Truyện Tranh Webtoon, Manhwa, Manga Online Miễn Phí')

@section('content')
<div class="container">

    <!-- Featured Comics Wide Banner Carousel (Image 2 Style) -->
    @if($featuredComics->isNotEmpty())
    <section class="featured-banner-section">
        <div class="featured-carousel" id="featuredCarousel">
            <div class="featured-viewport">
                <div class="featured-track" id="featuredTrack">
                    @foreach($featuredComics as $index => $comic)
                    <div class="featured-slide {{ $index === 0 ? 'is-active' : '' }}" data-index="{{ $index }}">
                        <a href="{{ route('comics.show', $comic->slug) }}" class="featured-slide-card">
                            <img src="{{ $comic->banner_url }}" alt="{{ $comic->title }}" class="featured-slide-bg" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" onerror="this.src='{{ asset('images/default.png') }}'">
                            <div class="featured-slide-overlay"></div>
                            
                            <div class="featured-slide-content">
                                <div class="featured-slide-info">
                                    <h2 class="featured-slide-title">{{ $comic->title }}</h2>
                                    <p class="featured-slide-desc">
                                        @if($comic->description)
                                            {{ Str::limit(strip_tags($comic->description), 110) }}
                                        @else
                                            {{ $comic->latestChapter ? 'Chap ' . $comic->latestChapter->formatted_number : 'Mới cập nhật' }} &bull; {{ $comic->genres->pluck('name')->take(3)->implode(', ') }}
                                        @endif
                                    </p>
                                </div>
                                <span class="btn-featured-action">
                                    XEM THÔNG TIN
                                </span>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Navigation Buttons -->
            @if($featuredComics->count() > 1)
            <button type="button" class="featured-nav-btn prev" id="featuredPrev" aria-label="Slide trước">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
            <button type="button" class="featured-nav-btn next" id="featuredNext" aria-label="Slide tiếp theo">
                <i class="fa-solid fa-chevron-right"></i>
            </button>

            <!-- Pagination Dots -->
            <div class="featured-pagination" id="featuredPagination">
                @foreach($featuredComics as $index => $comic)
                <button type="button" class="featured-dot {{ $index === 0 ? 'active' : '' }}" data-slide="{{ $index }}" aria-label="Slide {{ $index + 1 }}"></button>
                @endforeach
            </div>
            @endif
        </div>
    </section>
    @endif

    <!-- Recently Read Bar (For Logged in Users with history) -->
    @if($recentHistories->isNotEmpty())
    <section style="margin: 20px 0 10px; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 18px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--primary-light);">
                Truyện bạn vừa đọc gần đây
            </h4>
            <a href="{{ route('history.index') }}" style="font-size: 0.82rem; color: var(--text-dim);">Xem tất cả</a>
        </div>
        <div style="display: flex; gap: 14px; overflow-x: auto; padding-bottom: 4px;">
            @foreach($recentHistories as $history)
                <a href="{{ route('reader.read', ['comicSlug' => $history->comic->slug, 'chapterNumber' => $history->chapter->chapter_number]) }}" style="display: flex; align-items: center; gap: 10px; background: var(--bg-input); padding: 6px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); flex-shrink: 0;">
                    <img src="{{ $history->comic->cover_url }}" alt="{{ $history->comic->title }}" style="width: 32px; height: 42px; object-fit: cover; border-radius: 4px;" loading="lazy" onerror="this.src='{{ asset('images/default.png') }}'">
                    <div>
                        <p style="font-size: 0.85rem; font-weight: 700; max-width: 140px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $history->comic->title }}</p>
                        <p style="font-size: 0.75rem; color: var(--primary-light);">Đọc tiếp Chap {{ $history->chapter->formatted_number }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    <!-- Main Content Area: Left Grid + Right Top Rankings -->
    <div class="home-layout" style="margin-top: 24px;">
        
        <!-- Left: Latest Updated Comics -->
        <div>
            <div class="section-header">
                <h2 class="section-title">
                    Truyện Mới Cập Nhật
                </h2>
                <a href="{{ route('comics.index') }}" class="view-more-link">
                    Xem tất cả
                </a>
            </div>

            @if($latestComics->isEmpty())
                <div style="text-align: center; padding: 50px 20px; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px solid var(--border-color);">
                    <h3 style="font-size: 1.2rem; margin-bottom: 8px;">Chưa có truyện nào trong hệ thống</h3>
                    <p style="color: var(--text-dim); font-size: 0.9rem;">Hãy dùng Tool Đăng Chapter để tạo bộ truyện đầu tiên!</p>
                </div>
            @else
                <div class="comics-grid">
                    @foreach($latestComics as $comic)
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
            @endif
        </div>

        <!-- Right Sidebar: Rankings & Genre Cloud -->
        <aside>
            <!-- Rankings Box (Top all-time views) -->
            <div class="rankings-widget">
                <div class="rankings-header">
                    <h3 class="rankings-title">
                        <i class="fa-solid fa-fire-flame-curved" style="color: #f59e0b;"></i>
                        Top Bảng Xếp Hạng
                    </h3>
                </div>

                <div class="rankings-list">
                    @forelse($topComics as $index => $comic)
                        <div class="rank-item">
                            <span class="rank-num rank-{{ $index + 1 }}">{{ $index + 1 }}</span>
                            <a href="{{ route('comics.show', $comic->slug) }}" class="rank-thumb-link">
                                <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" class="rank-thumb" loading="lazy" onerror="this.src='{{ asset('images/default.png') }}'">
                            </a>
                            <div class="rank-info">
                                <a href="{{ route('comics.show', $comic->slug) }}" class="rank-title" title="{{ $comic->title }}">
                                    {{ $comic->title }}
                                </a>
                                <div class="rank-meta">
                                    @if($comic->latestChapter)
                                        <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $comic->latestChapter->chapter_number]) }}" class="rank-chap-link">
                                            Chap {{ $comic->latestChapter->formatted_number }}
                                        </a>
                                    @else
                                        <span class="rank-chap-link" style="opacity: 0.6; pointer-events: none;">Đang cập nhật</span>
                                    @endif
                                    <span class="rank-views">
                                        <i class="fa-solid fa-eye" style="font-size: 0.72rem; margin-right: 4px;"></i>{{ $comic->formatted_views }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p style="color: var(--text-dim); font-size: 0.88rem; text-align: center; padding: 20px 0;">Chưa có dữ liệu bảng xếp hạng</p>
                    @endforelse
                </div>
            </div>

            <!-- Genre Cloud -->
            <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 18px; margin-top: 24px;">
                <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 14px;">
                    Thể Loại Nổi Bật
                </h3>
                <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                    @foreach($genres as $genre)
                        <a href="{{ route('comics.index', ['genre' => $genre->slug]) }}" class="genre-tag">
                            {{ $genre->name }} <span style="font-size: 0.75rem; color: var(--text-dim);">({{ $genre->comics_count }})</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>

</div>

@push('scripts')
<script>
    // Featured Banner Carousel (Infinite Loop, Always 2 Visible Sides & Touchpad 2-finger scroll)
    document.addEventListener('DOMContentLoaded', function () {
        const carousel = document.getElementById('featuredCarousel');
        if (!carousel) return;

        const viewport = carousel.querySelector('.featured-viewport');
        const track = document.getElementById('featuredTrack');
        const originalSlides = Array.from(track.querySelectorAll('.featured-slide'));
        const dots = Array.from(document.querySelectorAll('.featured-dot'));
        const prevBtn = document.getElementById('featuredPrev');
        const nextBtn = document.getElementById('featuredNext');

        const realCount = originalSlides.length;
        if (realCount === 0) return;

        // Clone slides on both ends for infinite looping (so both sides always show a comic)
        const cloneCount = Math.min(2, realCount);
        if (realCount >= 2) {
            // Append clone of first slides to end
            for (let i = 0; i < cloneCount; i++) {
                const clone = originalSlides[i].cloneNode(true);
                clone.classList.remove('is-active');
                clone.setAttribute('data-cloned', 'end');
                track.appendChild(clone);
            }
            // Prepend clone of last slides to start
            for (let i = realCount - 1; i >= realCount - cloneCount; i--) {
                const clone = originalSlides[i].cloneNode(true);
                clone.classList.remove('is-active');
                clone.setAttribute('data-cloned', 'start');
                track.insertBefore(clone, track.firstChild);
            }
        }

        let allSlides = Array.from(track.querySelectorAll('.featured-slide'));
        let currentIndex = realCount >= 2 ? cloneCount : 0;
        let isTransitioning = false;
        let slideInterval = null;

        function getOffsetForIndex(idx) {
            const slide = allSlides[idx];
            if (!slide) return 0;
            const viewportCenter = viewport.offsetWidth / 2;
            const slideCenter = slide.offsetLeft + (slide.offsetWidth / 2);
            return viewportCenter - slideCenter;
        }

        function updateSlidePosition(smooth = true) {
            const targetOffset = getOffsetForIndex(currentIndex);
            track.style.transition = smooth ? 'transform 0.45s cubic-bezier(0.22, 1, 0.36, 1)' : 'none';
            track.style.transform = `translateX(${targetOffset}px)`;

            let realIndex = 0;
            if (realCount >= 2) {
                realIndex = ((currentIndex - cloneCount) % realCount + realCount) % realCount;
            } else {
                realIndex = currentIndex;
            }

            dots.forEach((dot, i) => {
                dot.classList.toggle('active', i === realIndex);
            });
            allSlides.forEach((s, i) => {
                s.classList.toggle('is-active', i === currentIndex);
            });
        }

        // Infinite loop wrap jump after transition
        track.addEventListener('transitionend', function () {
            isTransitioning = false;
            if (realCount >= 2) {
                if (currentIndex >= realCount + cloneCount) {
                    currentIndex = currentIndex - realCount;
                    updateSlidePosition(false);
                } else if (currentIndex < cloneCount) {
                    currentIndex = currentIndex + realCount;
                    updateSlidePosition(false);
                }
            }
        });

        function goToSlide(index) {
            if (isTransitioning) return;
            isTransitioning = true;
            currentIndex = index;
            updateSlidePosition(true);
        }

        function nextSlide() {
            if (isTransitioning) return;
            goToSlide(currentIndex + 1);
        }

        function prevSlide() {
            if (isTransitioning) return;
            goToSlide(currentIndex - 1);
        }

        function startAutoplay() {
            stopAutoplay();
            if (realCount > 1) {
                slideInterval = setInterval(nextSlide, 5000);
            }
        }

        function stopAutoplay() {
            if (slideInterval) {
                clearInterval(slideInterval);
                slideInterval = null;
            }
        }

        if (prevBtn) prevBtn.addEventListener('click', (e) => { e.preventDefault(); prevSlide(); startAutoplay(); });
        if (nextBtn) nextBtn.addEventListener('click', (e) => { e.preventDefault(); nextSlide(); startAutoplay(); });

        dots.forEach(dot => {
            dot.addEventListener('click', function (e) {
                e.preventDefault();
                const targetReal = parseInt(this.getAttribute('data-slide'));
                goToSlide(realCount >= 2 ? targetReal + cloneCount : targetReal);
                startAutoplay();
            });
        });

        // 2-Finger Touchpad Horizontal Scroll Gesture (Wheel event)
        let accumulatedDeltaX = 0;
        let wheelTimeout = null;
        let isWheelLocked = false;

        viewport.addEventListener('wheel', function (e) {
            if (Math.abs(e.deltaX) > Math.abs(e.deltaY) && Math.abs(e.deltaX) > 6) {
                e.preventDefault();
                stopAutoplay();

                if (isWheelLocked || isTransitioning) return;

                accumulatedDeltaX += e.deltaX;

                if (Math.abs(accumulatedDeltaX) >= 30) {
                    isWheelLocked = true;
                    if (accumulatedDeltaX > 0) {
                        nextSlide();
                    } else {
                        prevSlide();
                    }
                    accumulatedDeltaX = 0;
                    setTimeout(() => {
                        isWheelLocked = false;
                    }, 460);
                }

                clearTimeout(wheelTimeout);
                wheelTimeout = setTimeout(() => {
                    accumulatedDeltaX = 0;
                    startAutoplay();
                }, 350);
            }
        }, { passive: false });

        // Touchscreen support for mobile
        let touchStartX = 0;
        let touchEndX = 0;

        viewport.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
            stopAutoplay();
        }, { passive: true });

        viewport.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].clientX;
            const diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 40) {
                if (diff > 0) nextSlide();
                else prevSlide();
            }
            startAutoplay();
        }, { passive: true });

        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);

        window.addEventListener('resize', () => updateSlidePosition(false));

        updateSlidePosition(false);
        startAutoplay();
    });
</script>
@endpush
@endsection
