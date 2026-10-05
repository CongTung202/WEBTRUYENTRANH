<?php

namespace App\Services\Crud;

use App\Models\ReadingHistory;

class HistoryCrudService
{
    public function recordHistory(int $userId, int $comicId, int $chapterId): ReadingHistory
    {
        return ReadingHistory::updateOrCreate(
            [
                'user_id' => $userId,
                'comic_id' => $comicId,
            ],
            [
                'chapter_id' => $chapterId,
                'last_read_at' => now(),
            ]
        );
    }

    public function getUserHistories(int $userId, int $perPage = 18)
    {
        return ReadingHistory::with(['comic.latestChapter', 'chapter'])
            ->where('user_id', $userId)
            ->orderBy('last_read_at', 'desc')
            ->paginate($perPage);
    }

    public function clearUserHistories(int $userId): int
    {
        return ReadingHistory::where('user_id', $userId)->delete();
    }
}
