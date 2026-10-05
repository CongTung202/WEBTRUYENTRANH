@extends('layouts.app')

@section('title', 'Thông Tin Tài Khoản - GTSCHUNDER')

@section('content')
<div class="container" style="max-width: 680px; padding-top: 40px; padding-bottom: 60px;">
    
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; box-shadow: var(--shadow-md);">
        <div style="text-align: center; margin-bottom: 28px;">
            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--primary); margin: 0 auto 12px; object-fit: cover;">
            <h2 style="font-size: 1.4rem; font-weight: 800;">{{ $user->name }}</h2>
            <p style="color: var(--text-dim); font-size: 0.9rem;">
                {{ $user->email }} &bull; 
                <span class="badge {{ $user->isAdmin() ? 'badge-primary' : 'badge-ongoing' }}">
                    {{ $user->isAdmin() ? 'Quản Trị Viên' : 'Thành Viên' }}
                </span>
            </p>
        </div>

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">Họ và tên</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-color); padding: 10px 14px; border-radius: var(--radius-md); color: #fff;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">Email (Không thể thay đổi)</label>
                <input type="email" value="{{ $user->email }}" disabled style="width: 100%; background: #111; border: 1px solid var(--border-color); padding: 10px 14px; border-radius: var(--radius-md); color: var(--text-dim); cursor: not-allowed;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">Mật khẩu mới (Để trống nếu không đổi)</label>
                <input type="password" name="password" style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-color); padding: 10px 14px; border-radius: var(--radius-md); color: #fff;">
            </div>

            <div style="margin-bottom: 26px;">
                <label style="display: block; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;">Xác nhận mật khẩu mới</label>
                <input type="password" name="password_confirmation" style="width: 100%; background: var(--bg-input); border: 1px solid var(--border-color); padding: 10px 14px; border-radius: var(--radius-md); color: #fff;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
                <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi
            </button>
        </form>
    </div>

</div>
@endsection
