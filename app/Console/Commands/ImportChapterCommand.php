<?php

namespace App\Console\Commands;

use App\Models\Comic;
use App\Services\ChapterImportService;
use Illuminate\Console\Command;

class ImportChapterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comic:import-chapter 
                            {--comic= : ID hoặc slug của truyện} 
                            {--chapter= : Số thứ tự chapter (ví dụ: 1, 2, 10.5)} 
                            {--path= : Đường dẫn thư mục ảnh trên máy local} 
                            {--title= : Tiêu đề chapter (tùy chọn)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Đăng chapter truyện siêu tốc từ thư mục ảnh local, nén WebP và upload lên Cloudinary';

    /**
     * Execute the console command.
     */
    public function handle(ChapterImportService $importService)
    {
        $this->info('====================================================');
        $this->info('🚀 TOOL ĐĂNG CHAPTER TRUYỆN SIÊU TỐC - WEBTRUYENTRANH');
        $this->info('====================================================');

        $comicInput = $this->option('comic');
        if (!$comicInput) {
            $comics = Comic::select('id', 'title')->orderBy('title')->get();
            if ($comics->isEmpty()) {
                $this->error('Chưa có truyện nào trong database! Vui lòng tạo truyện trước.');
                return 1;
            }

            $choices = [];
            foreach ($comics as $c) {
                $choices[$c->id] = "[ID: {$c->id}] {$c->title}";
            }
            $selectedChoice = $this->choice('Chọn truyện cần đăng chapter:', $choices);
            preg_match('/\[ID: (\d+)\]/', $selectedChoice, $matches);
            $comicId = (int) $matches[1];
        } else {
            $comic = is_numeric($comicInput) 
                ? Comic::find($comicInput) 
                : Comic::where('slug', $comicInput)->first();

            if (!$comic) {
                $this->error("Không tìm thấy truyện với thông tin: {$comicInput}");
                return 1;
            }
            $comicId = $comic->id;
        }

        $comic = Comic::with('latestChapter')->find($comicId);
        $this->line("Truyện được chọn: <fg=cyan>{$comic->title}</> (ID: {$comic->id})");

        $defaultNextChap = $comic->latestChapter ? ($comic->latestChapter->chapter_number + 1) : 1;
        $chapterNumberInput = $this->option('chapter') ?: $this->ask("Nhập số chapter (mặc định: {$defaultNextChap})", (string) $defaultNextChap);
        $chapterNumber = (float) $chapterNumberInput;

        $titleInput = $this->option('title') ?: $this->ask("Nhập tiêu đề chapter (bỏ trống để đặt là 'Chapter {$chapterNumber}')", "Chapter {$chapterNumber}");

        $path = $this->option('path') ?: $this->ask("Nhập đường dẫn thư mục ảnh trên máy local (ví dụ: D:\Manga\Chap1)");

        if (!is_dir($path)) {
            $this->error("Thư mục không tồn tại: {$path}");
            return 1;
        }

        $this->info("\nĐang quét và chuẩn bị upload...");

        $bar = null;

        try {
            $result = $importService->importFromLocalDirectory(
                $comicId,
                $chapterNumber,
                $titleInput,
                $path,
                function (int $current, int $total, string $file) use (&$bar) {
                    if (!$bar) {
                        $bar = $this->output->createProgressBar($total);
                        $bar->setFormat(" %current%/%max% [%bar%] %percent:3s%% -- Đang tải: %message%");
                    }
                    $bar->setMessage($file);
                    $bar->setProgress($current);
                }
            );

            if ($bar) {
                $bar->finish();
                $this->newLine();
            }

            $this->info("\n🎉 ĐĂNG CHAPTER THÀNH CÔNG RỰC RỠ!");
            $this->table(
                ['Thông tin', 'Giá trị'],
                [
                    ['Truyện', $result['comic']->title],
                    ['Chapter', 'Chapter ' . $result['chapter']->chapter_number . ' - ' . $result['chapter']->title],
                    ['Tổng số trang', $result['total_pages'] . ' trang WebP'],
                    ['Thư mục Cloudinary', $result['cloudinary_folder']],
                ]
            );

            return 0;
        } catch (\Throwable $e) {
            $this->error("\n❌ Đăng chapter thất bại: " . $e->getMessage());
            return 1;
        }
    }
}
