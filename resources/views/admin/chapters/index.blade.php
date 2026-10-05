@extends('layouts.admin')

@section('title', 'Quản Lý Chapters: ' . $comic->title . ' - GTSCHUNDER Admin')
@section('page_title', 'Chapters: ' . $comic->title)

@section('content')
<div class="admin-card-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
        <div>
            <a href="{{ route('admin.comics.index') }}" style="color: var(--admin-text-muted); font-size: 0.88rem; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 6px;">
                <i class="fa-solid fa-arrow-left"></i> Quay lại danh sách truyện
            </a>
            <h3 style="font-size: 1.2rem; font-weight: 800; color: #fff;">{{ $comic->title }} <span style="font-size: 0.9rem; font-weight: normal; color: var(--admin-text-muted);">({{ $chapters->total() }} chapters)</span></h3>
        </div>

        <div style="display: flex; gap: 10px;">
            <a href="{{ route('admin.tools.fast-upload') }}" class="btn btn-accent">
                Đăng Truyện Nhanh
            </a>
            <a href="{{ route('admin.comics.chapters.create', $comic->id) }}" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Thêm Chapter Mới
            </a>
        </div>
    </div>

    <!-- Table -->
    <table class="admin-table">
        <thead>
            <tr>
                <th>Số Chapter</th>
                <th>Tiêu đề</th>
                <th>Số trang ảnh</th>
                <th>Lượt xem</th>
                <th>Ngày đăng</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($chapters as $chap)
                <tr>
                    <td><strong style="color: var(--admin-primary-light); font-size: 1rem;">Chap {{ $chap->formatted_number }}</strong></td>
                    <td>{{ $chap->title ?: 'Chapter ' . $chap->formatted_number }}</td>
                    <td><span class="badge badge-primary">{{ $chap->pages_count }} trang</span></td>
                    <td>{{ number_format($chap->views) }}</td>
                    <td>{{ $chap->created_at->format('d/m/Y H:i') }}</td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="{{ route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $chap->chapter_number]) }}" target="_blank" class="btn btn-outline btn-sm" title="Xem trên web">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.comics.chapters.edit', ['comic' => $comic->id, 'chapter' => $chap->id]) }}" class="btn btn-outline btn-sm" title="Sửa">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('admin.comics.chapters.destroy', ['comic' => $comic->id, 'chapter' => $chap->id]) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa Chapter {{ $chap->formatted_number }}?');">
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
                    <td colspan="6" style="text-align: center; color: var(--admin-text-muted); padding: 30px;">Chưa có chapter nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-wrapper" style="margin-top: 24px;">
        {{ $chapters->links() }}
    </div>
</div>
@endsection
