@extends('layouts.admin')

@section('title', 'Thêm Truyện Mới - GTSCHunder Admin')
@section('page_title', 'Thêm Bộ Truyện Mới')

@section('content')
<div style="max-width: 850px; margin: 0 auto;">
    <div class="admin-card-box">
        <form action="{{ route('admin.comics.store') }}" method="POST" enctype="multipart/form-data" id="comicForm">
            @csrf

            <div class="form-group">
                <label class="form-label">Tên Truyện <span style="color: var(--admin-danger);">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required class="form-control" placeholder="Nhập tên truyện tranh...">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Tác Giả</label>
                    <input type="text" name="author" value="{{ old('author') }}" class="form-control" placeholder="Tên tác giả...">
                </div>
                <div class="form-group">
                    <label class="form-label">Tên Khác / Tên Phụ</label>
                    <input type="text" name="other_names" value="{{ old('other_names') }}" class="form-control" placeholder="Ví dụ: NaKangLim, Cinderella Gray...">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Tóm Tắt Nội Dung</label>
                <textarea name="description" rows="5" class="form-control" placeholder="Mô tả nội dung, cốt truyện...">{{ old('description') }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Tình Trạng Truyện</label>
                    <select name="status" class="form-control">
                        <option value="ongoing" {{ old('status') === 'ongoing' ? 'selected' : '' }}>Đang tiến hành</option>
                        <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                        <option value="dropped" {{ old('status') === 'dropped' ? 'selected' : '' }}>Tạm ngưng</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Ảnh Bìa Dọc (Cover Image)</label>
                    <input type="file" name="cover_image" accept="image/*" class="form-control">
                    <small style="font-size: 0.78rem; color: var(--admin-text-muted); display: block; margin-top: 4px;">Hiển thị dạng thẻ truyện (Tỉ lệ 3:4)</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Ảnh Bìa Ngang / Banner</label>
                    <input type="file" name="banner_image" accept="image/*" class="form-control">
                    <small style="font-size: 0.78rem; color: var(--admin-text-muted); display: block; margin-top: 4px;">(Nếu trống sẽ tự động lấy Ảnh bìa dọc)</small>
                </div>
            </div>

            <!-- Interactive Search & Auto-Add Tags/Genres -->
            <div class="form-group" style="background: #0d131c; padding: 18px; border-radius: 10px; border: 1px solid var(--admin-border);">
                <label class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fa-solid fa-tags" style="color: var(--admin-primary-light);"></i> Thể Loại</span>
                    <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Nhấn Enter hoặc chọn từ danh sách</span>
                </label>

                <!-- Selected Tags Container -->
                <div id="selectedTagsContainer" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; min-height: 36px; padding: 6px; background: var(--admin-card); border-radius: 8px; border: 1px dashed var(--admin-border);">
                    <span id="noTagsNotice" style="font-size: 0.82rem; color: var(--admin-text-muted); align-self: center; padding: 4px;">Chưa chọn thể loại nào...</span>
                </div>

                <!-- Hidden inputs container for form submit -->
                <div id="hiddenTagsInputs"></div>

                <!-- Search Input & Live Dropdown -->
                <div style="position: relative;">
                    <div style="display: flex; gap: 8px;">
                        <input type="text" id="genreSearchInput" class="form-control" placeholder="Gõ tên thể loại để tìm hoặc nhập thể loại mới rồi nhấn Enter..." autocomplete="off">
                        <button type="button" class="btn btn-outline" id="addNewTagBtn" style="white-space: nowrap;">
                            <i class="fa-solid fa-plus"></i> Thêm
                        </button>
                    </div>

                    <!-- Autocomplete Dropdown -->
                    <div id="genreDropdown" style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: var(--admin-card); border: 1px solid var(--admin-border); border-radius: 8px; max-height: 200px; overflow-y: auto; z-index: 100; display: none; box-shadow: 0 10px 25px rgba(0,0,0,0.5);">
                    </div>
                </div>

                <!-- Quick Suggested Tags -->
                <div style="margin-top: 14px;">
                    <span style="font-size: 0.78rem; color: var(--admin-text-dim); display: block; margin-bottom: 6px;">Gợi ý nhanh (nhấn để thêm):</span>
                    <div style="display: flex; flex-wrap: wrap; gap: 6px;" id="suggestedTagsList">
                        @foreach($genres as $g)
                            <button type="button" class="suggested-genre-chip" data-id="{{ $g->id }}" data-name="{{ $g->name }}">
                                + {{ $g->name }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 24px; margin: 20px 0; flex-wrap: wrap;">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}> Đặt làm truyện Nổi Bật (Slider Banner)
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="is_recommended" value="1" {{ old('is_recommended') ? 'checked' : '' }}> Đề xuất độc giả
                </label>
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; color: #f59e0b; font-weight: 600;">
                    <input type="checkbox" name="is_hidden" value="1" {{ old('is_hidden') ? 'checked' : '' }}> <i class="fa-solid fa-eye-slash"></i> Tạm ẩn truyện khỏi giao diện người dùng
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--admin-border);">
                <a href="{{ route('admin.comics.index') }}" class="btn btn-outline">Hủy bỏ</a>
                <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-floppy-disk"></i> Lưu Truyện Mới</button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
    .selected-genre-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--admin-primary);
        color: #fff;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 600;
        animation: fadeIn 0.2s ease;
    }
    .selected-genre-badge .remove-tag-btn {
        background: none;
        border: none;
        color: #fca5a5;
        cursor: pointer;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        padding: 0;
        margin-left: 2px;
    }
    .selected-genre-badge .remove-tag-btn:hover {
        color: #ef4444;
    }
    .genre-dropdown-item {
        padding: 10px 14px;
        cursor: pointer;
        font-size: 0.88rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--admin-border);
        transition: background 0.15s;
    }
    .genre-dropdown-item:hover, .genre-dropdown-item.active {
        background: var(--admin-primary);
        color: #fff;
    }
    .genre-dropdown-item.is-new {
        background: rgba(245, 158, 11, 0.15);
        color: #f59e0b;
        font-weight: 700;
    }
    .suggested-genre-chip {
        background: #151c27;
        border: 1px solid var(--admin-border);
        color: var(--admin-text-muted);
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.78rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .suggested-genre-chip:hover {
        background: var(--admin-primary);
        border-color: var(--admin-primary);
        color: #fff;
    }
    .suggested-genre-chip.selected {
        opacity: 0.4;
        pointer-events: none;
    }
</style>
@endpush

@push('scripts')
<script>
    const allGenresList = @json($genres->map(fn($g) => ['id' => $g->id, 'name' => $g->name]));
    const selectedTags = new Map(); // name -> id or null

    const tagsContainer = document.getElementById('selectedTagsContainer');
    const hiddenInputs = document.getElementById('hiddenTagsInputs');
    const searchInput = document.getElementById('genreSearchInput');
    const dropdown = document.getElementById('genreDropdown');
    const noTagsNotice = document.getElementById('noTagsNotice');
    const addBtn = document.getElementById('addNewTagBtn');

    function renderTags() {
        // Clear badges (except notice if empty)
        tagsContainer.querySelectorAll('.selected-genre-badge').forEach(el => el.remove());
        hiddenInputs.innerHTML = '';

        if (selectedTags.size === 0) {
            noTagsNotice.style.display = 'inline-block';
        } else {
            noTagsNotice.style.display = 'none';
        }

        selectedTags.forEach((id, name) => {
            // Badge UI
            const badge = document.createElement('span');
            badge.className = 'selected-genre-badge';
            badge.innerHTML = `
                <span>${name}</span>
                <button type="button" class="remove-tag-btn" onclick="removeTag('${name.replace(/'/g, "\\'")}')">&times;</button>
            `;
            tagsContainer.appendChild(badge);

            // Hidden input
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'genres[]';
            input.value = id || name; // if existing send ID or name, if new send name
            hiddenInputs.appendChild(input);
        });

        // Update suggested chips state
        document.querySelectorAll('.suggested-genre-chip').forEach(chip => {
            chip.classList.toggle('selected', selectedTags.has(chip.dataset.name));
        });
    }

    function addTag(name, id = null) {
        const cleanName = name.trim();
        if (!cleanName) return;

        // Check if existing in allGenres
        const existing = allGenresList.find(g => g.name.toLowerCase() === cleanName.toLowerCase());
        if (existing) {
            selectedTags.set(existing.name, existing.id);
        } else {
            selectedTags.set(cleanName, null);
        }

        renderTags();
        searchInput.value = '';
        dropdown.style.display = 'none';
    }

    function removeTag(name) {
        selectedTags.delete(name);
        renderTags();
    }

    // Search Input Event
    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        if (!query) {
            dropdown.style.display = 'none';
            dropdown.innerHTML = '';
            return;
        }

        const matched = allGenresList.filter(g => g.name.toLowerCase().includes(query));
        const exactMatch = allGenresList.some(g => g.name.toLowerCase() === query);

        let html = '';
        matched.forEach(g => {
            const isSelected = selectedTags.has(g.name);
            html += `
                <div class="genre-dropdown-item" onclick="addTag('${g.name.replace(/'/g, "\\'")}', ${g.id})" style="${isSelected ? 'opacity: 0.5; pointer-events: none;' : ''}">
                    <span>${g.name}</span>
                    <span style="font-size: 0.75rem; color: var(--admin-text-muted);">${isSelected ? 'Đã chọn' : '+ Chọn'}</span>
                </div>
            `;
        });

        if (!exactMatch && query.length > 0) {
            const cleanVal = searchInput.value.trim();
            html += `
                <div class="genre-dropdown-item is-new" onclick="addTag('${cleanVal.replace(/'/g, "\\'")}')">
                    <span><i class="fa-solid fa-plus-circle"></i> Tạo thể loại mới: "<strong>${cleanVal}</strong>"</span>
                    <span style="font-size: 0.75rem; color: #f59e0b;">Tự động lưu vào DB</span>
                </div>
            `;
        }

        dropdown.innerHTML = html;
        dropdown.style.display = 'block';
    });

    // Enter Key on Search Input
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = this.value.trim();
            if (val) {
                addTag(val);
            }
        }
    });

    addBtn.addEventListener('click', function() {
        const val = searchInput.value.trim();
        if (val) {
            addTag(val);
        }
    });

    // Suggested Chips click
    document.querySelectorAll('.suggested-genre-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            addTag(this.dataset.name, parseInt(this.dataset.id));
        });
    });

    // Close dropdown when click outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Pre-populate if old values exist
    @if(old('genres'))
        @foreach(old('genres') as $oldG)
            addTag('{{ $oldG }}');
        @endforeach
    @endif
</script>
@endpush
@endsection
