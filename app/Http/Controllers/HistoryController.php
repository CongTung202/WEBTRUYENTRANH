<?php

namespace App\Http\Controllers;

use App\Services\Crud\HistoryCrudService;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    protected HistoryCrudService $historyCrudService;

    public function __construct(HistoryCrudService $historyCrudService)
    {
        $this->historyCrudService = $historyCrudService;
    }

    public function index()
    {
        $histories = collect();
        if (auth()->check()) {
            $histories = $this->historyCrudService->getUserHistories(auth()->id(), 18);
        }

        return view('user.history', compact('histories'));
    }

    public function clear()
    {
        if (auth()->check()) {
            $this->historyCrudService->clearUserHistories(auth()->id());
        }

        return redirect()->route('history.index')->with('success', 'Đã xóa toàn bộ lịch sử đọc truyện!');
    }
}
