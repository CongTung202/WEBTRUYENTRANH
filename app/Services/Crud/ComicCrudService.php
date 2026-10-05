<?php

namespace App\Services\Crud;

use App\Models\Comic;
use App\Models\Genre;
use App\Services\CloudinaryService;
use App\Services\CacheService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ComicCrudService
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
     * Get paginated comics list with filters
     */
    public function listComics(array $filters = [], int $perPage = 15)
    {
        $query = Comic::with(['genres', 'chapters']);

        // Check if admin allows hidden comics or frontend requires visible only
        if (!empty($filters['include_hidden'])) {
            if (!empty($filters['visibility'])) {
                if ($filters['visibility'] === 'hidden') {
                    $query->where('is_hidden', true);
                } elseif ($filters['visibility'] === 'visible') {
                    $query->where('is_hidden', false);
                }
            }
        } else {
            $query->visible();
        }

        if (!empty($filters['q'])) {
            $keyword = trim($filters['q']);
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('author', 'like', "%{$keyword}%")
                  ->orWhere('other_names', 'like', "%{$keyword}%");
            });
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['ongoing', 'completed', 'dropped'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by multiple genres or single genre
        $selectedGenres = [];
        if (!empty($filters['genres'])) {
            $selectedGenres = is_array($filters['genres']) ? $filters['genres'] : explode(',', $filters['genres']);
        } elseif (!empty($filters['genre'])) {
            $selectedGenres = is_array($filters['genre']) ? $filters['genre'] : explode(',', $filters['genre']);
        }
        $selectedGenres = array_filter(array_map('trim', $selectedGenres));

        // Filter by multiple genres with AND condition (must contain ALL selected tags)
        if (!empty($selectedGenres)) {
            foreach ($selectedGenres as $slug) {
                $query->whereHas('genres', function ($q) use ($slug) {
                    $q->where('slug', $slug);
                });
            }
        }

        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'views' => $query->orderBy('views', 'desc')->orderBy('id', 'desc'),
            'rating' => $query->orderBy('rating_score', 'desc')->orderBy('rating_count', 'desc')->orderBy('views', 'desc'),
            'title', 'title_asc' => $query->orderBy('title', 'asc'),
            'title_desc' => $query->orderBy('title', 'desc'),
            'newest' => $query->orderBy('created_at', 'desc')->orderBy('id', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc')->orderBy('id', 'asc'),
            default => $query->orderByRaw('COALESCE((SELECT MAX(created_at) FROM chapters WHERE chapters.comic_id = comics.id), comics.updated_at) DESC')->orderBy('id', 'desc'),
        };

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Create a new comic with cover & banner image processing and auto genre tagging
     */
    public function createComic(array $data, ?UploadedFile $coverFile = null, ?UploadedFile $bannerFile = null): Comic
    {
        $slug = Str::slug($data['title']);
        $coverUrl = asset('images/default.png');
        $bannerUrl = null;

        if ($coverFile) {
            $uploadRes = $this->cloudinaryService->uploadImage(
                $coverFile->getRealPath(),
                "webtruyentranh/{$slug}",
                "cover",
                true
            );
            $coverUrl = $uploadRes['secure_url'];
        }

        if ($bannerFile) {
            $uploadBanner = $this->cloudinaryService->uploadImage(
                $bannerFile->getRealPath(),
                "webtruyentranh/{$slug}",
                "banner",
                true
            );
            $bannerUrl = $uploadBanner['secure_url'];
        }

        $comic = Comic::create([
            'title' => $data['title'],
            'slug' => $slug,
            'author' => $data['author'] ?? null,
            'other_names' => $data['other_names'] ?? null,
            'description' => $data['description'] ?? null,
            'cover_image' => $coverUrl,
            'banner_image' => $bannerUrl,
            'status' => $data['status'] ?? 'ongoing',
            'is_featured' => !empty($data['is_featured']),
            'is_recommended' => !empty($data['is_recommended']),
            'is_hidden' => !empty($data['is_hidden']),
        ]);

        $this->syncGenres($comic, $data['genres'] ?? []);

        $this->cacheService->clearComicCaches();

        return $comic;
    }

    /**
     * Update existing comic
     */
    public function updateComic(Comic $comic, array $data, ?UploadedFile $coverFile = null, ?UploadedFile $bannerFile = null): Comic
    {
        $slug = Str::slug($data['title']);
        $coverUrl = $comic->cover_image;
        $bannerUrl = $comic->banner_image;

        if ($coverFile) {
            $uploadRes = $this->cloudinaryService->uploadImage(
                $coverFile->getRealPath(),
                "webtruyentranh/{$slug}",
                "cover_" . time(),
                true
            );
            $coverUrl = $uploadRes['secure_url'];
        }

        if ($bannerFile) {
            $uploadBanner = $this->cloudinaryService->uploadImage(
                $bannerFile->getRealPath(),
                "webtruyentranh/{$slug}",
                "banner_" . time(),
                true
            );
            $bannerUrl = $uploadBanner['secure_url'];
        }

        $comic->update([
            'title' => $data['title'],
            'slug' => $slug,
            'author' => $data['author'] ?? null,
            'other_names' => $data['other_names'] ?? null,
            'description' => $data['description'] ?? null,
            'cover_image' => $coverUrl,
            'banner_image' => $bannerUrl,
            'status' => $data['status'] ?? 'ongoing',
            'is_featured' => !empty($data['is_featured']),
            'is_recommended' => !empty($data['is_recommended']),
            'is_hidden' => !empty($data['is_hidden']),
        ]);

        $this->syncGenres($comic, $data['genres'] ?? []);

        $this->cacheService->clearComicCaches($comic->id, $comic->slug);

        return $comic;
    }

    /**
     * Toggle comic visibility (hide/show)
     */
    public function toggleVisibility(Comic $comic): bool
    {
        $comic->is_hidden = !$comic->is_hidden;
        $comic->save();
        $this->cacheService->clearComicCaches($comic->id, $comic->slug);

        return $comic->is_hidden;
    }

    /**
     * Helper to sync genres, auto-creating any new genre tag on the fly
     */
    protected function syncGenres(Comic $comic, ?array $genres): void
    {
        if (empty($genres)) {
            $comic->genres()->detach();
            return;
        }

        $genreIds = [];
        foreach ($genres as $genreItem) {
            $trimmed = trim($genreItem);
            if (empty($trimmed)) continue;

            if (is_numeric($trimmed)) {
                $genre = Genre::find((int) $trimmed);
                if ($genre) {
                    $genreIds[] = $genre->id;
                    continue;
                }
            }

            // Search by name or slug, or create new
            $slug = Str::slug($trimmed);
            $genre = Genre::firstOrCreate(
                ['slug' => $slug],
                ['name' => $trimmed]
            );
            $genreIds[] = $genre->id;
        }

        $comic->genres()->sync(array_unique($genreIds));
    }

    /**
     * Delete comic and clear caches
     */
    public function deleteComic(Comic $comic): bool
    {
        $comicId = $comic->id;
        $comicSlug = $comic->slug;

        $deleted = $comic->delete();
        $this->cacheService->clearComicCaches($comicId, $comicSlug);

        return $deleted;
    }
}
