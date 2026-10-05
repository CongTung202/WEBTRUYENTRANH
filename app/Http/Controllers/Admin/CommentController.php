<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Services\Crud\CommentCrudService;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    protected CommentCrudService $commentCrudService;

    public function __construct(CommentCrudService $commentCrudService)
    {
        $this->commentCrudService = $commentCrudService;
    }

    public function index(Request $request)
    {
        $comments = $this->commentCrudService->listComments($request->all(), 20);
        return view('admin.comments.index', compact('comments'));
    }

    public function toggleHide(Comment $comment)
    {
        $isHidden = $this->commentCrudService->toggleHideComment($comment);
        $status = $isHidden ? 'ẩn' : 'hiển thị';
        return back()->with('success', "Đã {$status} bình luận!");
    }

    public function destroy(Comment $comment)
    {
        try {
            $this->commentCrudService->deleteComment($comment, auth()->user());
            return back()->with('success', 'Đã xóa bình luận thành công!');
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
