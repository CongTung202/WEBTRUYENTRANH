@extends('layouts.app')

@section('title', 'Đăng nhập - GTSCHUNDER')

@section('content')
<div class="login-wrapper" style="min-height: auto; padding: 40px 20px 70px;">
    <h1 class="login-logo">
        <a href="{{ route('login') }}">GTSCHUNDER</a>
    </h1>

    <div class="login-card">
        <div class="login-tabs">
            <div class="tab-item active">
                <i class="fas fa-sign-in-alt"></i> Đăng nhập
            </div>
            <a href="{{ route('register') }}" class="tab-item inactive">
                <i class="fas fa-user-plus"></i> Đăng ký
            </a>
        </div>

        <div class="login-body">
            @if($errors->any())
                <div class="alert alert-error" style="margin-bottom: 16px; padding: 10px 14px; font-size: 13px;">
                    @foreach($errors->all() as $err)
                        <p>{{ $err }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" name="login_input" class="form-input" required autocomplete="off" id="login_input" placeholder=" " value="{{ old('login_input') }}">
                        <label for="login_input" class="floating-label">Gmail hoặc tên đăng nhập</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="password" name="password" class="form-input" required id="password" placeholder=" ">
                        <label for="password" class="floating-label">Mật khẩu</label>
                    </div>
                </div>

                <div class="form-options">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="remember" value="1" checked>
                        <span class="checkmark">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <path d="M5 13l4 4L19 7"></path>
                            </svg>
                        </span>
                        Lưu tài khoản
                    </label>
                </div>

                <button type="submit" class="btn-submit">Đăng nhập</button>
            </form>

            <div class="auth-demo-hint">
                Tài khoản mẫu: <strong>admin@gmail.com</strong> / <strong>123456</strong>
            </div>
        </div>
    </div>

    <div class="footer-text">
        <strong>GTSCHUNDER</strong> Copyright © <strong>GTSCHUNDER Corp.</strong> All Rights Reserved.
    </div>
</div>
@endsection
