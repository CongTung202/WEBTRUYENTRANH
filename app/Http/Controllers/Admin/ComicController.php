<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\Genre;
use App\Services\Crud\ComicCrudService;
use App\Http\Requests\Comic\StoreComicRequest;
use App\Http\Requests\Comic\UpdateComicRequest;
use Illuminate\Http\Request;

class ComicController extends Controller
{
    protected ComicCrudService $comicCrudService;

    public function __construct(ComicCrudService $comicCrudService)
    {
        $this->comicCrudService = $comicCrudService;
    }

    public function index(Request $request)
    {
        $filters = array_merge($request->all(), ['include_hidden' => true]);
        $comics = $this->comicCrudService->listComics($filters, 15);
        return view('admin.comics.index', compact('comics'));
    }

    public function toggleVisibility(Comic $comic)
    {
        $isHidden = $this->comicCrudService->toggleVisibility($comic);
        $stateText = $isHidden ? 'đã ẩn khỏi' : 'đã hiển thị trên';

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_hidden' => $isHidden,
                'message' => "Truyện '{$comic->title}' {$stateText} trang người dùng.",
            ]);
        }

        return back()->with('success', "Truyện '{$comic->title}' {$stateText} trang người dùng.");
    }

    public function create()
    {
        $genres = Genre::orderBy('name')->get();
        return view('admin.comics.create', compact('genres'));
    }

    public function store(StoreComicRequest $request)
    {
        $comic = $this->comicCrudService->createComic(
            $request->validated(),
            $request->file('cover_image'),
            $request->file('banner_image')
        );

        return redirect()->route('admin.comics.index')
            ->with('success', "Đã thêm truyện '{$comic->title}' thành công!");
    }

    public function edit(Comic $comic)
    {
        $genres = Genre::orderBy('name')->get();
        $selectedGenres = $comic->genres->pluck('id')->toArray();

        return view('admin.comics.edit', compact('comic', 'genres', 'selectedGenres'));
    }

    public function update(UpdateComicRequest $request, Comic $comic)
    {
        $this->comicCrudService->updateComic(
            $comic,
            $request->validated(),
            $request->file('cover_image'),
            $request->file('banner_image')
        );

        return redirect()->route('admin.comics.index')
            ->with('success', "Cập nhật truyện '{$comic->title}' thành công!");
    }

    public function destroy(Comic $comic)
    {
        $title = $comic->title;
        $this->comicCrudService->deleteComic($comic);

        return redirect()->route('admin.comics.index')
            ->with('success', "Đã xóa truyện '{$title}'!");
    }
}
