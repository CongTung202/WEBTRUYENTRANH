<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Services\Crud\GenreCrudService;
use App\Http\Requests\Genre\StoreGenreRequest;
use App\Http\Requests\Genre\UpdateGenreRequest;

class GenreController extends Controller
{
    protected GenreCrudService $genreCrudService;

    public function __construct(GenreCrudService $genreCrudService)
    {
        $this->genreCrudService = $genreCrudService;
    }

    public function index()
    {
        $genres = $this->genreCrudService->listGenres();
        return view('admin.genres.index', compact('genres'));
    }

    public function store(StoreGenreRequest $request)
    {
        $genre = $this->genreCrudService->createGenre($request->validated());
        return redirect()->route('admin.genres.index')
            ->with('success', "Đã thêm thể loại '{$genre->name}'!");
    }

    public function update(UpdateGenreRequest $request, Genre $genre)
    {
        $this->genreCrudService->updateGenre($genre, $request->validated());
        return redirect()->route('admin.genres.index')
            ->with('success', "Đã cập nhật thể loại '{$genre->name}'!");
    }

    public function destroy(Genre $genre)
    {
        $name = $genre->name;
        $this->genreCrudService->deleteGenre($genre);
        return redirect()->route('admin.genres.index')
            ->with('success', "Đã xóa thể loại '{$name}'!");
    }
}
