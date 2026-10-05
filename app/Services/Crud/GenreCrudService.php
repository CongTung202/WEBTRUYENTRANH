<?php

namespace App\Services\Crud;

use App\Models\Genre;
use App\Services\CacheService;
use Illuminate\Support\Str;

class GenreCrudService
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function listGenres()
    {
        return Genre::withCount('comics')->orderBy('name')->get();
    }

    public function createGenre(array $data): Genre
    {
        $genre = Genre::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        $this->cacheService->clearComicCaches();
        return $genre;
    }

    public function updateGenre(Genre $genre, array $data): Genre
    {
        $genre->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
        ]);

        $this->cacheService->clearComicCaches();
        return $genre;
    }

    public function deleteGenre(Genre $genre): bool
    {
        $deleted = $genre->delete();
        $this->cacheService->clearComicCaches();
        return $deleted;
    }
}
