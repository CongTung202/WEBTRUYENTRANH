<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $totalComics = Comic::count();
        $totalChapters = Chapter::count();
        $totalUsers = User::count();
        $totalViews = Comic::sum('views');

        $recentComics = Comic::with(['latestChapter', 'genres'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $topViewedComics = Comic::orderBy('views', 'desc')->limit(5)->get();

        $recentUsers = User::orderBy('created_at', 'desc')->limit(5)->get();
        $recentComments = Comment::with(['user', 'comic'])->orderBy('created_at', 'desc')->limit(5)->get();

        return view('admin.dashboard', compact(
            'totalComics',
            'totalChapters',
            'totalUsers',
            'totalViews',
            'recentComics',
            'topViewedComics',
            'recentUsers',
            'recentComments'
        ));
    }
}
