@extends('layouts.admin')

@section('title', 'Quản Lý Thể Loại - GTSCHUNDER Admin')
@section('page_title', 'Quản Lý Thể Loại Truyện')

@section('content')
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Add Genre Form -->
    <div class="admin-card-box">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px;">Thêm Thể Loại Mới</h3>
        <form action="{{ route('admin.genres.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Tên Thể Loại <span style="color: var(--admin-danger);">*</span></label>
                <input type="text" name="name" required class="form-control" placeholder="Ví dụ: Hành động, Tình cảm...">
            </div>
            <div class="form-group">
                <label class="form-label">Mô Tả</label>
                <textarea name="description" rows="3" class="form-control" placeholder="Mô tả thể loại..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Thêm Thể Loại</button>
        </form>
    </div>

    <!-- Genres List -->
    <div class="admin-card-box">
        <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px;">Danh Sách Thể Loại ({{ $genres->count() }})</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Slug</th>
                    <th>Số Truyện</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($genres as $genre)
                    <tr>
                        <td><strong>{{ $genre->name }}</strong></td>
                        <td><code>{{ $genre->slug }}</code></td>
                        <td><span class="badge badge-primary">{{ $genre->comics_count }}</span></td>
                        <td>
                            <form action="{{ route('admin.genres.destroy', $genre->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa thể loại này?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm" title="Xóa">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--admin-text-muted);">Chưa có thể loại nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
