<?php

namespace App\Http\Controllers;

use App\Services\Crud\CommentCrudService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    protected CommentCrudService $commentCrudService;

    public function __construct(CommentCrudService $commentCrudService)
    {
        $this->commentCrudService = $commentCrudService;
    }

    public function store(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Vui lòng đăng nhập để bình luận!'], 401);
        }

        $request->validate([
            'comic_id' => 'required|exists:comics,id',
            'chapter_id' => 'nullable|exists:chapters,id',
            'content' => 'required|string|min:2|max:1000',
        ]);

        $comment = $this->commentCrudService->createComment(
            auth()->id(),
            (int) $request->comic_id,
            $request->filled('chapter_id') ? (int) $request->chapter_id : null,
            $request->content
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'comment' => [
                    'id' => $comment->id,
                    'user_name' => $comment->user->name,
                    'user_avatar' => $comment->user->avatar_url,
                    'content' => e($comment->content),
                    'created_at' => $comment->created_at->diffForHumans(),
                    'likes' => 0,
                ],
                'message' => 'Bình luận thành công!',
            ]);
        }

        return back()->with('success', 'Bình luận thành công!');
    }

    public function like($id)
    {
        $likes = $this->commentCrudService->likeComment((int) $id);

        return response()->json([
            'success' => true,
            'likes' => $likes,
        ]);
    }

    public function destroy($id)
    {
        $comment = \App\Models\Comment::findOrFail($id);

        try {
            $this->commentCrudService->deleteComment($comment, auth()->user());
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa bình luận!',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}
