@extends('layouts.admin')

@section('title', 'Tool Đăng Chapter Nhanh - GTSCHUNDER')
@section('page_title', 'Tool Đăng Chapter Nhanh')

@section('content')
<div style="max-width: 900px; margin: 0 auto;">

    <div class="admin-card-box">
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--admin-border);">
            <div>
                <h3 style="font-size: 1.3rem; font-weight: 800; margin-bottom: 4px;">Đăng Chapter Tự Động</h3>
            </div>
        </div>

        <!-- Mode Tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 24px; background: #0d131c; padding: 6px; border-radius: 10px;">
            <button type="button" class="btn btn-primary" id="tabLocalDirBtn" onclick="switchUploadMode('local')" style="flex: 1;">
                <i class="fa-solid fa-folder-tree"></i> Cách 1: Quét từ Thư mục Local trên máy
            </button>
            <button type="button" class="btn btn-outline" id="tabFilesBtn" onclick="switchUploadMode('files')" style="flex: 1;">
                <i class="fa-solid fa-cloud-arrow-up"></i> Cách 2: Kéo & Thả nhiều ảnh vào web
            </button>
        </div>

        <!-- Common Comic Selector -->
        <div class="form-group" style="background: var(--admin-sidebar); padding: 18px; border-radius: 10px; border: 1px solid var(--admin-border);">
            <label class="form-label" style="display: flex; justify-content: space-between;">
                <span>1. Chọn Bộ Truyện Cần Đăng Chapter <span style="color: var(--admin-danger);">*</span></span>
                <span id="comicStatusInfo" style="font-size: 0.82rem; color: var(--admin-primary-light);"></span>
            </label>
            <select id="comicSelect" class="form-control" style="font-size: 1rem; padding: 12px;" required>
                <option value="">-- Chọn hoặc tìm truyện theo tên --</option>
                @foreach($comics as $c)
                    <option value="{{ $c->id }}" data-slug="{{ $c->slug }}">
                        [ID: {{ $c->id }}] {{ $c->title }} ({{ $c->latestChapter ? 'Đã có Chap ' . $c->latestChapter->formatted_number : 'Chưa có chap' }})
                    </option>
                @endforeach
            </select>

            <div id="selectedComicPreview" style="display: none; align-items: center; gap: 14px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--admin-border);">
                <img id="previewCover" src="" alt="cover" style="width: 45px; height: 60px; object-fit: cover; border-radius: 6px;">
                <div>
                    <h4 id="previewTitle" style="font-size: 1rem; margin-bottom: 2px;"></h4>
                    <p style="font-size: 0.82rem; color: var(--admin-text-muted);">
                        Slug: <code id="previewSlug" style="color: var(--admin-primary-light);"></code> &bull; 
                        Gợi ý Chapter tiếp theo: <strong id="previewNextChap" style="color: #f59e0b;"></strong>
                    </p>
                </div>
            </div>
        </div>

        <!-- Chapter Number & Title Fields -->
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px;">
            <div class="form-group">
                <label class="form-label">2. Số Thứ Tự Chapter <span style="color: var(--admin-danger);">*</span></label>
                <input type="number" step="0.1" id="chapterNumberInput" class="form-control" placeholder="Ví dụ: 1 hoặc 10.5" required>
            </div>
            <div class="form-group">
                <label class="form-label">3. Tiêu Đề Chapter (Tùy chọn)</label>
                <input type="text" id="chapterTitleInput" class="form-control" placeholder="Ví dụ: Khởi đầu mới, Đại chiến,...">
            </div>
        </div>

        <!-- Mode 1: Local Directory Form -->
        <div id="modeLocalDir">
            <div class="form-group">
                <label class="form-label">
                    4. Nhập Đường Dẫn Thư Mục Chứa Ảnh Trên Máy Local <span style="color: var(--admin-danger);">*</span>
                </label>
                <div style="display: flex; gap: 10px;">
                    <input type="text" id="localDirectoryInput" class="form-control" placeholder="Ví dụ: D:\Manga\OnePiece\Chap100 hoặc C:\truyen\chap1" style="font-family: monospace;">
                </div>
                <small style="color: var(--admin-text-muted); display: block; margin-top: 6px;">
                    <i class="fa-solid fa-circle-info"></i> Hệ thống sẽ tự động quét tất cả tệp ảnh (jpg, png, webp) trong thư mục này và sắp xếp tuần tự theo tên (1, 2, 3... 10).
                </small>
            </div>

            <button type="button" class="btn btn-primary btn-lg" id="startLocalUploadBtn" style="width: 100%; padding: 14px; font-weight: 700; font-size: 1.05rem; margin-top: 10px;">
                <i class="fa-solid fa-cloud-arrow-up"></i> Tải Lên
            </button>
        </div>

        <!-- Mode 2: Multi-file Browser Upload Form -->
        <div id="modeFiles" style="display: none;">
            <div class="form-group">
                <label class="form-label">4. Chọn hoặc Kéo Thả Các Tệp Ảnh Chapter Vào Đây <span style="color: var(--admin-danger);">*</span></label>
                <div class="fast-upload-box" onclick="document.getElementById('browserFilesInput').click()">
                    <i class="fa-solid fa-cloud-arrow-up" style="font-size: 2.5rem; color: var(--admin-primary-light); margin-bottom: 12px;"></i>
                    <h4 style="font-size: 1.05rem; margin-bottom: 6px;">Nhấn để chọn ảnh hoặc Kéo thả nhiều ảnh vào đây</h4>
                    <p style="color: var(--admin-text-muted); font-size: 0.85rem;">Hỗ trợ: JPG, PNG, WEBP, GIF (Tải từng ảnh qua AJAX tránh nghẽn)</p>
                    <input type="file" id="browserFilesInput" multiple accept="image/*" style="display: none;">
                </div>
                <div id="selectedFilesSummary" style="margin-top: 10px; font-size: 0.88rem; color: #34d399; display: none;"></div>
            </div>

            <button type="button" class="btn btn-primary btn-lg" id="startBrowserUploadBtn" style="width: 100%; padding: 14px; font-weight: 700; font-size: 1.05rem; margin-top: 10px;">
                <i class="fa-solid fa-cloud-arrow-up"></i> Tải Lên
            </button>
        </div>

    </div>

</div>

<!-- Upload Progress & Status Modal Popup -->
<div id="uploadProgressModal" class="upload-progress-modal" style="display: none;">
    <div class="upload-modal-backdrop"></div>
    <div class="upload-modal-card">
        <!-- Modal Header -->
        <div class="upload-modal-header">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="upload-header-icon" id="modalHeaderIcon">
                    <i class="fa-solid fa-cloud-arrow-up fa-bounce"></i>
                </div>
                <div>
                    <h3 class="upload-modal-title" id="modalComicTitle">Đang Tải Lên Chapter...</h3>
                    <p class="upload-modal-subtitle" id="modalChapterInfo">Vui lòng giữ nguyên trình duyệt trong quá trình tải ảnh</p>
                </div>
            </div>
            <span class="upload-status-badge" id="modalStatusBadge">Đang xử lý</span>
        </div>

        <!-- Modal Body -->
        <div class="upload-modal-body">
            <!-- Progress Counter -->
            <div class="progress-stats-box">
                <div class="progress-percent-big" id="modalProgressPercent">0%</div>
                <div class="progress-numbers" id="modalProgressCount">Trang 0 / 0</div>
            </div>

            <!-- Custom Animated Progress Bar -->
            <div class="upload-progressbar-track">
                <div class="upload-progressbar-fill" id="modalProgressBarFill" style="width: 0%;"></div>
            </div>

            <!-- Current Action Live Message -->
            <div class="upload-current-status" id="modalCurrentStatus">
                <i class="fa-solid fa-spinner fa-spin"></i> Đang chuẩn bị tệp hình ảnh...
            </div>

            <!-- Log / Page Badges Area -->
            <div class="upload-pages-log-container" id="modalPagesLog">
                <!-- Live badges: [✓ Trang 1] [✓ Trang 2] ... -->
            </div>

            <!-- Completion Action Box (Shown when finished) -->
            <div id="modalSuccessActions" style="display: none; margin-top: 20px; padding-top: 18px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center;">
                <p id="modalSuccessMessage" style="color: #34d399; font-size: 0.95rem; font-weight: 700; margin-bottom: 16px;"></p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <a id="modalBtnRead" href="#" target="_blank" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-book-open"></i> Xem Chapter Vừa Đăng
                    </a>
                    <button type="button" class="btn btn-outline btn-lg" onclick="closeProgressModalAndReset()">
                        <i class="fa-solid fa-plus"></i> Đăng Chapter Tiếp Theo
                    </button>
                </div>
            </div>

            <!-- Error Box & Retry (Shown if error) -->
            <div id="modalErrorActions" style="display: none; margin-top: 20px; padding: 14px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.4); border-radius: 8px;">
                <p id="modalErrorMessage" style="color: #fca5a5; font-size: 0.9rem; margin-bottom: 12px;"></p>
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" class="btn btn-danger btn-sm" id="modalBtnRetry">
                        <i class="fa-solid fa-rotate-right"></i> Thử Lại Trang Lỗi
                    </button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="closeProgressModal()">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let currentComicData = null;
    let isUploadRunning = false;
    let resumeRetryCallback = null;

    // Switch Upload Mode
    function switchUploadMode(mode) {
        if (mode === 'local') {
            document.getElementById('modeLocalDir').style.display = 'block';
            document.getElementById('modeFiles').style.display = 'none';
            document.getElementById('tabLocalDirBtn').className = 'btn btn-primary';
            document.getElementById('tabFilesBtn').className = 'btn btn-outline';
        } else {
            document.getElementById('modeLocalDir').style.display = 'none';
            document.getElementById('modeFiles').style.display = 'block';
            document.getElementById('tabLocalDirBtn').className = 'btn btn-outline';
            document.getElementById('tabFilesBtn').className = 'btn btn-primary';
        }
    }

    // Comic Select Change Handler
    const comicSelect = document.getElementById('comicSelect');
    comicSelect.addEventListener('change', function() {
        const comicId = this.value;
        if (!comicId) {
            document.getElementById('selectedComicPreview').style.display = 'none';
            return;
        }

        fetch(`/admin/fast-upload/comic-info/${comicId}`)
            .then(res => res.json())
            .then(data => {
                currentComicData = data;
                document.getElementById('previewCover').src = data.cover;
                document.getElementById('previewTitle').innerText = data.title;
                document.getElementById('previewSlug').innerText = data.slug;
                document.getElementById('previewNextChap').innerText = `Chap ${data.suggested_next_chapter}`;
                document.getElementById('chapterNumberInput').value = data.suggested_next_chapter;
                document.getElementById('chapterTitleInput').value = `Chapter ${data.suggested_next_chapter}`;
                document.getElementById('selectedComicPreview').style.display = 'flex';
            })
            .catch(err => console.error(err));
    });

    // Handle Browser File Input Change
    const browserFilesInput = document.getElementById('browserFilesInput');
    browserFilesInput.addEventListener('change', function() {
        const files = this.files;
        const summary = document.getElementById('selectedFilesSummary');
        if (files.length > 0) {
            summary.innerHTML = `<i class="fa-solid fa-check"></i> Đã chọn <strong>${files.length}</strong> tệp hình ảnh để tải lên.`;
            summary.style.display = 'block';
        } else {
            summary.style.display = 'none';
        }
    });

    // Helper: Modal Progress Controller
    function openProgressModal(title, subtitle, totalFiles) {
        isUploadRunning = true;
        document.getElementById('uploadProgressModal').style.display = 'flex';
        document.getElementById('modalComicTitle').innerText = title;
        document.getElementById('modalChapterInfo').innerText = subtitle;
        document.getElementById('modalStatusBadge').innerText = 'Đang tải lên';
        document.getElementById('modalStatusBadge').className = 'upload-status-badge';
        document.getElementById('modalHeaderIcon').className = 'upload-header-icon';
        document.getElementById('modalHeaderIcon').innerHTML = '<i class="fa-solid fa-cloud-arrow-up fa-bounce"></i>';

        document.getElementById('modalProgressPercent').innerText = '0%';
        document.getElementById('modalProgressCount').innerText = `Trang 0 / ${totalFiles}`;
        document.getElementById('modalProgressBarFill').style.width = '0%';
        document.getElementById('modalCurrentStatus').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang chuẩn bị tệp hình ảnh...';

        const logContainer = document.getElementById('modalPagesLog');
        logContainer.innerHTML = '';
        for (let i = 1; i <= totalFiles; i++) {
            const chip = document.createElement('span');
            chip.id = `pageChip_${i}`;
            chip.className = 'upload-page-chip';
            chip.innerText = `Trang ${i}`;
            logContainer.appendChild(chip);
        }

        document.getElementById('modalSuccessActions').style.display = 'none';
        document.getElementById('modalErrorActions').style.display = 'none';
    }

    function updateModalProgress(currentPage, totalPages, filename = '') {
        const percent = Math.min(100, Math.round((currentPage / totalPages) * 100));
        document.getElementById('modalProgressPercent').innerText = `${percent}%`;
        document.getElementById('modalProgressCount').innerText = `Trang ${currentPage} / ${totalPages}`;
        document.getElementById('modalProgressBarFill').style.width = `${percent}%`;
        document.getElementById('modalCurrentStatus').innerHTML = `<i class="fa-solid fa-spinner fa-spin" style="color: var(--admin-accent);"></i> Đang nén WebP & tải lên Cloudinary: Trang <strong>${currentPage}</strong> ${filename ? `(${filename})` : ''}...`;

        const chip = document.getElementById(`pageChip_${currentPage}`);
        if (chip) {
            chip.className = 'upload-page-chip done';
            chip.innerHTML = `<i class="fa-solid fa-check"></i> Trang ${currentPage}`;
            chip.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function showModalSuccess(data) {
        isUploadRunning = false;
        document.getElementById('modalStatusBadge').innerText = 'Hoàn thành';
        document.getElementById('modalStatusBadge').className = 'upload-status-badge success';
        document.getElementById('modalHeaderIcon').className = 'upload-header-icon success';
        document.getElementById('modalHeaderIcon').innerHTML = '<i class="fa-solid fa-circle-check"></i>';
        document.getElementById('modalProgressPercent').innerText = '100%';
        document.getElementById('modalProgressBarFill').style.width = '100%';
        document.getElementById('modalCurrentStatus').innerHTML = '<span style="color: #34d399; font-weight: 700;"><i class="fa-solid fa-check-double"></i> Đã hoàn tất xử lý tất cả hình ảnh!</span>';

        document.getElementById('modalSuccessMessage').innerText = data.message;
        document.getElementById('modalBtnRead').href = data.read_url;
        document.getElementById('modalSuccessActions').style.display = 'block';
    }

    function showModalError(errorMessage, retryCallback = null) {
        isUploadRunning = false;
        resumeRetryCallback = retryCallback;
        document.getElementById('modalStatusBadge').innerText = 'Có lỗi';
        document.getElementById('modalStatusBadge').className = 'upload-status-badge error';
        document.getElementById('modalHeaderIcon').className = 'upload-header-icon error';
        document.getElementById('modalHeaderIcon').innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
        document.getElementById('modalCurrentStatus').innerHTML = `<span style="color: #ef4444;"><i class="fa-solid fa-circle-xmark"></i> ${errorMessage}</span>`;
        document.getElementById('modalErrorMessage').innerText = errorMessage;
        document.getElementById('modalErrorActions').style.display = 'block';

        if (retryCallback) {
            document.getElementById('modalBtnRetry').style.display = 'inline-flex';
            document.getElementById('modalBtnRetry').onclick = function() {
                document.getElementById('modalErrorActions').style.display = 'none';
                document.getElementById('modalStatusBadge').innerText = 'Đang tiếp tục';
                document.getElementById('modalStatusBadge').className = 'upload-status-badge';
                document.getElementById('modalHeaderIcon').className = 'upload-header-icon';
                document.getElementById('modalHeaderIcon').innerHTML = '<i class="fa-solid fa-cloud-arrow-up fa-bounce"></i>';
                retryCallback();
            };
        } else {
            document.getElementById('modalBtnRetry').style.display = 'none';
        }
    }

    function closeProgressModal() {
        if (isUploadRunning && !confirm('Quá trình upload đang diễn ra. Bạn có chắc chắn muốn hủy?')) {
            return;
        }
        document.getElementById('uploadProgressModal').style.display = 'none';
    }

    function closeProgressModalAndReset() {
        document.getElementById('uploadProgressModal').style.display = 'none';
        const nextNum = parseFloat(document.getElementById('chapterNumberInput').value) + 1;
        document.getElementById('chapterNumberInput').value = nextNum;
        document.getElementById('chapterTitleInput').value = `Chapter ${nextNum}`;
        document.getElementById('browserFilesInput').value = '';
        document.getElementById('selectedFilesSummary').style.display = 'none';
        if (currentComicData) {
            document.getElementById('previewNextChap').innerText = `Chap ${nextNum}`;
        }
    }

    // Process Mode 1: Local Directory Sequential AJAX Upload
    document.getElementById('startLocalUploadBtn').addEventListener('click', async function() {
        const comicId = comicSelect.value;
        const chapterNumber = document.getElementById('chapterNumberInput').value;
        const chapterTitle = document.getElementById('chapterTitleInput').value;
        const localDir = document.getElementById('localDirectoryInput').value.trim();

        if (!comicId) {
            alert('Vui lòng chọn bộ truyện!');
            return;
        }
        if (!chapterNumber) {
            alert('Vui lòng nhập số chapter!');
            return;
        }
        if (!localDir) {
            alert('Vui lòng nhập đường dẫn thư mục ảnh trên máy local!');
            return;
        }

        const comicText = comicSelect.options[comicSelect.selectedIndex].text;
        openProgressModal(comicText, `Chapter ${chapterNumber} (Quét từ thư mục Local)`, 1);

        try {
            // Step 1: Initialize local directory & get file list
            const initRes = await fetch(`{{ route('admin.tools.init-local') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    comic_id: comicId,
                    chapter_number: chapterNumber,
                    title: chapterTitle,
                    local_directory: localDir
                })
            });

            const initData = await initRes.json();
            if (!initData.success) {
                showModalError(initData.error || 'Có lỗi xảy ra khi quét thư mục!');
                return;
            }

            const files = initData.files;
            const chapterId = initData.chapter_id;
            const totalFiles = files.length;

            openProgressModal(initData.comic_title, `Chapter ${initData.chapter_number} - ${totalFiles} trang`, totalFiles);

            // Step 2: Upload sequentially page by page with auto-retry
            async function uploadLocalSequence(startIndex = 0) {
                for (let i = startIndex; i < totalFiles; i++) {
                    const item = files[i];
                    let retries = 0;
                    let success = false;

                    const chip = document.getElementById(`pageChip_${item.page_number}`);
                    if (chip) chip.className = 'upload-page-chip uploading';

                    while (retries < 3 && !success) {
                        try {
                            const pageRes = await fetch(`{{ route('admin.tools.upload-local-page') }}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    chapter_id: chapterId,
                                    page_number: item.page_number,
                                    file_path: item.file_path
                                })
                            });

                            const pageData = await pageRes.json();
                            if (pageData.success) {
                                success = true;
                                updateModalProgress(item.page_number, totalFiles, item.filename);
                            } else {
                                retries++;
                                if (retries >= 3) throw new Error(pageData.error || `Lỗi tải trang ${item.page_number}`);
                                await new Promise(r => setTimeout(r, 1000));
                            }
                        } catch (err) {
                            retries++;
                            if (retries >= 3) {
                                if (chip) chip.className = 'upload-page-chip failed';
                                showModalError(`Lỗi tải trang ${item.page_number} (${item.filename}): ${err.message}`, () => uploadLocalSequence(i));
                                return;
                            }
                            await new Promise(r => setTimeout(r, 1000));
                        }
                    }
                }

                // Step 3: Finish chapter
                document.getElementById('modalCurrentStatus').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang hoàn tất & cập nhật bộ nhớ đệm...';
                const finishRes = await fetch(`{{ route('admin.tools.finish-upload') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ chapter_id: chapterId })
                });

                const finishData = await finishRes.json();
                if (finishData.success) {
                    showModalSuccess(finishData);
                } else {
                    showModalError(finishData.error || 'Lỗi khi hoàn tất chapter');
                }
            }

            await uploadLocalSequence(0);

        } catch (e) {
            showModalError('Lỗi kết nối máy chủ: ' + e.message);
        }
    });

    // Process Mode 2: Multi-file Browser Sequential AJAX Upload
    document.getElementById('startBrowserUploadBtn').addEventListener('click', async function() {
        const comicId = comicSelect.value;
        const chapterNumber = document.getElementById('chapterNumberInput').value;
        const chapterTitle = document.getElementById('chapterTitleInput').value;
        const rawFiles = Array.from(browserFilesInput.files);

        if (!comicId) {
            alert('Vui lòng chọn bộ truyện!');
            return;
        }
        if (!chapterNumber) {
            alert('Vui lòng nhập số chapter!');
            return;
        }
        if (rawFiles.length === 0) {
            alert('Vui lòng chọn ít nhất 1 ảnh!');
            return;
        }

        // Natural sort files by filename
        rawFiles.sort((a, b) => a.name.localeCompare(b.name, undefined, { numeric: true, sensitivity: 'base' }));
        const totalFiles = rawFiles.length;

        const comicText = comicSelect.options[comicSelect.selectedIndex].text;
        openProgressModal(comicText, `Chapter ${chapterNumber} - Tổng ${totalFiles} trang tải lên`, totalFiles);

        try {
            // Step 1: Initialize chapter
            const initRes = await fetch(`{{ route('admin.tools.init-browser') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    comic_id: comicId,
                    chapter_number: chapterNumber,
                    title: chapterTitle,
                    total_files: totalFiles
                })
            });

            const initData = await initRes.json();
            if (!initData.success) {
                showModalError(initData.error || 'Lỗi khởi tạo chapter!');
                return;
            }

            const chapterId = initData.chapter_id;

            // Step 2: Upload sequentially page by page
            async function uploadBrowserSequence(startIndex = 0) {
                for (let i = startIndex; i < totalFiles; i++) {
                    const file = rawFiles[i];
                    const pageNumber = i + 1;
                    let retries = 0;
                    let success = false;

                    const chip = document.getElementById(`pageChip_${pageNumber}`);
                    if (chip) chip.className = 'upload-page-chip uploading';

                    while (retries < 3 && !success) {
                        try {
                            const formData = new FormData();
                            formData.append('chapter_id', chapterId);
                            formData.append('page_number', pageNumber);
                            formData.append('page_file', file);

                            const pageRes = await fetch(`{{ route('admin.tools.upload-browser-page') }}`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                            const pageData = await pageRes.json();
                            if (pageData.success) {
                                success = true;
                                updateModalProgress(pageNumber, totalFiles, file.name);
                            } else {
                                retries++;
                                if (retries >= 3) throw new Error(pageData.error || `Lỗi tải trang ${pageNumber}`);
                                await new Promise(r => setTimeout(r, 1000));
                            }
                        } catch (err) {
                            retries++;
                            if (retries >= 3) {
                                if (chip) chip.className = 'upload-page-chip failed';
                                showModalError(`Lỗi tải trang ${pageNumber} (${file.name}): ${err.message}`, () => uploadBrowserSequence(i));
                                return;
                            }
                            await new Promise(r => setTimeout(r, 1000));
                        }
                    }
                }

                // Step 3: Finish chapter
                document.getElementById('modalCurrentStatus').innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Đang hoàn tất & cập nhật bộ nhớ đệm...';
                const finishRes = await fetch(`{{ route('admin.tools.finish-upload') }}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ chapter_id: chapterId })
                });

                const finishData = await finishRes.json();
                if (finishData.success) {
                    showModalSuccess(finishData);
                } else {
                    showModalError(finishData.error || 'Lỗi khi hoàn tất chapter');
                }
            }

            await uploadBrowserSequence(0);

        } catch (e) {
            showModalError('Lỗi kết nối máy chủ: ' + e.message);
        }
    });
</script>
@endpush
@endsection
