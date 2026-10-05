<?php

namespace App\Services\Crud;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\ChapterPage;
use App\Services\CloudinaryService;
use App\Services\CacheService;
use App\Services\ChapterImportService;
use Illuminate\Support\Facades\DB;

class ChapterCrudService
{
    protected CloudinaryService $cloudinaryService;
    protected CacheService $cacheService;
    protected ChapterImportService $chapterImportService;

    public function __construct(
        CloudinaryService $cloudinaryService,
        CacheService $cacheService,
        ChapterImportService $chapterImportService
    ) {
        $this->cloudinaryService = $cloudinaryService;
        $this->cacheService = $cacheService;
        $this->chapterImportService = $chapterImportService;
    }

    /**
     * List chapters for a comic
     */
    public function listChapters(Comic $comic, int $perPage = 20)
    {
        return $comic->chapters()
            ->withCount('pages')
            ->orderBy('chapter_number', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create chapter with pages
     */
    public function createChapter(Comic $comic, array $data, array $pageFiles): array
    {
        return $this->chapterImportService->importFromUploadedFiles(
            $comic->id,
            (float) $data['chapter_number'],
            $data['title'] ?? null,
            $pageFiles
        );
    }

    /**
     * Update chapter metadata
     */
    public function updateChapter(Comic $comic, Chapter $chapter, array $data): Chapter
    {
        $chapterNumber = (float) $data['chapter_number'];
        $slug = 'chap-' . (floor($chapterNumber) == $chapterNumber ? (int)$chapterNumber : str_replace('.', '-', (string)$chapterNumber));

        $chapter->update([
            'chapter_number' => $chapterNumber,
            'title' => $data['title'] ?? ('Chapter ' . $chapterNumber),
            'slug' => $slug,
        ]);

        $this->cacheService->clearComicCaches($comic->id, $comic->slug);

        return $chapter;
    }

    /**
     * Add more pages to chapter
     */
    public function addPages(Comic $comic, Chapter $chapter, array $pageFiles): int
    {
        $currentMaxPage = $chapter->pages()->max('page_number') ?? 0;
        $cloudinaryFolder = "webtruyentranh/{$comic->slug}/{$chapter->slug}";

        // Sort naturally
        usort($pageFiles, function ($a, $b) {
            return strnatcmp($a->getClientOriginalName(), $b->getClientOriginalName());
        });

        foreach ($pageFiles as $file) {
            $currentMaxPage++;
            $uploadRes = $this->cloudinaryService->uploadImage(
                $file->getRealPath(),
                $cloudinaryFolder,
                (string) $currentMaxPage,
                true
            );

            ChapterPage::create([
                'chapter_id' => $chapter->id,
                'page_number' => $currentMaxPage,
                'image_url' => $uploadRes['secure_url'],
                'cloudinary_public_id' => $uploadRes['public_id'],
            ]);
        }

        $comic->touch();
        $this->cacheService->clearComicCaches($comic->id, $comic->slug);

        return count($pageFiles);
    }

    /**
     * Reorder pages in batch via transaction
     */
    public function reorderPages(Comic $comic, Chapter $chapter, array $pageIds): void
    {
        DB::transaction(function () use ($chapter, $pageIds) {
            foreach ($pageIds as $index => $pageId) {
                ChapterPage::where('id', $pageId)
                    ->where('chapter_id', $chapter->id)
                    ->update(['page_number' => $index + 1]);
            }
        });

        $this->cacheService->clearComicCaches($comic->id, $comic->slug);
    }

    /**
     * Delete page and re-index remaining pages
     */
    public function deletePage(Comic $comic, Chapter $chapter, ChapterPage $page): void
    {
        if ($page->cloudinary_public_id) {
            $this->cloudinaryService->deleteImage($page->cloudinary_public_id);
        }

        $page->delete();

        // Renumber
        $pages = $chapter->pages()->orderBy('page_number', 'asc')->get();
        foreach ($pages as $index => $p) {
            $p->page_number = $index + 1;
            $p->save();
        }

        $this->cacheService->clearComicCaches($comic->id, $comic->slug);
    }

    /**
     * Delete entire chapter
     */
    public function deleteChapter(Comic $comic, Chapter $chapter): bool
    {
        $deleted = $chapter->delete();
        $this->cacheService->clearComicCaches($comic->id, $comic->slug);
        return $deleted;
    }
}
