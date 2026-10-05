<?php

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\Chapter;
use App\Models\ReadingHistory;
use Illuminate\Http\Request;

class ReaderController extends Controller
{
    public function read(string $comicSlug, $chapterNumber)
    {
        $comic = Comic::where('slug', $comicSlug)->firstOrFail();

        if ($comic->is_hidden && !(auth()->check() && auth()->user()->isAdmin())) {
            abort(404, 'Truyện này hiện đang tạm ẩn.');
        }

        $chapter = Chapter::with(['pages' => function ($q) {
            $q->orderBy('page_number', 'asc');
        }, 'comments.user'])
        ->where('comic_id', $comic->id)
        ->where('chapter_number', $chapterNumber)
        ->firstOrFail();

        // Increment chapter and comic views
        $viewKey = "viewed_chap_{$chapter->id}";
        if (!session()->has($viewKey)) {
            $chapter->increment('views');
            $comic->increment('views');
            session()->put($viewKey, true);
        }

        // Save reading history for logged in user
        if (auth()->check()) {
            ReadingHistory::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'comic_id' => $comic->id,
                ],
                [
                    'chapter_id' => $chapter->id,
                    'last_read_at' => now(),
                ]
            );
        }

        // All chapters for selector
        $allChapters = Chapter::select('id', 'comic_id', 'chapter_number', 'title', 'slug')
            ->where('comic_id', $comic->id)
            ->orderBy('chapter_number', 'desc')
            ->get();

        $prevChapter = $chapter->prevChapter;
        $nextChapter = $chapter->nextChapter;

        return view('reader.read', compact(
            'comic',
            'chapter',
            'allChapters',
            'prevChapter',
            'nextChapter'
        ));
    }
}
