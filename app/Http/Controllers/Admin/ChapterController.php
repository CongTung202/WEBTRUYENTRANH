<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\ChapterPage;
use App\Services\Crud\ChapterCrudService;
use App\Http\Requests\Chapter\StoreChapterRequest;
use App\Http\Requests\Chapter\UpdateChapterRequest;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    protected ChapterCrudService $chapterCrudService;

    public function __construct(ChapterCrudService $chapterCrudService)
    {
        $this->chapterCrudService = $chapterCrudService;
    }

    public function index(Request $request, Comic $comic)
    {
        $chapters = $this->chapterCrudService->listChapters($comic, 20);
        return view('admin.chapters.index', compact('comic', 'chapters'));
    }

    public function create(Comic $comic)
    {
        $latest = $comic->latestChapter;
        $suggestedNumber = $latest ? ($latest->chapter_number + 1) : 1;

        return view('admin.chapters.create', compact('comic', 'suggestedNumber'));
    }

    public function store(StoreChapterRequest $request, Comic $comic)
    {
        try {
            $this->chapterCrudService->createChapter(
                $comic,
                $request->validated(),
                $request->file('pages')
            );

            return redirect()->route('admin.comics.chapters.index', $comic->id)
                ->with('success', "Đã thêm Chapter {$request->chapter_number} thành công!");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(Comic $comic, Chapter $chapter)
    {
        $pages = $chapter->pages()->orderBy('page_number', 'asc')->get();
        return view('admin.chapters.edit', compact('comic', 'chapter', 'pages'));
    }

    public function update(UpdateChapterRequest $request, Comic $comic, Chapter $chapter)
    {
        $this->chapterCrudService->updateChapter($comic, $chapter, $request->validated());

        return redirect()->route('admin.comics.chapters.index', $comic->id)
            ->with('success', "Cập nhật Chapter {$chapter->chapter_number} thành công!");
    }

    public function addPages(Request $request, Comic $comic, Chapter $chapter)
    {
        $request->validate([
            'new_pages' => 'required|array|min:1',
            'new_pages.*' => 'image|max:15360',
        ]);

        try {
            $count = $this->chapterCrudService->addPages($comic, $chapter, $request->file('new_pages'));

            return back()->with('success', "Đã thêm thành công {$count} trang ảnh mới vào Chapter {$chapter->formatted_number}!");
        } catch (\Throwable $e) {
            return back()->with('error', 'Lỗi khi thêm trang ảnh: ' . $e->getMessage());
        }
    }

    public function reorderPages(Request $request, Comic $comic, Chapter $chapter)
    {
        $request->validate([
            'page_ids' => 'required|array',
            'page_ids.*' => 'integer|exists:chapter_pages,id',
        ]);

        $this->chapterCrudService->reorderPages($comic, $chapter, $request->page_ids);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật thứ tự các trang ảnh thành công!',
        ]);
    }

    public function deletePage(Comic $comic, Chapter $chapter, ChapterPage $page)
    {
        if ($page->chapter_id !== $chapter->id) {
            return back()->with('error', 'Trang ảnh không thuộc về chapter này!');
        }

        $this->chapterCrudService->deletePage($comic, $chapter, $page);

        return back()->with('success', 'Đã xóa trang ảnh và cập nhật lại thứ tự!');
    }

    public function destroy(Comic $comic, Chapter $chapter)
    {
        $number = $chapter->chapter_number;
        $this->chapterCrudService->deleteChapter($comic, $chapter);

        return redirect()->route('admin.comics.chapters.index', $comic->id)
            ->with('success', "Đã xóa Chapter {$number}!");
    }
}
