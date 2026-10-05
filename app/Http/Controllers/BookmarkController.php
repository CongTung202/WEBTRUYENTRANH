<?php

namespace App\Http\Controllers;

use App\Services\Crud\BookmarkCrudService;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    protected BookmarkCrudService $bookmarkCrudService;

    public function __construct(BookmarkCrudService $bookmarkCrudService)
    {
        $this->bookmarkCrudService = $bookmarkCrudService;
    }

    public function index()
    {
        if (auth()->check()) {
            $bookmarks = $this->bookmarkCrudService->getUserBookmarks(auth()->id(), 18);
        } else {
            $bookmarks = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 18);
        }
        return view('user.bookmarks', compact('bookmarks'));
    }
}
