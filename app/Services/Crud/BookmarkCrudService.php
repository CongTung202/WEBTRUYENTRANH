<?php

namespace App\Services\Crud;

use App\Models\Bookmark;

class BookmarkCrudService
{
    public function toggleBookmark(int $userId, int $comicId): array
    {
        $bookmark = Bookmark::where('user_id', $userId)
            ->where('comic_id', $comicId)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            return [
                'bookmarked' => false,
                'message' => 'Đã bỏ theo dõi truyện.',
            ];
        }

        Bookmark::create([
            'user_id' => $userId,
            'comic_id' => $comicId,
        ]);

        return [
            'bookmarked' => true,
            'message' => 'Đã thêm truyện vào tủ truyện theo dõi!',
        ];
    }

    public function getUserBookmarks(int $userId, int $perPage = 18)
    {
        return Bookmark::with(['comic.latestChapter', 'comic.genres'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
