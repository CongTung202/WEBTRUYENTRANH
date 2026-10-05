<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Genre;
use App\Models\ReadingHistory;
use App\Services\CacheService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index()
    {
        $featuredComics = $this->cacheService->getFeaturedComics();
        $latestComics = $this->cacheService->getLatestUpdatedComics(18);
        $topComics = $this->cacheService->getTopComics(10);
        $genres = $this->cacheService->getAllGenres();

        $recentHistories = collect();
        if (auth()->check()) {
            $recentHistories = ReadingHistory::with(['comic', 'chapter'])
                ->where('user_id', auth()->id())
                ->orderBy('last_read_at', 'desc')
                ->limit(6)
                ->get();
        }

        return view('home.index', compact(
            'featuredComics',
            'latestComics',
            'topComics',
            'genres',
            'recentHistories'
        ));
    }
}
