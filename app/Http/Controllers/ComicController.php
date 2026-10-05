<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Genre;
use App\Models\Rating;
use App\Models\ReadingHistory;
use App\Services\CacheService;
use App\Services\Crud\ComicCrudService;
use App\Services\Crud\BookmarkCrudService;
use App\Services\Crud\RatingCrudService;
use Illuminate\Http\Request;

class ComicController extends Controller
{
    protected CacheService $cacheService;
    protected ComicCrudService $comicCrudService;
    protected BookmarkCrudService $bookmarkCrudService;
    protected RatingCrudService $ratingCrudService;

    public function __construct(
        CacheService $cacheService,
        ComicCrudService $comicCrudService,
        BookmarkCrudService $bookmarkCrudService,
        RatingCrudService $ratingCrudService
    ) {
        $this->cacheService = $cacheService;
        $this->comicCrudService = $comicCrudService;
        $this->bookmarkCrudService = $bookmarkCrudService;
        $this->ratingCrudService = $ratingCrudService;
    }

    /**
     * Comic list, filter and search page
     */
    public function index(Request $request)
    {
        $comics = $this->comicCrudService->listComics($request->all(), 24);
        $genres = $this->cacheService->getAllGenres();
        
        $selectedGenres = [];
        if ($request->filled('genres')) {
            $selectedGenres = is_array($request->genres) ? $request->genres : explode(',', $request->genres);
        } elseif ($request->filled('genre')) {
            $selectedGenres = is_array($request->genre) ? $request->genre : explode(',', $request->genre);
        }
        $selectedGenres = array_values(array_filter(array_map('trim', $selectedGenres)));

        $sort = $request->get('sort', 'latest');
        $status = $request->get('status');

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            $html = view('comics.partials.comics_grid', compact('comics'))->render();
            $title = !empty($selectedGenres) 
                ? 'Thể loại: ' . $genres->whereIn('slug', $selectedGenres)->pluck('name')->join(', ')
                : (request('q') ? 'Kết quả tìm kiếm cho: "' . e(request('q')) . '"' : 'Danh Sách Truyện Tranh');

            return response()->json([
                'html' => $html,
                'total' => $comics->total(),
                'title' => $title,
                'selected_count' => count($selectedGenres),
            ]);
        }

        return view('comics.index', compact('comics', 'genres', 'selectedGenres', 'sort', 'status'));
    }

    /**
     * Comic detail page
     */
    public function show(string $slug)
    {
        $comic = Comic::with([
            'genres',
            'chapters' => function ($q) {
                $q->orderBy('chapter_number', 'desc');
            },
            'comments.user',
        ])->where('slug', $slug)->firstOrFail();

        if ($comic->is_hidden && !(auth()->check() && auth()->user()->isAdmin())) {
            abort(404, 'Truyện này hiện đang tạm ẩn.');
        }

        // Increment views with session prevention
        $viewKey = 'viewed_comic_' . $comic->id;
        if (!session()->has($viewKey)) {
            $comic->increment('views');
            $comic->increment('daily_views');
            $comic->increment('weekly_views');
            $comic->increment('monthly_views');
            session()->put($viewKey, true);
        }

        $isBookmarked = auth()->check() ? $comic->isBookmarkedBy(auth()->user()) : false;
        
        $userRating = null;
        if (auth()->check()) {
            $ratingRecord = Rating::where('user_id', auth()->id())
                ->where('comic_id', $comic->id)
                ->first();
            $userRating = $ratingRecord ? $ratingRecord->score : null;
        }

        $lastReadHistory = null;
        if (auth()->check()) {
            $lastReadHistory = ReadingHistory::where('user_id', auth()->id())
                ->where('comic_id', $comic->id)
                ->with('chapter')
                ->first();
        }

        $firstChapter = $comic->chapters->sortBy('chapter_number')->first();
        $latestChapter = $comic->chapters->sortByDesc('chapter_number')->first();

        // Related comics by genre
        $genreIds = $comic->genres->pluck('id');
        $relatedComics = Comic::with(['latestChapter', 'genres'])->whereHas('genres', function ($q) use ($genreIds) {
            $q->whereIn('genres.id', $genreIds);
        })->where('id', '!=', $comic->id)->limit(6)->get();

        return view('comics.show', compact(
            'comic',
            'isBookmarked',
            'userRating',
            'lastReadHistory',
            'firstChapter',
            'latestChapter',
            'relatedComics'
        ));
    }

    /**
     * Toggle bookmark
     */
    public function toggleBookmark($id)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Vui lòng đăng nhập để lưu truyện!'], 401);
        }

        $result = $this->bookmarkCrudService->toggleBookmark(auth()->id(), (int) $id);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'bookmarked' => $result['bookmarked'],
                'message' => $result['message'],
            ]);
        }

        return back()->with('success', $result['message']);
    }

    /**
     * Submit rating
     */
    public function rate(Request $request, $id)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Vui lòng đăng nhập để đánh giá!'], 401);
        }

        $request->validate([
            'score' => 'required|integer|min:1|max:5',
        ]);

        $result = $this->ratingCrudService->rateComic(auth()->id(), (int) $id, (int) $request->score);

        return response()->json([
            'success' => true,
            'rating_score' => $result['rating_score'],
            'rating_count' => $result['rating_count'],
            'message' => $result['message'],
        ]);
    }
}
