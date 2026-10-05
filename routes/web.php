<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ComicController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\SearchApiController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ComicController as AdminComicController;
use App\Http\Controllers\Admin\ChapterController as AdminChapterController;
use App\Http\Controllers\Admin\GenreController as AdminGenreController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\FastChapterUploadController as AdminFastUploadController;

/*
|--------------------------------------------------------------------------
| Public & Reader Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/truyen', [ComicController::class, 'index'])->name('comics.index');
Route::get('/truyen/{slug}', [ComicController::class, 'show'])->name('comics.show');
Route::get('/truyen/{comicSlug}/chap-{chapterNumber}', [ReaderController::class, 'read'])->name('reader.read');

// Live search API
Route::get('/api/search', [SearchApiController::class, 'search'])->name('api.search');

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// User bookmarks and history (Accessible publicly with guest state fallback)
Route::get('/tu-truyen', [BookmarkController::class, 'index'])->name('bookmarks.index');
Route::get('/lich-su', [HistoryController::class, 'index'])->name('history.index');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::post('/lich-su/clear', [HistoryController::class, 'clear'])->name('history.clear');

    Route::post('/truyen/{id}/bookmark', [ComicController::class, 'toggleBookmark'])->name('comics.bookmark');
    Route::post('/truyen/{id}/rate', [ComicController::class, 'rate'])->name('comics.rate');

    Route::post('/comment', [CommentController::class, 'store'])->name('comments.store');
    Route::post('/comment/{id}/like', [CommentController::class, 'like'])->name('comments.like');
    Route::delete('/comment/{id}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Management Routes (Role: admin)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Comics & Chapters
    Route::post('comics/{comic}/toggle-visibility', [AdminComicController::class, 'toggleVisibility'])->name('comics.toggle-visibility');
    Route::resource('comics', AdminComicController::class);
    Route::resource('comics.chapters', AdminChapterController::class);
    Route::post('comics/{comic}/chapters/{chapter}/add-pages', [AdminChapterController::class, 'addPages'])->name('comics.chapters.add-pages');
    Route::post('comics/{comic}/chapters/{chapter}/reorder-pages', [AdminChapterController::class, 'reorderPages'])->name('comics.chapters.reorder-pages');
    Route::delete('comics/{comic}/chapters/{chapter}/pages/{page}', [AdminChapterController::class, 'deletePage'])->name('comics.chapters.delete-page');

    // Fast Upload Tool (Local Dir & Batch Upload with Chunked AJAX & Real-time Progress)
    Route::get('/fast-upload', [AdminFastUploadController::class, 'index'])->name('tools.fast-upload');
    Route::get('/fast-upload/comic-info/{id}', [AdminFastUploadController::class, 'getComicInfo'])->name('tools.comic-info');
    Route::post('/fast-upload/init-local', [AdminFastUploadController::class, 'initLocalDirectory'])->name('tools.init-local');
    Route::post('/fast-upload/upload-local-page', [AdminFastUploadController::class, 'uploadLocalPage'])->name('tools.upload-local-page');
    Route::post('/fast-upload/init-browser', [AdminFastUploadController::class, 'initBrowserUpload'])->name('tools.init-browser');
    Route::post('/fast-upload/upload-browser-page', [AdminFastUploadController::class, 'uploadBrowserPage'])->name('tools.upload-browser-page');
    Route::post('/fast-upload/finish', [AdminFastUploadController::class, 'finishChapter'])->name('tools.finish-upload');
    Route::post('/fast-upload/process-local', [AdminFastUploadController::class, 'processLocalDirectory'])->name('tools.process-local');
    Route::post('/fast-upload/process-files', [AdminFastUploadController::class, 'processBrowserFiles'])->name('tools.process-files');

    // Genres
    Route::resource('genres', AdminGenreController::class)->except(['create', 'show', 'edit']);

    // Users
    Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('users/{user}/toggle-role', [AdminUserController::class, 'toggleRole'])->name('users.toggle-role');
    Route::delete('users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Comments
    Route::get('comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::post('comments/{comment}/toggle-hide', [AdminCommentController::class, 'toggleHide'])->name('comments.toggle-hide');
    Route::delete('comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');
});
