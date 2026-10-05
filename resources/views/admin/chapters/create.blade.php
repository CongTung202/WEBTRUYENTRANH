@extends('layouts.admin')

@section('title', 'Thêm Chapter - ' . $comic->title . ' - GTSCHUNDER Admin')
@section('page_title', 'Thêm Chapter: ' . $comic->title)

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div class="admin-card-box">
        <form action="{{ route('admin.comics.chapters.store', $comic->id) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label">Số Chapter <span style="color: var(--admin-danger);">*</span></label>
                    <input type="number" step="0.1" name="chapter_number" value="{{ old('chapter_number', $suggestedNumber) }}" required class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Tiêu Đề Chapter</label>
                    <input type="text" name="title" value="{{ old('title', 'Chapter ' . $suggestedNumber) }}" class="form-control" placeholder="Tên chapter...">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Chọn Các Trang Ảnh Chapter <span style="color: var(--admin-danger);">*</span></label>
                <input type="file" name="pages[]" multiple accept="image/*" required class="form-control" style="padding: 12px;">
                <small style="color: var(--admin-text-muted); display: block; margin-top: 6px;">
                    <i class="fa-solid fa-circle-info"></i> Bạn có thể chọn nhiều ảnh cùng lúc (JPG, PNG, WEBP). Ảnh sẽ được nén sang WebP và tải lên Cloudinary.
                </small>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--admin-border);">
                <a href="{{ route('admin.comics.chapters.index', $comic->id) }}" class="btn btn-outline">Hủy bỏ</a>
                <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-cloud-arrow-up"></i> Tải Lên & Lưu Chapter</button>
            </div>
        </form>
    </div>
</div>
@endsection
