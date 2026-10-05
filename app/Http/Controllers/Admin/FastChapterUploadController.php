<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\ChapterPage;
use App\Services\CloudinaryService;
use App\Services\CacheService;
use App\Services\ChapterImportService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FastChapterUploadController extends Controller
{
    protected ChapterImportService $importService;
    protected CloudinaryService $cloudinaryService;
    protected CacheService $cacheService;

    public function __construct(
        ChapterImportService $importService,
        CloudinaryService $cloudinaryService,
        CacheService $cacheService
    ) {
        $this->importService = $importService;
        $this->cloudinaryService = $cloudinaryService;
        $this->cacheService = $cacheService;
    }

    public function index()
    {
        $comics = Comic::with('latestChapter')->orderBy('title')->get();
        return view('admin.tools.fast-upload', compact('comics'));
    }

    /**
     * Get comic info via AJAX (latest chapter, title, slug)
     */
    public function getComicInfo($id)
    {
        $comic = Comic::with('latestChapter')->findOrFail($id);
        $nextChap = $comic->latestChapter ? ($comic->latestChapter->chapter_number + 1) : 1;

        return response()->json([
            'id' => $comic->id,
            'title' => $comic->title,
            'slug' => $comic->slug,
            'latest_chapter' => $comic->latestChapter ? $comic->latestChapter->chapter_number : 0,
            'suggested_next_chapter' => $nextChap,
            'cover' => $comic->cover_url,
        ]);
    }

    /**
     * Step 1 (Local): Scan folder, sort images, initialize chapter record
     */
    public function initLocalDirectory(Request $request)
    {
        $request->validate([
            'comic_id' => 'required|exists:comics,id',
            'chapter_number' => 'required|numeric|min:0',
            'title' => 'nullable|string|max:255',
            'local_directory' => 'required|string',
        ]);

        try {
            $comic = Comic::findOrFail($request->comic_id);
            $cleanPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $request->local_directory), DIRECTORY_SEPARATOR);

            if (!is_dir($cleanPath)) {
                return response()->json([
                    'success' => false,
                    'error' => "Thư mục không tồn tại: {$cleanPath}",
                ], 422);
            }

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif'];
            $files = scandir($cleanPath);
            $imageFiles = [];

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;
                $fullPath = $cleanPath . DIRECTORY_SEPARATOR . $file;
                if (is_file($fullPath)) {
                    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowedExtensions)) {
                        $imageFiles[] = $fullPath;
                    }
                }
            }

            if (empty($imageFiles)) {
                return response()->json([
                    'success' => false,
                    'error' => "Không tìm thấy tệp hình ảnh hợp lệ (jpg, png, webp,...) nào trong thư mục: {$cleanPath}",
                ], 422);
            }

            natsort($imageFiles);
            $imageFiles = array_values($imageFiles);

            $chapterNumber = (float) $request->chapter_number;
            $chapterSlug = 'chap-' . (floor($chapterNumber) == $chapterNumber ? (int)$chapterNumber : str_replace('.', '-', (string)$chapterNumber));

            $chapter = Chapter::firstOrNew([
                'comic_id' => $comic->id,
                'chapter_number' => $chapterNumber,
            ]);
            $chapter->title = $request->title ?: ('Chapter ' . (floor($chapterNumber) == $chapterNumber ? (int)$chapterNumber : $chapterNumber));
            $chapter->slug = $chapterSlug;
            $chapter->save();

            // Clear old pages if re-uploading
            ChapterPage::where('chapter_id', $chapter->id)->delete();

            $fileList = [];
            foreach ($imageFiles as $index => $filePath) {
                $fileList[] = [
                    'page_number' => $index + 1,
                    'filename' => basename($filePath),
                    'file_path' => $filePath,
                ];
            }

            return response()->json([
                'success' => true,
                'comic_id' => $comic->id,
                'comic_title' => $comic->title,
                'chapter_id' => $chapter->id,
                'chapter_number' => $chapter->formatted_number,
                'chapter_title' => $chapter->title,
                'total_files' => count($fileList),
                'files' => $fileList,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => 'Lỗi khởi tạo thư mục: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Step 2 (Local): Upload a single page file by path
     */
    public function uploadLocalPage(Request $request)
    {
        $request->validate([
            'chapter_id' => 'required|exists:chapters,id',
            'page_number' => 'required|integer|min:1',
            'file_path' => 'required|string',
        ]);

        @set_time_limit(120);

        try {
            $chapter = Chapter::with('comic')->findOrFail($request->chapter_id);
            $comic = $chapter->comic;
            $filePath = $request->file_path;

            if (!file_exists($filePath)) {
                throw new Exception("Tệp không tồn tại: {$filePath}");
            }

            $cloudinaryFolder = "webtruyentranh/{$comic->slug}/{$chapter->slug}";
            $publicId = (string) $request->page_number;

            $uploadRes = $this->cloudinaryService->uploadImage(
                $filePath,
                $cloudinaryFolder,
                $publicId,
                true // Convert to webp
            );

            // Update or create chapter page
            ChapterPage::updateOrCreate(
                [
                    'chapter_id' => $chapter->id,
                    'page_number' => (int) $request->page_number,
                ],
                [
                    'image_url' => $uploadRes['secure_url'],
                    'cloudinary_public_id' => $uploadRes['public_id'],
                ]
            );

            return response()->json([
                'success' => true,
                'page_number' => (int) $request->page_number,
                'url' => $uploadRes['secure_url'],
            ]);
        } catch (\Throwable $e) {
            Log::error("uploadLocalPage error page {$request->page_number}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Step 1 (Browser): Initialize chapter for browser multi-file upload
     */
    public function initBrowserUpload(Request $request)
    {
        $request->validate([
            'comic_id' => 'required|exists:comics,id',
            'chapter_number' => 'required|numeric|min:0',
            'title' => 'nullable|string|max:255',
            'total_files' => 'required|integer|min:1',
        ]);

        try {
            $comic = Comic::findOrFail($request->comic_id);
            $chapterNumber = (float) $request->chapter_number;
            $chapterSlug = 'chap-' . (floor($chapterNumber) == $chapterNumber ? (int)$chapterNumber : str_replace('.', '-', (string)$chapterNumber));

            $chapter = Chapter::firstOrNew([
                'comic_id' => $comic->id,
                'chapter_number' => $chapterNumber,
            ]);
            $chapter->title = $request->title ?: ('Chapter ' . (floor($chapterNumber) == $chapterNumber ? (int)$chapterNumber : $chapterNumber));
            $chapter->slug = $chapterSlug;
            $chapter->save();

            ChapterPage::where('chapter_id', $chapter->id)->delete();

            return response()->json([
                'success' => true,
                'comic_id' => $comic->id,
                'comic_title' => $comic->title,
                'chapter_id' => $chapter->id,
                'chapter_number' => $chapter->formatted_number,
                'chapter_title' => $chapter->title,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Step 2 (Browser): Upload single page via multipart file
     */
    public function uploadBrowserPage(Request $request)
    {
        $request->validate([
            'chapter_id' => 'required|exists:chapters,id',
            'page_number' => 'required|integer|min:1',
            'page_file' => 'required|image|max:20480',
        ]);

        @set_time_limit(120);

        try {
            $chapter = Chapter::with('comic')->findOrFail($request->chapter_id);
            $comic = $chapter->comic;
            $file = $request->file('page_file');

            $cloudinaryFolder = "webtruyentranh/{$comic->slug}/{$chapter->slug}";
            $publicId = (string) $request->page_number;

            $uploadRes = $this->cloudinaryService->uploadImage(
                $file->getRealPath(),
                $cloudinaryFolder,
                $publicId,
                true
            );

            ChapterPage::updateOrCreate(
                [
                    'chapter_id' => $chapter->id,
                    'page_number' => (int) $request->page_number,
                ],
                [
                    'image_url' => $uploadRes['secure_url'],
                    'cloudinary_public_id' => $uploadRes['public_id'],
                ]
            );

            return response()->json([
                'success' => true,
                'page_number' => (int) $request->page_number,
                'url' => $uploadRes['secure_url'],
            ]);
        } catch (\Throwable $e) {
            Log::error("uploadBrowserPage error page {$request->page_number}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Step 3: Finalize chapter upload (touch comic, clear caches, generate summary)
     */
    public function finishChapter(Request $request)
    {
        $request->validate([
            'chapter_id' => 'required|exists:chapters,id',
        ]);

        try {
            $chapter = Chapter::with('comic')->findOrFail($request->chapter_id);
            $comic = $chapter->comic;

            // Touch comic timestamp
            $comic->touch();
            $this->cacheService->clearComicCaches($comic->id, $comic->slug);

            $totalPages = ChapterPage::where('chapter_id', $chapter->id)->count();

            return response()->json([
                'success' => true,
                'message' => "Đăng thành công Chapter {$chapter->formatted_number} cho bộ truyện '{$comic->title}' với {$totalPages} trang",
                'comic_title' => $comic->title,
                'chapter_number' => $chapter->formatted_number,
                'total_pages' => $totalPages,
                'read_url' => route('reader.read', ['comicSlug' => $comic->slug, 'chapterNumber' => $chapter->chapter_number]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Legacy Fallback methods
     */
    public function processLocalDirectory(Request $request)
    {
        return $this->importService->importFromLocalDirectory(
            (int) $request->comic_id,
            (float) $request->chapter_number,
            $request->title,
            $request->local_directory
        );
    }

    public function processBrowserFiles(Request $request)
    {
        return $this->importService->importFromUploadedFiles(
            (int) $request->comic_id,
            (float) $request->chapter_number,
            $request->title,
            $request->file('pages')
        );
    }
}
