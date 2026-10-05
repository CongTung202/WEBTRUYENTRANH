@extends('layouts.admin')

@section('title', 'Quản Lý Người Dùng - GTSCHUNDER Admin')
@section('page_title', 'Danh Sách Người Dùng')

@section('content')
<div class="admin-card-box">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 14px;">
        <form action="{{ route('admin.users.index') }}" method="GET" style="display: flex; gap: 10px; flex: 1; max-width: 450px;">
            <input type="text" name="q" class="form-control" placeholder="Tìm tên, email..." value="{{ request('q') }}">
            <button type="submit" class="btn btn-outline"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Avatar</th>
                <th>Tên</th>
                <th>Email</th>
                <th>Vai trò</th>
                <th>Theo dõi</th>
                <th>Bình luận</th>
                <th>Ngày tham gia</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td style="width: 45px;">
                        <img src="{{ $user->avatar_url }}" alt="avatar" style="width: 32px; height: 32px; border-radius: 50%;">
                    </td>
                    <td><strong>{{ $user->name }}</strong></td>
                    <td>{{ $user->email }}</td>
                    <td>
                        <span class="badge {{ $user->isAdmin() ? 'badge-primary' : 'badge-ongoing' }}">
                            {{ $user->isAdmin() ? 'Admin' : 'User' }}
                        </span>
                    </td>
                    <td>{{ $user->bookmarks_count }}</td>
                    <td>{{ $user->comments_count }}</td>
                    <td>{{ $user->created_at->format('d/m/Y') }}</td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.toggle-role', $user->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline btn-sm" title="Đổi quyền Admin / User">
                                        <i class="fa-solid fa-user-shield"></i> {{ $user->isAdmin() ? 'Hạ quyền User' : 'Set Admin' }}
                                    </button>
                                </form>
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa tài khoản này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Xóa tài khoản">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </form>
                            @else
                                <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Tài khoản hiện tại</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--admin-text-muted); padding: 30px;">Chưa có người dùng nào.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination-wrapper" style="margin-top: 24px;">
        {{ $users->links() }}
    </div>
</div>
@endsection
