<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use Illuminate\Http\Request;

class SearchApiController extends Controller
{
    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));

        if (empty($query)) {
            return response()->json([]);
        }

        $results = Comic::with(['latestChapter', 'genres'])
            ->visible()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('other_names', 'like', "%{$query}%")
                  ->orWhere('author', 'like', "%{$query}%");
            })
            ->orderBy('views', 'desc')
            ->limit(8)
            ->get()
            ->map(function ($comic) {
                return [
                    'id' => $comic->id,
                    'title' => $comic->title,
                    'slug' => $comic->slug,
                    'cover' => $comic->cover_url,
                    'views' => $comic->formatted_views,
                    'rating' => $comic->rating_score,
                    'latest_chapter' => $comic->latestChapter ? 'Chap ' . $comic->latestChapter->formatted_number : 'Đang cập nhật',
                    'genres' => $comic->genres->pluck('name')->take(2)->implode(', '),
                    'url' => route('comics.show', $comic->slug),
                ];
            });

        return response()->json($results);
    }
}
