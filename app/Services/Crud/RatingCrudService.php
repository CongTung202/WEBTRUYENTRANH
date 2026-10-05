<?php

namespace App\Services\Crud;

use App\Models\Comic;
use App\Models\Rating;

class RatingCrudService
{
    public function rateComic(int $userId, int $comicId, int $score): array
    {
        $comic = Comic::findOrFail($comicId);

        Rating::updateOrCreate(
            ['user_id' => $userId, 'comic_id' => $comic->id],
            ['score' => $score]
        );

        $avgScore = Rating::where('comic_id', $comic->id)->avg('score');
        $ratingCount = Rating::where('comic_id', $comic->id)->count();

        $comic->rating_score = round($avgScore, 2);
        $comic->rating_count = $ratingCount;
        $comic->save();

        return [
            'rating_score' => $comic->rating_score,
            'rating_count' => $comic->rating_count,
            'message' => 'Cảm ơn bạn đã đánh giá truyện!',
        ];
    }
}
