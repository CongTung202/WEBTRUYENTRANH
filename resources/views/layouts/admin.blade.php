<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản trị - GTSCHunder')</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Google Fonts with full Vietnamese Support -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    @stack('styles')
</head>
<body class="admin-body">

    <div class="admin-layout">
        <!-- Sidebar Backdrop for Mobile -->
        <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>

        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="admin-brand">
                <img src="{{ asset('images/logo.png') }}" alt="GTSCHunder" style="width: 36px; height: 36px; object-fit: contain; border-radius: 6px;">
                <span>GTSC<span style="color: #506891;font-weight:bold;">HUNDER</span></span>
            </div>

            <ul class="admin-menu">
                <li class="admin-menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <a href="{{ route('admin.dashboard') }}"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
                </li>
                
                <li class="admin-menu-item {{ request()->routeIs('admin.tools.fast-upload') ? 'active' : '' }}">
                    <a href="{{ route('admin.tools.fast-upload') }}">
                        <i class="fa-solid fa-bolt" style="color: #f59e0b;"></i>
                        <span>Đăng Truyện Nhanh</span>
                    </a>
                </li>

                <li class="admin-menu-item {{ request()->routeIs('admin.comics.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.comics.index') }}"><i class="fa-solid fa-book"></i> Quản lý Truyện</a>
                </li>

                <li class="admin-menu-item {{ request()->routeIs('admin.genres.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.genres.index') }}"><i class="fa-solid fa-tags"></i> Thể loại</a>
                </li>

                <li class="admin-menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.users.index') }}"><i class="fa-solid fa-users"></i> Người dùng</a>
                </li>

                <li class="admin-menu-item {{ request()->routeIs('admin.comments.*') ? 'active' : '' }}">
                    <a href="{{ route('admin.comments.index') }}"><i class="fa-solid fa-comments"></i> Bình luận</a>
                </li>

                <li style="margin-top: auto; border-top: 1px solid var(--admin-border); padding-top: 12px;">
                    <a href="{{ route('home') }}" style="color: var(--admin-text-muted); display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 8px; font-size: 0.9rem;">
                        <i class="fa-solid fa-arrow-left"></i> Xem Website
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Topbar -->
            <header class="admin-topbar">
                <div style="display: flex; align-items: center;">
                    <button class="admin-sidebar-toggle" id="adminSidebarToggle" title="Menu">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0;">@yield('page_title', 'Bảng Điều Khiển')</h2>
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <img src="{{ auth()->user()->avatar_url }}" alt="avatar" style="width: 32px; height: 32px; border-radius: 50%; border: 2px solid var(--admin-primary);">
                        <span style="font-weight: 600; font-size: 0.88rem;" class="admin-user-name-top">{{ Str::limit(auth()->user()->name, 12) }}</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" style="color: var(--admin-danger); border-color: rgba(239,68,68,0.3);" title="Đăng xuất">
                            <i class="fa-solid fa-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Content Area -->
            <div class="admin-content">
                @if(session('success'))
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-error">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    @include('partials.delete_confirm_modal')

    <script>
        // Admin Mobile Sidebar Toggle
        const adminToggle = document.getElementById('adminSidebarToggle');
        const adminSidebar = document.getElementById('adminSidebar');
        const adminBackdrop = document.getElementById('adminSidebarBackdrop');

        if (adminToggle && adminSidebar && adminBackdrop) {
            function toggleSidebar() {
                adminSidebar.classList.toggle('show');
                adminBackdrop.classList.toggle('show');
            }
            adminToggle.addEventListener('click', toggleSidebar);
            adminBackdrop.addEventListener('click', toggleSidebar);
        }
    </script>

    @stack('scripts')
</body>
</html>
