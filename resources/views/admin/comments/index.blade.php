@extends('layouts.admin')

@section('title', 'Quản Lý Bình Luận - GTSCHUNDER Admin')
@section('page_title', 'Kiểm Duyệt Bình Luận')

@section('content')
<div class="admin-card-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
        <form action="{{ route('admin.comments.index') }}" method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 450px;">
            <input type="text" name="q" class="form-control" placeholder="Tìm nội dung bình luận..." value="{{ request('q') }}">
            <button type="submit" class="btn btn-outline"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Người dùng</th>
                <th>Truyện / Chapter</th>
                <th>Nội dung bình luận</th>
                <th>Lượt thích</th>
                <th>Thời gian</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($comments as $comment)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <img src="{{ $comment->user->avatar_url }}" alt="avatar" style="width: 28px; height: 28px; border-radius: 50%;">
                            <span style="font-weight: 600;">{{ $comment->user->name }}</span>
                        </div>
                    </td>
                    <td>
                        @if($comment->comic)
                            <a href="{{ route('comics.show', $comment->comic->slug) }}" target="_blank" style="color: var(--admin-primary-light); font-weight: 600;">
                                {{ $comment->comic->title }}
                            </a>
                        @endif
                        @if($comment->chapter)
                            <div style="font-size: 0.8rem; color: var(--admin-text-muted);">Chap {{ $comment->chapter->formatted_number }}</div>
                        @endif
                    </td>
                    <td style="max-width: 320px; line-height: 1.4;">{{ $comment->content }}</td>
                    <td><i class="fa-solid fa-thumbs-up" style="color: var(--admin-primary-light);"></i> {{ $comment->likes }}</td>
                    <td>{{ $comment->created_at->diffForHumans() }}</td>
                    <td>
                        @if($comment->is_hidden)
                            <span class="badge" style="background: rgba(239,68,68,0.2); color: #f87171;">Đã ẩn</span>
                        @else
                            <span class="badge" style="background: rgba(16,185,129,0.2); color: #34d399;">Hiển thị</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <form action="{{ route('admin.comments.toggle-hide', $comment->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm" title="{{ $comment->is_hidden ? 'Hiện bình luận' : 'Ẩn bình luận' }}">
                                    <i class="fa-solid {{ $comment->is_hidden ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.comments.destroy', $comment->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa bình luận này?');">
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
                    <td colspan="7" style="text-align: center; color: var(--admin-text-muted); padding: 30px;">Chưa có bình luận nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-wrapper" style="margin-top: 24px;">
        {{ $comments->links() }}
    </div>
</div>
@endsection
