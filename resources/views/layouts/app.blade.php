<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GTSCHunder - Đọc Truyện Tranh Webtoon, Manhwa Online Miễn Phí')</title>
    <meta name="description" content="@yield('meta_description', 'GTSCHunder - Nền tảng đọc truyện tranh Manhwa, Manga, Manhua, Webtoon online chất lượng cao, cập nhật nhanh nhất mỗi ngày.')">
    
    <!-- Favicons -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Google Fonts with full Vietnamese Support -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Main Style -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    
    @stack('styles')
</head>
<body class="@yield('body_class')">

    <!-- Header Navbar - 2-tier Structure (TruyenQQ Style) -->
    @unless(View::hasSection('hide_navbar'))
    <header class="site-header">
        <!-- Top Row: Logo | Big Search Bar | Auth Actions -->
        <div class="header-top-row">
            <div class="container header-top-inner">
                <!-- Brand Logo -->
                <div style="display: flex; align-items: center; gap: 12px;">
                    <a href="{{ route('home') }}" class="brand-logo">
                        <img src="{{ asset('images/logo.png') }}" alt="GTSCHunder Logo" style="width: 40px; height: 40px; object-fit: contain; border-radius: 8px;">
                        <span>GTSC<span style="color: #506891;font-weight:bold;">HUNDER</span></span>
                    </a>
                </div>

                <!-- Big Search Bar with Live Ajax -->
                <div class="main-search-wrapper">
                    <form action="{{ route('comics.index') }}" method="GET" class="main-search-form">
                        <input type="text" name="q" id="headerSearchInput" placeholder="Bạn muốn tìm truyện gì..." autocomplete="off" value="{{ request('q') }}">
                        <button type="submit" class="main-search-submit" title="Tìm kiếm">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </form>
                    <div id="searchResultsDropdown" class="search-results-dropdown-large"></div>
                </div>

                <!-- User / Auth Actions on Right -->
                <div class="header-auth-actions">
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline btn-sm" style="border-color: var(--primary);">
                                Admin
                            </a>
                        @endif

                        <div class="user-dropdown">
                            <button class="user-avatar-btn" id="userMenuToggle">
                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="user-avatar-img">
                                <span style="font-weight: 600; font-size: 0.9rem; color: #fff;">{{ Str::limit(auth()->user()->name, 14) }}</span>
                                <i class="fa-solid fa-chevron-down" style="font-size: 0.72rem; color: var(--text-dim);"></i>
                            </button>
                            <div class="dropdown-menu" id="userDropdownMenu">
                                <a href="{{ route('profile') }}" class="dropdown-item">Tài khoản</a>
                                <a href="{{ route('bookmarks.index') }}" class="dropdown-item">Truyện theo dõi</a>
                                <a href="{{ route('history.index') }}" class="dropdown-item">Lịch sử đọc</a>
                                @if(auth()->user()->isAdmin())
                                    <a href="{{ route('admin.tools.fast-upload') }}" class="dropdown-item" style="color: var(--accent);">Tool Đăng Chapter</a>
                                @endif
                                <hr style="border-color: var(--border-color); margin: 6px 0;">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item" style="width: 100%; border: none; background: transparent; cursor: pointer; text-align: left; color: var(--accent-red);">
                                        Đăng xuất
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('register') }}" class="btn-register-top">Đăng ký</a>
                        <a href="{{ route('login') }}" class="btn-login-top">Đăng nhập</a>
                    @endauth
                </div>
            </div>
        </div>

        <!-- Bottom Subnav Bar (Orange Bar like TruyenQQ) -->
        <nav class="subnav-bar">
            <div class="container subnav-inner">
                <ul class="subnav-links">
                    <li><a href="{{ route('home') }}" class="subnav-link {{ request()->routeIs('home') ? 'active' : '' }}">Trang Chủ</a></li>
                    
                    <!-- Genre Mega Dropdown -->
                    <li class="subnav-dropdown-item">
                        <a href="{{ route('comics.index') }}" class="subnav-link">
                            Thể Loại <i class="fa-solid fa-caret-down" style="font-size: 0.75rem; margin-left: 2px;"></i>
                        </a>
                        <div class="subnav-dropdown-menu genre-mega-menu">
                            <div class="genre-grid-menu">
                                <a href="{{ route('comics.index') }}" class="genre-menu-link" style="font-weight: 700; color: #f97316;">Tất cả thể loại</a>
                                @foreach($headerGenres ?? [] as $hg)
                                    <a href="{{ route('comics.index', ['genres' => [$hg->slug]]) }}" class="genre-menu-link">
                                        {{ $hg->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </li>

                    <!-- Rank Dropdown -->
                    <li class="subnav-dropdown-item">
                        <a href="{{ route('comics.index', ['sort' => 'views']) }}" class="subnav-link">
                            Xếp Hạng <i class="fa-solid fa-caret-down" style="font-size: 0.75rem; margin-left: 2px;"></i>
                        </a>
                        <div class="subnav-dropdown-menu rank-sub-menu">
                            <a href="{{ route('comics.index', ['sort' => 'views']) }}" class="rank-menu-link">Lượt xem nhiều nhất</a>
                            <a href="{{ route('comics.index', ['sort' => 'rating']) }}" class="rank-menu-link">Đánh giá cao nhất</a>
                            <a href="{{ route('comics.index', ['sort' => 'latest']) }}" class="rank-menu-link">Mới cập nhật</a>
                            <a href="{{ route('comics.index', ['sort' => 'newest']) }}" class="rank-menu-link">Truyện mới đăng</a>
                        </div>
                    </li>

                    <li><a href="{{ route('history.index') }}" class="subnav-link {{ request()->routeIs('history.index') ? 'active' : '' }}">Lịch Sử</a></li>
                    <li><a href="{{ route('bookmarks.index') }}" class="subnav-link {{ request()->routeIs('bookmarks.index') ? 'active' : '' }}">Theo Dõi</a></li>
                    
                    <!-- Quick Category Shortcuts -->
                    <li class="subnav-hide-mobile"><a href="{{ route('comics.index', ['genres' => ['manhwa']]) }}" class="subnav-link">Manhwa</a></li>
                    <li class="subnav-hide-mobile"><a href="{{ route('comics.index', ['genres' => ['manga']]) }}" class="subnav-link">Manga</a></li>
                </ul>
            </div>
        </nav>
    </header>
    @endunless

    <!-- Flash Alerts -->
    <div class="container" style="margin-top: 16px;">
        @if(session('success'))
            <div class="alert alert-success">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error">
                <div>
                    @foreach($errors->all() as $err)
                        <p>{{ $err }}</p>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Main Content -->
    <main style="flex: 1;">
        @yield('content')
    </main>

    <!-- Footer -->
    @unless(View::hasSection('hide_footer'))
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <a href="{{ route('home') }}" class="brand-logo" style="margin-bottom: 12px; display: inline-flex; align-items: center; gap: 10px;">
                        <img src="{{ asset('images/logo.png') }}" alt="GTSCHunder Logo" style="width: 36px; height: 36px; object-fit: contain;">
                        <span>GTSC<span style="color: #506891;font-weight:bold;" style="background: var(--primary);">HUNDER</span></span>
                    </a>
                    <p style="color: var(--text-dim); font-size: 0.9rem; max-width: 450px; line-height: 1.6;">
                        <strong>GTSCHunder</strong> </br> 甘いだけじゃダメだけどね今日くらいは許してよ… hum 大事な話だからもうちょ〜〜〜〜〜〜っと</br>(気合いだけで言えるかな)</br>誠心誠意をきいてくれますか？</br>Ah〜〜 (うぉーー！)
                    </p>
                </div>
                <div>
                    <h4 style="margin-bottom: 14px; font-size: 1rem;">Liên kết nhanh</h4>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                        <li><a href="{{ route('home') }}">Trang chủ</a></li>
                        <li><a href="{{ route('comics.index') }}">Tất cả truyện</a></li>
                        <li><a href="{{ route('comics.index', ['status' => 'completed']) }}">Truyện hoàn thành</a></li>
                        <li><a href="{{ route('history.index') }}">Lịch sử đọc</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="margin-bottom: 14px; font-size: 1rem;">Hỗ trợ & Quản trị</h4>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem; color: var(--text-muted);">
                        <li><a href="{{ route('login') }}">Đăng nhập thành viên</a></li>
                        <li><a href="{{ route('register') }}">Tạo tài khoản mới</a></li>
                        @auth
                            @if(auth()->user()->isAdmin())
                                <li><a href="{{ route('admin.dashboard') }}" style="color: var(--accent);">Bảng quản trị Admin</a></li>
                            @endif
                        @endauth
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} GTSCHunder. Nền tảng truyện tranh trực tuyến Webtoon đỉnh cao.</p>
            </div>
        </div>
    </footer>
    @endunless

    <!-- Core Scripts -->
    <script>
        // Global Image Error Fallback to default.png
        document.addEventListener('error', function (e) {
            if (e.target.tagName === 'IMG' && !e.target.dataset.fallbackApplied) {
                e.target.dataset.fallbackApplied = 'true';
                e.target.src = '{{ asset("images/default.png") }}';
            }
        }, true);

        // User Dropdown toggle
        const userMenuToggle = document.getElementById('userMenuToggle');
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        if (userMenuToggle && userDropdownMenu) {
            userMenuToggle.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdownMenu.style.display = userDropdownMenu.style.display === 'block' ? 'none' : 'block';
            });
            document.addEventListener('click', function() {
                userDropdownMenu.style.display = 'none';
            });
        }

        // Live Search AJAX with Large Suggestions Dropdown
        const searchInput = document.getElementById('headerSearchInput');
        const searchDropdown = document.getElementById('searchResultsDropdown');
        let searchTimer = null;

        if (searchInput && searchDropdown) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                const val = this.value.trim();
                if (val.length < 2) {
                    searchDropdown.style.display = 'none';
                    searchDropdown.innerHTML = '';
                    return;
                }

                searchTimer = setTimeout(() => {
                    fetch(`{{ route('api.search') }}?q=${encodeURIComponent(val)}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data.length === 0) {
                                searchDropdown.innerHTML = '<div style="padding: 16px; text-align: center; color: var(--text-dim); font-size: 0.9rem;">Không tìm thấy truyện phù hợp</div>';
                                searchDropdown.style.display = 'block';
                                return;
                            }

                            let html = '';
                            data.forEach(item => {
                                html += `
                                    <a href="${item.url}" class="search-suggestion-item">
                                        <img src="${item.cover}" alt="${item.title}" class="search-sug-img" onerror="this.src='{{ asset('images/default.png') }}'">
                                        <div class="search-sug-info">
                                            <h4 class="search-sug-title">${item.title}</h4>
                                            <div class="search-sug-meta">
                                                <span class="search-sug-chap">${item.latest_chapter}</span>
                                                <span class="search-sug-views">${item.views} lượt xem</span>
                                                ${item.genres ? `<span class="search-sug-genre">${item.genres}</span>` : ''}
                                            </div>
                                        </div>
                                    </a>
                                `;
                            });
                            searchDropdown.innerHTML = html;
                            searchDropdown.style.display = 'block';
                        })
                        .catch(err => console.error(err));
                }, 200);
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                    searchDropdown.style.display = 'none';
                }
            });
        }
    </script>

    @include('partials.delete_confirm_modal')

    @stack('scripts')
</body>
</html>
