@extends('layouts.admin')

@section('title', 'Quản Lý Truyện - GTSCHUNDER Admin')
@section('page_title', 'Danh Sách Truyện Tranh')

@section('content')
<div class="admin-card-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
        <!-- Search & Filter -->
        <form action="{{ route('admin.comics.index') }}" method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 650px; flex-wrap: wrap;">
            <input type="text" name="q" class="form-control" placeholder="Tìm tên truyện, tác giả..." value="{{ request('q') }}" style="flex: 1; min-width: 180px;">
            <select name="status" class="form-control" style="width: 140px;">
                <option value="">-- Tiến độ --</option>
                <option value="ongoing" {{ request('status') === 'ongoing' ? 'selected' : '' }}>Đang tiến hành</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                <option value="dropped" {{ request('status') === 'dropped' ? 'selected' : '' }}>Tạm ngưng</option>
            </select>
            <select name="visibility" class="form-control" style="width: 140px;">
                <option value="">-- Hiển thị --</option>
                <option value="visible" {{ request('visibility') === 'visible' ? 'selected' : '' }}>Đang hiển thị</option>
                <option value="hidden" {{ request('visibility') === 'hidden' ? 'selected' : '' }}>Đang bị ẩn</option>
            </select>
            <button type="submit" class="btn btn-outline"><i class="fa-solid fa-magnifying-glass"></i> Lọc</button>
        </form>

        <!-- Actions -->
        <div style="display: flex; gap: 10px;">
            <a href="{{ route('admin.tools.fast-upload') }}" class="btn btn-accent">
                Đăng Truyện Nhanh
            </a>
            <a href="{{ route('admin.comics.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Thêm Truyện Mới
            </a>
        </div>
    </div>

    <!-- Table -->
    <table class="admin-table">
        <thead>
            <tr>
                <th>Ảnh</th>
                <th>Tên truyện</th>
                <th>Thể loại</th>
                <th>Số Chapter</th>
                <th>Tiến độ</th>
                <th>Trạng thái Web</th>
                <th>Lượt xem</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($comics as $comic)
                <tr style="{{ $comic->is_hidden ? 'opacity: 0.8; background: rgba(0,0,0,0.15);' : '' }}">
                    <td style="width: 50px;">
                        <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" style="width: 40px; height: 55px; object-fit: cover; border-radius: 4px;" onerror="this.src='{{ asset('images/default.png') }}'">
                    </td>
                    <td>
                        <strong style="color: #fff; font-size: 0.95rem;">{{ $comic->title }}</strong>
                        <div style="font-size: 0.8rem; color: var(--admin-text-muted);">Tác giả: {{ $comic->author ?: 'Chưa rõ' }}</div>
                    </td>
                    <td>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px; max-width: 220px;">
                            @foreach($comic->genres->take(3) as $g)
                                <span class="badge badge-primary" style="font-size: 0.7rem;">{{ $g->name }}</span>
                            @endforeach
                            @if($comic->genres->count() > 3)
                                <span style="font-size: 0.7rem; color: var(--admin-text-muted);">+{{ $comic->genres->count() - 3 }}</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('admin.comics.chapters.index', $comic->id) }}" class="btn btn-outline btn-sm" style="color: var(--admin-primary-light);">
                            <i class="fa-solid fa-layer-group"></i> {{ $comic->chapters->count() }} chaps
                        </a>
                    </td>
                    <td>
                        <span class="badge {{ $comic->status === 'completed' ? 'badge-completed' : 'badge-ongoing' }}">
                            {{ $comic->status_label }}
                        </span>
                    </td>
                    <td>
                        @if($comic->is_hidden)
                            <span class="badge" style="background: rgba(245, 158, 11, 0.2); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.4); font-size: 0.76rem;">
                                <i class="fa-solid fa-eye-slash"></i> Đang ẩn
                            </span>
                        @else
                            <span class="badge" style="background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); font-size: 0.76rem;">
                                <i class="fa-solid fa-eye"></i> Hiển thị
                            </span>
                        @endif
                    </td>
                    <td>{{ number_format($comic->views) }}</td>
                    <td>
                        <div style="display: flex; gap: 6px; align-items: center;">
                            <!-- Toggle Visibility Button -->
                            <form action="{{ route('admin.comics.toggle-visibility', $comic->id) }}" method="POST" style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm" style="{{ $comic->is_hidden ? 'color: #f59e0b; border-color: rgba(245, 158, 11, 0.4);' : 'color: #34d399; border-color: rgba(16, 185, 129, 0.4);' }}" title="{{ $comic->is_hidden ? 'Hiện truyện lên web' : 'Ẩn truyện khỏi web' }}">
                                    <i class="fa-solid {{ $comic->is_hidden ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                                </button>
                            </form>

                            <a href="{{ route('comics.show', $comic->slug) }}" target="_blank" class="btn btn-outline btn-sm" title="Xem trên web">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                            <a href="{{ route('admin.comics.edit', $comic->id) }}" class="btn btn-outline btn-sm" title="Chỉnh sửa">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.comics.destroy', $comic->id) }}" method="POST" data-confirm-message="Bạn có chắc chắn muốn xóa bộ truyện &quot;{{ addslashes($comic->title) }}&quot; và tất cả chapters?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Xóa">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--admin-text-muted); padding: 30px;">Chưa có bộ truyện nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-wrapper" style="margin-top: 24px;">
        {{ $comics->links() }}
    </div>
</div>
@endsection
