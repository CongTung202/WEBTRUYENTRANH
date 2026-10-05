@extends('layouts.admin')

@section('title', 'Sửa Chapter ' . $chapter->formatted_number . ' - ' . $comic->title . ' - GTSCHunder Admin')
@section('page_title', 'Quản Lý & Sắp Xếp Trang Chapter ' . $chapter->formatted_number)

@section('content')
<div style="max-width: 1000px; margin: 0 auto;">

    <!-- Top Navigation & Basic Info -->
    <div class="admin-card-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid var(--admin-border);">
            <div>
                <a href="{{ route('admin.comics.chapters.index', $comic->id) }}" style="color: var(--admin-text-muted); font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                    <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách chapters
                </a>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: #fff;">
                    {{ $comic->title }} &bull; <span style="color: var(--admin-primary-light);">Chapter {{ $chapter->formatted_number }}</span>
                </h3>
            </div>
            <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $chapter->chapter_number]) }}" target="_blank" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-eye"></i> Đọc thử trên Web
            </a>
        </div>

        <form action="{{ route('admin.comics.chapters.update', ['comic' => $comic->id, 'chapter' => $chapter->id]) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: 1fr 2fr auto; gap: 16px; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Số Chapter <span style="color: var(--admin-danger);">*</span></label>
                    <input type="number" step="0.1" name="chapter_number" value="{{ old('chapter_number', $chapter->chapter_number) }}" required class="form-control">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label">Tiêu Đề Chapter</label>
                    <input type="text" name="title" value="{{ old('title', $chapter->title) }}" class="form-control">
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="height: 42px;">
                        <i class="fa-solid fa-floppy-disk"></i> Lưu Thông Tin
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Upload Additional Pages Box -->
    <div class="admin-card-box" style="border-left: 4px solid #f59e0b;">
        <h4 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-cloud-arrow-up" style="color: #f59e0b;"></i> Thêm Trang Mới
        </h4>

        <form action="{{ route('admin.comics.chapters.add-pages', ['comic' => $comic->id, 'chapter' => $chapter->id]) }}" method="POST" enctype="multipart/form-data" id="addPagesForm">
            @csrf
            <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <input type="file" name="new_pages[]" multiple accept="image/*" required class="form-control" style="flex: 1; min-width: 280px; padding: 10px;">
                <button type="submit" class="btn btn-accent" id="uploadMoreBtn">
                    <i class="fa-solid fa-plus"></i> Tải Thêm Ảnh Lên
                </button>
            </div>
        </form>
    </div>

    <!-- Drag and Drop Reordering Pages Section -->
    <div class="admin-card-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h4 style="font-size: 1.15rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-arrows-up-down-left-right" style="color: var(--admin-primary-light);"></i> 
                    Kéo & Thả Để Sắp Xếp Lại Vị Trí Trang Ảnh
                    <span style="font-size: 0.85rem; font-weight: normal; color: var(--admin-text-muted);">({{ $pages->count() }} trang)</span>
                </h4>
                <p style="color: var(--admin-text-muted); font-size: 0.85rem; margin-top: 4px;">
                    <i class="fa-solid fa-circle-info" style="color: var(--admin-primary-light);"></i> 
                    Bạn có thể giữ chuột và kéo thả các thẻ ảnh để thay đổi thứ tự trang đọc, sau đó nhấn <strong>"Lưu Thứ Tự"</strong>.
                </p>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-primary" id="saveOrderBtn">
                    <i class="fa-solid fa-floppy-disk"></i> Lưu Thứ Tự Mới
                </button>
            </div>
        </div>

        <div id="reorderStatusAlert" style="display: none; padding: 10px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem;"></div>

        <!-- Sortable Grid -->
        <div class="pages-sortable-grid" id="sortablePagesContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px;">
            @forelse($pages as $page)
                <div class="page-sort-item" data-id="{{ $page->id }}" style="background: #0d131c; border: 2px solid var(--admin-border); border-radius: 10px; overflow: hidden; position: relative; cursor: grab; user-select: none; transition: transform 0.2s, box-shadow 0.2s;">
                    
                    <!-- Page Number Badge & Drag Handle -->
                    <div style="padding: 8px 10px; background: rgba(17, 23, 34, 0.9); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--admin-border);">
                        <span class="badge badge-primary page-num-badge" style="font-weight: 700; font-size: 0.8rem;">
                            Trang <span class="num-val">{{ $page->page_number }}</span>
                        </span>
                        <span style="color: var(--admin-text-muted); font-size: 0.85rem;" title="Giữ để kéo">
                            <i class="fa-solid fa-grip-vertical"></i>
                        </span>
                    </div>

                    <!-- Image Thumbnail -->
                    <div style="width: 100%; height: 230px; background: #000; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <img src="{{ $page->image_url }}" alt="Page {{ $page->page_number }}" style="width: 100%; height: 100%; object-fit: contain;" loading="lazy" onerror="this.src='{{ asset('images/default.png') }}'">
                    </div>

                    <!-- Actions Footer -->
                    <div style="padding: 8px; background: #111722; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--admin-border);">
                        <a href="{{ $page->image_url }}" target="_blank" style="color: var(--admin-primary-light); font-size: 0.78rem;" title="Xem ảnh gốc">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Xem
                        </a>
                        <form action="{{ route('admin.comics.chapters.delete-page', ['comic' => $comic->id, 'chapter' => $chapter->id, 'page' => $page->id]) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa trang số {{ $page->page_number }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" style="background: none; border: none; color: var(--admin-danger); cursor: pointer; font-size: 0.82rem;" title="Xóa trang này">
                                <i class="fa-solid fa-trash-can"></i> Xóa
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1/-1; text-align: center; padding: 40px; color: var(--admin-text-muted);">
                    <i class="fa-regular fa-image" style="font-size: 2.5rem; margin-bottom: 10px; display: block;"></i>
                    Chưa có trang ảnh nào. Hãy dùng form phía trên để thêm ảnh vào chapter!
                </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<!-- SortableJS for smooth drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const container = document.getElementById('sortablePagesContainer');
    const saveOrderBtn = document.getElementById('saveOrderBtn');
    const statusAlert = document.getElementById('reorderStatusAlert');

    if (container && Sortable) {
        const sortable = new Sortable(container, {
            animation: 200,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function() {
                updatePageBadges();
                saveOrderBtn.style.background = '#f59e0b';
                saveOrderBtn.style.color = '#000';
                saveOrderBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Lưu Thay Đổi Thứ Tự*';
            }
        });

        function updatePageBadges() {
            const items = container.querySelectorAll('.page-sort-item');
            items.forEach((item, idx) => {
                const badge = item.querySelector('.num-val');
                if (badge) badge.innerText = idx + 1;
            });
        }

        saveOrderBtn.addEventListener('click', function() {
            const items = container.querySelectorAll('.page-sort-item');
            const pageIds = Array.from(items).map(item => parseInt(item.dataset.id));

            if (pageIds.length === 0) return;

            saveOrderBtn.disabled = true;
            saveOrderBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang lưu...';

            fetch(`{{ route('admin.comics.chapters.reorder-pages', ['comic' => $comic->id, 'chapter' => $chapter->id]) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ page_ids: pageIds })
            })
            .then(res => res.json())
            .then(data => {
                saveOrderBtn.disabled = false;
                saveOrderBtn.style.background = 'var(--admin-primary)';
                saveOrderBtn.style.color = '#fff';
                saveOrderBtn.innerHTML = '<i class="fa-solid fa-check"></i> Đã Lưu Thứ Tự!';

                statusAlert.style.display = 'block';
                statusAlert.className = 'alert alert-success';
                statusAlert.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${data.message || 'Đã cập nhật thứ tự các trang thành công!'}`;

                setTimeout(() => {
                    saveOrderBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Lưu Thứ Tự Mới';
                }, 2500);
            })
            .catch(err => {
                saveOrderBtn.disabled = false;
                alert('Lỗi khi lưu thứ tự: ' + err.message);
            });
        });
    }
</script>

<style>
    .sortable-ghost {
        opacity: 0.4;
        border: 2px dashed #f59e0b !important;
        transform: scale(0.96);
    }
    .sortable-chosen {
        box-shadow: 0 0 15px rgba(245, 158, 11, 0.5) !important;
    }
</style>
@endpush
@endsection
