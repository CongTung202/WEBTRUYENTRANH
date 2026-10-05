@extends('layouts.admin')

@section('title', 'Admin Dashboard - GTSCHUNDER')
@section('page_title', 'Tổng Quan Hệ Thống')

@section('content')
<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div>
            <div class="stat-value">{{ number_format($totalComics) }}</div>
            <div class="stat-label">Tổng số Truyện</div>
        </div>
        <div class="stat-icon"><i class="fa-solid fa-book"></i></div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-value">{{ number_format($totalChapters) }}</div>
            <div class="stat-label">Tổng số Chapter</div>
        </div>
        <div class="stat-icon" style="color: #38bdf8; background: rgba(56,189,248,0.15);"><i class="fa-solid fa-layer-group"></i></div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-value">{{ number_format($totalUsers) }}</div>
            <div class="stat-label">Người dùng</div>
        </div>
        <div class="stat-icon" style="color: #34d399; background: rgba(52,211,153,0.15);"><i class="fa-solid fa-users"></i></div>
    </div>

    <div class="stat-card">
        <div>
            <div class="stat-value">{{ number_format($totalViews) }}</div>
            <div class="stat-label">Lượt xem tổng</div>
        </div>
        <div class="stat-icon" style="color: #fbbf24; background: rgba(251,191,36,0.15);"><i class="fa-solid fa-eye"></i></div>
    </div>
</div>

<!-- Quick Fast Upload Banner -->
<div style="background: linear-gradient(135deg, rgba(80,104,145,0.35), rgba(30,41,59,0.9)); border: 1px solid var(--admin-primary); border-radius: 12px; padding: 24px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
    <div>
        <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 6px; display: flex; align-items: center; gap: 10px;">
           Đăng Chap Mới Nhanh (Local Folder & Multi-Upload)
        </h3>
    </div>
    <a href="{{ route('admin.tools.fast-upload') }}" class="btn btn-primary btn-lg" style="font-weight: 700; white-space: nowrap;">
        <i class="fa-solid fa-arrow-right"></i> Mở Công Cụ
    </a>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Recent Comics -->
    <div class="admin-card-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-book-bookmark" style="color: var(--admin-primary-light);"></i> Truyện Mới Đăng</h3>
            <a href="{{ route('admin.comics.index') }}" style="font-size: 0.85rem; color: var(--admin-primary-light);">Xem tất cả &rarr;</a>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ảnh</th>
                    <th>Tên truyện</th>
                    <th>Chap mới</th>
                    <th>Lượt xem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentComics as $comic)
                    <tr>
                        <td style="width: 50px;">
                            <img src="{{ $comic->cover_url }}" alt="{{ $comic->title }}" style="width: 36px; height: 48px; object-fit: cover; border-radius: 4px;" onerror="this.src='{{ asset('images/default.png') }}'">
                        </td>
                        <td>
                            <a href="{{ route('admin.comics.edit', $comic->id) }}" style="font-weight: 600; color: #fff;">{{ $comic->title }}</a>
                        </td>
                        <td>
                            <span class="badge badge-primary">{{ $comic->latestChapter ? 'Chap ' . $comic->latestChapter->formatted_number : 'Chưa có' }}</span>
                        </td>
                        <td>{{ number_format($comic->views) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--admin-text-muted);">Chưa có truyện nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Top Viewed Comics -->
    <div class="admin-card-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
            <h3 style="font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-fire" style="color: #ef4444;"></i> Top Lượt Xem</h3>
            <a href="{{ route('admin.comics.index') }}" style="font-size: 0.85rem; color: var(--admin-primary-light);">Xem tất cả &rarr;</a>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Tên truyện</th>
                    <th>Tình trạng</th>
                    <th>Đánh giá</th>
                    <th>Lượt xem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topViewedComics as $comic)
                    <tr>
                        <td>
                            <a href="{{ route('admin.comics.edit', $comic->id) }}" style="font-weight: 600; color: #fff;">{{ $comic->title }}</a>
                        </td>
                        <td>
                            <span class="badge {{ $comic->status === 'completed' ? 'badge-completed' : 'badge-ongoing' }}">
                                {{ $comic->status_label }}
                            </span>
                        </td>
                        <td style="color: #f59e0b;"><i class="fa-solid fa-star"></i> {{ number_format($comic->rating_score, 1) }}</td>
                        <td style="font-weight: 700; color: #fff;">{{ number_format($comic->views) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--admin-text-muted);">Chưa có dữ liệu.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
