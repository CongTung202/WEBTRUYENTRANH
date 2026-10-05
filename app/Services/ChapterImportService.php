<?php

namespace App\Services;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\ChapterPage;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChapterImportService
{
    protected CloudinaryService $cloudinaryService;
    protected CacheService $cacheService;

    public function __construct(
        CloudinaryService $cloudinaryService,
        CacheService $cacheService
    ) {
        $this->cloudinaryService = $cloudinaryService;
        $this->cacheService = $cacheService;
    }

    /**
     * Import a chapter from a local filesystem directory
     *
     * @param int $comicId
     * @param float $chapterNumber
     * @param string|null $title
     * @param string $localDirectory
     * @param callable|null $progressCallback function(int $current, int $total, string $file)
     * @return array
     */
    public function importFromLocalDirectory(
        int $comicId,
        float $chapterNumber,
        ?string $title,
        string $localDirectory,
        ?callable $progressCallback = null
    ): array {
        $comic = Comic::findOrFail($comicId);

        $cleanPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $localDirectory), DIRECTORY_SEPARATOR);
        if (!is_dir($cleanPath)) {
            throw new Exception("Thư mục không tồn tại: {$cleanPath}");
        }

        // Scan images from directory
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif'];
        $files = scandir($cleanPath);
        $imageFiles = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $fullPath = $cleanPath . DIRECTORY_SEPARATOR . $file;
            if (is_file($fullPath)) {
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                if (in_array($ext, $allowedExtensions)) {
                    $imageFiles[] = $fullPath;
                }
            }
        }

        if (empty($imageFiles)) {
            throw new Exception("Không tìm thấy tệp hình ảnh hợp lệ nào trong thư mục: {$cleanPath}");
        }

        // Natural sort files (1.jpg, 2.jpg, 10.jpg)
        natsort($imageFiles);
        $imageFiles = array_values($imageFiles);

        return $this->processAndUploadFiles(
            $comic,
            $chapterNumber,
            $title,
            $imageFiles,
            $progressCallback
        );
    }

    /**
     * Import a chapter from uploaded files
     *
     * @param int $comicId
     * @param float $chapterNumber
     * @param string|null $title
     * @param array $uploadedFiles Array of UploadedFile objects
     * @param callable|null $progressCallback
     * @return array
     */
    public function importFromUploadedFiles(
        int $comicId,
        float $chapterNumber,
        ?string $title,
        array $uploadedFiles,
        ?callable $progressCallback = null
    ): array {
        $comic = Comic::findOrFail($comicId);

        if (empty($uploadedFiles)) {
            throw new Exception("Vui lòng chọn ít nhất 1 ảnh cho chapter!");
        }

        // Sort by original filename naturally
        usort($uploadedFiles, function ($a, $b) {
            return strnatcmp($a->getClientOriginalName(), $b->getClientOriginalName());
        });

        $filePaths = [];
        foreach ($uploadedFiles as $file) {
            $filePaths[] = $file->getRealPath();
        }

        return $this->processAndUploadFiles(
            $comic,
            $chapterNumber,
            $title,
            $filePaths,
            $progressCallback
        );
    }

    /**
     * Internal processor to convert, upload to Cloudinary, and save to database
     */
    protected function processAndUploadFiles(
        Comic $comic,
        float $chapterNumber,
        ?string $title,
        array $filePaths,
        ?callable $progressCallback = null
    ): array {
        $totalFiles = count($filePaths);
        $chapterSlug = 'chap-' . (floor($chapterNumber) == $chapterNumber ? (int)$chapterNumber : str_replace('.', '-', (string)$chapterNumber));
        $cloudinaryFolder = "webtruyentranh/{$comic->slug}/{$chapterSlug}";

        // Start DB Transaction or find/create Chapter
        $chapter = Chapter::firstOrNew([
            'comic_id' => $comic->id,
            'chapter_number' => $chapterNumber,
        ]);

        $chapter->title = $title ?: ('Chapter ' . (floor($chapterNumber) == $chapterNumber ? (int)$chapterNumber : $chapterNumber));
        $chapter->slug = $chapterSlug;
        $chapter->save();

        // Delete old pages if re-uploading
        ChapterPage::where('chapter_id', $chapter->id)->delete();

        $pageResults = [];
        $pageNumber = 1;

        foreach ($filePaths as $filePath) {
            if ($progressCallback) {
                $progressCallback($pageNumber, $totalFiles, basename($filePath));
            }

            try {
                $publicId = (string) $pageNumber;
                $uploadRes = $this->cloudinaryService->uploadImage(
                    $filePath,
                    $cloudinaryFolder,
                    $publicId,
                    true // Convert to webp
                );

                $page = ChapterPage::create([
                    'chapter_id' => $chapter->id,
                    'page_number' => $pageNumber,
                    'image_url' => $uploadRes['secure_url'],
                    'cloudinary_public_id' => $uploadRes['public_id'],
                ]);

                $pageResults[] = [
                    'page_number' => $pageNumber,
                    'url' => $uploadRes['secure_url'],
                    'public_id' => $uploadRes['public_id'],
                ];
            } catch (\Throwable $e) {
                Log::error("Failed to upload page {$pageNumber} for chapter {$chapter->id}: " . $e->getMessage());
                throw new Exception("Lỗi khi tải trang số {$pageNumber}: " . $e->getMessage());
            }

            $pageNumber++;
        }

        // Touch comic updated_at
        $comic->touch();
        $this->cacheService->clearComicCaches($comic->id, $comic->slug);

        return [
            'success' => true,
            'comic' => $comic,
            'chapter' => $chapter,
            'total_pages' => count($pageResults),
            'cloudinary_folder' => $cloudinaryFolder,
            'pages' => $pageResults,
        ];
    }
}
