<?php

namespace App\Services;

use App\Models\Comic;
use App\Models\Genre;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    const TTL_LONG = 3600; // 1 hour
    const TTL_MEDIUM = 600; // 10 minutes
    const TTL_SHORT = 180; // 3 minutes

    /**
     * Get featured/slider comics with cache
     */
    public function getFeaturedComics()
    {
        return Cache::remember('comics_featured', self::TTL_MEDIUM, function () {
            return Comic::with(['genres', 'latestChapter'])
                ->visible()
                ->where('is_featured', true)
                ->orderBy('views', 'desc')
                ->limit(6)
                ->get();
        });
    }

    /**
     * Get latest updated comics with cache
     */
    public function getLatestUpdatedComics($limit = 18)
    {
        return Cache::remember('comics_latest_' . $limit, self::TTL_SHORT, function () use ($limit) {
            return Comic::with(['genres', 'latestChapters'])
                ->visible()
                ->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get top comics ranked by all-time total views
     */
    public function getTopComics($limit = 10)
    {
        $cacheKey = "comics_top_all_{$limit}";
        return Cache::remember($cacheKey, self::TTL_MEDIUM, function () use ($limit) {
            return Comic::with(['genres', 'latestChapter'])
                ->visible()
                ->orderBy('views', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get all genres cached
     */
    public function getAllGenres()
    {
        return Cache::remember('all_genres', self::TTL_LONG, function () {
            return Genre::withCount(['comics' => function ($q) {
                $q->where('is_hidden', false);
            }])->orderBy('name', 'asc')->get();
        });
    }

    /**
     * Clear comic related caches when a comic or chapter is updated
     */
    public function clearComicCaches(?int $comicId = null, ?string $comicSlug = null): void
    {
        Cache::forget('comics_featured');
        Cache::forget('comics_latest_18');
        Cache::forget('comics_latest_12');
        Cache::forget('comics_top_all_10');
        Cache::forget('comics_top_day_10');
        Cache::forget('comics_top_week_10');
        Cache::forget('comics_top_month_10');
        Cache::forget('all_genres');

        if ($comicSlug) {
            Cache::forget('comic_detail_' . $comicSlug);
        }
    }
}
