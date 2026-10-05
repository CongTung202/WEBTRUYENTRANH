@extends('layouts.app')

@section('title', 'Đăng ký - GTSCHUNDER')

@section('content')
<div class="login-wrapper" style="min-height: auto; padding: 40px 20px 70px;">
    <h1 class="login-logo">
        GTSC<strong style="color: #506891;font-weight:bold;">HUNDER</strong>
    </h1>

    <div class="login-card">
        <div class="login-tabs">
            <a href="{{ route('login') }}" class="tab-item inactive">
                <i class="fas fa-sign-in-alt"></i> Đăng nhập
            </a>
            <div class="tab-item active">
                <i class="fas fa-user-plus"></i> Đăng ký
            </div>
        </div>

        <div class="login-body">
            @if($errors->any())
                <div class="alert alert-error" style="margin-bottom: 16px; padding: 10px 14px; font-size: 13px;">
                    @foreach($errors->all() as $err)
                        <p>{{ $err }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}">
                @csrf
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" name="username" class="form-input" required autocomplete="off" id="username" placeholder=" " value="{{ old('username', old('name')) }}">
                        <label for="username" class="floating-label">Tên đăng nhập</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-input" required autocomplete="off" id="email" placeholder=" " value="{{ old('email') }}">
                        <label for="email" class="floating-label">Địa chỉ Email</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="password" name="register_password" class="form-input" required id="register_password" placeholder=" ">
                        <label for="register_password" class="floating-label">Mật khẩu</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" class="form-input" required id="confirm_password" placeholder=" ">
                        <label for="confirm_password" class="floating-label">Xác nhận mật khẩu</label>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Đăng ký tài khoản</button>
                
                <div class="login-footer-link">
                    Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập ngay</a>
                </div>
            </form>
        </div>
    </div>

    <div class="footer-text">
        <strong>GTSCHUNDER</strong> Copyright © <strong>GTSCHUNDER Corp.</strong> All Rights Reserved.
    </div>
</div>
@endsection
