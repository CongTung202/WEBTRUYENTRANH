@extends('layouts.app')

@section('title', 'Khám Phá & Tìm Kiếm Truyện Tranh - GTSCHUNDER')

@section('content')
<div class="container" style="padding-top: 24px; padding-bottom: 50px;">
    
    <!-- Filter Box -->
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 24px; margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 1.3rem;">
                Bộ Lọc Truyện Tranh
            </h2>
            @php
                $hasActiveFilters = (!empty($selectedGenres) || request('status') || request('q') || (request('sort') && request('sort') !== 'latest'));
            @endphp
            <button type="button" class="filter-clear-btn {{ !$hasActiveFilters ? 'is-disabled' : '' }}" id="clearAllFiltersBtn" onclick="resetAllFilters()" {{ !$hasActiveFilters ? 'disabled' : '' }}>
                <i class="fa-solid fa-rotate-left" style="font-size: 0.78rem;"></i>
                Xóa tất cả bộ lọc
            </button>
        </div>

        <form id="filterForm" onsubmit="event.preventDefault(); applyFilterAjax();">
            <input type="hidden" name="q" id="queryFilterInput" value="{{ request('q') }}">

            <!-- Genre Multi-Select Filter -->
            <div style="margin-bottom: 18px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <label style="font-size: 0.88rem; font-weight: 700; color: var(--text-dim);">THỂ LOẠI (Có thể chọn nhiều tag)</label>
                    <span id="selectedGenresCount" style="font-size: 0.82rem; color: var(--primary-light); font-weight: 600;">
                        {{ count($selectedGenres) > 0 ? 'Đã chọn: ' . count($selectedGenres) . ' thể loại' : '' }}
                    </span>
                </div>
                
                <div style="display: flex; flex-wrap: wrap; gap: 8px;" id="genresChipsContainer">
                    <button type="button" class="genre-filter-btn {{ empty($selectedGenres) ? 'active' : '' }}" id="btnAllGenres" onclick="resetGenres()">
                        Tất cả
                    </button>
                    @foreach($genres as $g)
                        @php $isSelected = in_array($g->slug, $selectedGenres); @endphp
                        <label class="genre-filter-btn {{ $isSelected ? 'active' : '' }}" id="label-{{ $g->slug }}" style="cursor: pointer;">
                            <input type="checkbox" name="genres[]" value="{{ $g->slug }}" {{ $isSelected ? 'checked' : '' }} onchange="toggleGenreChip(this, '{{ $g->slug }}')" style="display: none;">
                            <span>{{ $g->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Status & Sort Row -->
            <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: center; padding-top: 16px; border-top: 1px solid var(--border-color);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 0.88rem; font-weight: 700; color: var(--text-dim);">TÌNH TRẠNG:</span>
                    <input type="hidden" name="status" id="statusFilterInput" value="{{ request('status') }}">
                    <button type="button" class="btn btn-sm status-btn {{ !request('status') ? 'btn-primary' : 'btn-outline' }}" data-status="" onclick="setStatusFilter('')">Tất cả</button>
                    <button type="button" class="btn btn-sm status-btn {{ request('status') === 'ongoing' ? 'btn-primary' : 'btn-outline' }}" data-status="ongoing" onclick="setStatusFilter('ongoing')">Đang tiến hành</button>
                    <button type="button" class="btn btn-sm status-btn {{ request('status') === 'completed' ? 'btn-primary' : 'btn-outline' }}" data-status="completed" onclick="setStatusFilter('completed')">Đã hoàn thành</button>
                </div>

                <div style="display: flex; align-items: center; gap: 10px; margin-left: auto;">
                    <span style="font-size: 0.88rem; font-weight: 700; color: var(--text-dim);">SẮP XẾP:</span>
                    <select name="sort" id="sortSelect" onchange="onSortChange()" style="background: var(--bg-input); border: 1px solid var(--border-color); color: #fff; padding: 7px 14px; border-radius: var(--radius-sm); font-size: 0.88rem; outline: none; cursor: pointer;">
                        <option value="latest" {{ ($sort ?? request('sort', 'latest')) === 'latest' ? 'selected' : '' }}>Mới cập nhật</option>
                        <option value="newest" {{ ($sort ?? request('sort')) === 'newest' ? 'selected' : '' }}>Truyện mới đăng</option>
                        <option value="views" {{ ($sort ?? request('sort')) === 'views' ? 'selected' : '' }}>Lượt xem nhiều nhất</option>
                        <option value="rating" {{ ($sort ?? request('sort')) === 'rating' ? 'selected' : '' }}>Đánh giá cao nhất</option>
                        <option value="title" {{ ($sort ?? request('sort')) === 'title' ? 'selected' : '' }}>Tên truyện (A-Z)</option>
                        <option value="title_desc" {{ ($sort ?? request('sort')) === 'title_desc' ? 'selected' : '' }}>Tên truyện (Z-A)</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="font-size: 1.2rem; font-weight: 800;" id="resultsHeaderTitle">
            @if(request('q'))
                Kết quả tìm kiếm cho: "<span style="color: var(--primary-light);">{{ request('q') }}</span>"
            @elseif(!empty($selectedGenres))
                Thể loại: <span style="color: var(--primary-light);">{{ $genres->whereIn('slug', $selectedGenres)->pluck('name')->join(', ') }}</span>
            @else
                Danh Sách Truyện Tranh
            @endif
            <span style="font-size: 0.85rem; font-weight: normal; color: var(--text-dim);" id="resultsTotalCount">({{ $comics->total() }} bộ)</span>
        </h3>
    </div>

    <!-- AJAX Comics Container -->
    <div id="comicsAjaxWrapper" style="transition: opacity 0.2s ease;">
        @include('comics.partials.comics_grid', ['comics' => $comics])
    </div>

</div>

@push('scripts')
<script>
    let filterDebounceTimer = null;

    function updateClearFilterBtnState() {
        const checkedBoxes = document.querySelectorAll('input[name="genres[]"]:checked');
        const statusVal = document.getElementById('statusFilterInput') ? document.getElementById('statusFilterInput').value : '';
        const queryVal = document.getElementById('queryFilterInput') ? document.getElementById('queryFilterInput').value : '';
        const sortVal = document.getElementById('sortSelect') ? document.getElementById('sortSelect').value : 'latest';

        const hasFilters = (checkedBoxes.length > 0) || (statusVal !== '') || (queryVal !== '') || (sortVal !== 'latest');
        const clearBtn = document.getElementById('clearAllFiltersBtn');
        if (clearBtn) {
            if (hasFilters) {
                clearBtn.disabled = false;
                clearBtn.classList.remove('is-disabled');
            } else {
                clearBtn.disabled = true;
                clearBtn.classList.add('is-disabled');
            }
        }
    }

    function toggleGenreChip(checkbox, slug) {
        const label = document.getElementById('label-' + slug);
        if (label) {
            label.classList.toggle('active', checkbox.checked);
        }

        const checkedBoxes = document.querySelectorAll('input[name="genres[]"]:checked');
        const btnAll = document.getElementById('btnAllGenres');
        const countDisplay = document.getElementById('selectedGenresCount');

        if (checkedBoxes.length > 0) {
            btnAll.classList.remove('active');
            countDisplay.innerText = `Đã chọn: ${checkedBoxes.length} thể loại`;
        } else {
            btnAll.classList.add('active');
            countDisplay.innerText = '';
        }

        updateClearFilterBtnState();
        applyFilterAjax();
    }

    function resetGenres() {
        document.querySelectorAll('input[name="genres[]"]').forEach(cb => {
            cb.checked = false;
            const label = cb.closest('.genre-filter-btn');
            if (label) label.classList.remove('active');
        });
        document.getElementById('btnAllGenres').classList.add('active');
        document.getElementById('selectedGenresCount').innerText = '';
        updateClearFilterBtnState();
        applyFilterAjax();
    }

    function resetAllFilters() {
        document.querySelectorAll('input[name="genres[]"]').forEach(cb => {
            cb.checked = false;
            const label = cb.closest('.genre-filter-btn');
            if (label) label.classList.remove('active');
        });
        document.getElementById('btnAllGenres').classList.add('active');
        document.getElementById('selectedGenresCount').innerText = '';
        document.getElementById('queryFilterInput').value = '';
        document.getElementById('sortSelect').value = 'latest';
        
        document.getElementById('statusFilterInput').value = '';
        document.querySelectorAll('.status-btn').forEach(btn => {
            if (btn.dataset.status === '') {
                btn.classList.add('btn-primary');
                btn.classList.remove('btn-outline');
            } else {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
            }
        });

        updateClearFilterBtnState();
        applyFilterAjax();
    }

    function setStatusFilter(val) {
        document.getElementById('statusFilterInput').value = val;
        
        document.querySelectorAll('.status-btn').forEach(btn => {
            if (btn.dataset.status === val) {
                btn.classList.add('btn-primary');
                btn.classList.remove('btn-outline');
            } else {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
            }
        });

        updateClearFilterBtnState();
        applyFilterAjax();
    }

    function onSortChange() {
        updateClearFilterBtnState();
        applyFilterAjax();
    }

    function applyFilterAjax(pageUrl = null) {
        clearTimeout(filterDebounceTimer);

        filterDebounceTimer = setTimeout(() => {
            const form = document.getElementById('filterForm');
            const formData = new FormData(form);
            const params = new URLSearchParams();

            for (const [key, value] of formData.entries()) {
                if (value !== '' && value !== null && value !== undefined) {
                    params.append(key, value);
                }
            }

            let requestUrl = pageUrl || `{{ route('comics.index') }}?${params.toString()}`;

            const wrapper = document.getElementById('comicsAjaxWrapper');
            if (wrapper) wrapper.style.opacity = '0.35';

            fetch(requestUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (wrapper) {
                    wrapper.innerHTML = data.html;
                    wrapper.style.opacity = '1';
                }

                // Update results title and count
                const titleEl = document.getElementById('resultsHeaderTitle');
                if (titleEl) {
                    titleEl.innerHTML = `
                        ${data.title}
                        <span style="font-size: 0.85rem; font-weight: normal; color: var(--text-dim);" id="resultsTotalCount">(${data.total} bộ)</span>
                    `;
                }

                updateClearFilterBtnState();

                // Update Browser URL without refresh
                window.history.pushState({ path: requestUrl }, '', requestUrl);
            })
            .catch(err => {
                console.error(err);
                if (wrapper) wrapper.style.opacity = '1';
            });
        }, 120);
    }

    // Handle AJAX Pagination clicks
    document.addEventListener('click', function(e) {
        const paginationLink = e.target.closest('#comicsAjaxWrapper .pagination a');
        if (paginationLink) {
            e.preventDefault();
            const targetUrl = paginationLink.getAttribute('href');
            if (targetUrl) {
                applyFilterAjax(targetUrl);
                window.scrollTo({ top: 300, behavior: 'smooth' });
            }
        }
    });

    // Handle Browser Back/Forward buttons
    window.addEventListener('popstate', function() {
        location.reload();
    });
</script>
@endpush
@endsection
