<?php

namespace App\Services\Crud;

use App\Models\Comment;
use App\Models\User;
use Exception;

class CommentCrudService
{
    public function listComments(array $filters = [], int $perPage = 20)
    {
        $query = Comment::with(['user', 'comic', 'chapter']);

        if (!empty($filters['q'])) {
            $keyword = trim($filters['q']);
            $query->where('content', 'like', "%{$keyword}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }

    public function createComment(int $userId, int $comicId, ?int $chapterId, string $content): Comment
    {
        $comment = Comment::create([
            'user_id' => $userId,
            'comic_id' => $comicId,
            'chapter_id' => $chapterId,
            'content' => strip_tags(trim($content)),
        ]);

        $comment->load('user');
        return $comment;
    }

    public function likeComment(int $commentId): int
    {
        $comment = Comment::findOrFail($commentId);
        $comment->increment('likes');
        return $comment->likes;
    }

    public function toggleHideComment(Comment $comment): bool
    {
        $comment->is_hidden = !$comment->is_hidden;
        $comment->save();
        return $comment->is_hidden;
    }

    public function deleteComment(Comment $comment, ?User $currentUser): bool
    {
        if (!$currentUser) {
            throw new Exception('Chưa đăng nhập!');
        }

        if ($comment->user_id !== $currentUser->id && !$currentUser->isAdmin()) {
            throw new Exception('Bạn không có quyền xóa bình luận này!');
        }

        return $comment->delete();
    }
}
