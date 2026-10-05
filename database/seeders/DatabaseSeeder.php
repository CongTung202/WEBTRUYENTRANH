<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Genre;
use App\Models\Comic;
use App\Models\Chapter;
use App\Models\ChapterPage;
use App\Models\Comment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin Account
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Quản Trị',
                'password' => Hash::make('123456'),
                'role' => 'admin',
            ]
        );

        // 2. Create Normal User Account
        $user = User::firstOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name' => 'Độc Giả Vip',
                'password' => Hash::make('123456'),
                'role' => 'user',
            ]
        );

        // 3. Create Genres
        $genresData = [
            ['name' => 'Action', 'description' => 'Thể loại hành động, chiến đấu gay cấn, kịch tính.'],
            ['name' => 'Manhwa', 'description' => 'Truyện tranh Hàn Quốc định dạng Webtoon cuộn dọc màu sắc.'],
            ['name' => 'Manga', 'description' => 'Truyện tranh truyền thống Nhật Bản.'],
            ['name' => 'Manhua', 'description' => 'Truyện tranh Trung Quốc, thường có chủ đề tu tiên, huyền huyễn.'],
            ['name' => 'Chuyển Sinh', 'description' => 'Nhân vật chính tái sinh sang thế giới khác (Isekai/Reincarnation).'],
            ['name' => 'Romance', 'description' => 'Tình cảm lãng mạn, tình yêu đôi lứa ngọt ngào.'],
            ['name' => 'Fantasy', 'description' => 'Thế giới huyền bí, ma thuật, quái vật và phép thuật.'],
            ['name' => 'Tu Tiên', 'description' => 'Luyện khí, trúc cơ, độ kiếp thành tiên.'],
            ['name' => 'Võ Thuật', 'description' => 'Võ lâm kiếm hiệp, quyền thuật đỉnh cao.'],
            ['name' => 'Hài Hước', 'description' => 'Nội dung vui vẻ, giải trí, mang lại tiếng cười.'],
            ['name' => 'Hệ Thống', 'description' => 'Nhân vật chính sở hữu hệ thống phụ trợ thăng cấp sức mạnh.'],
            ['name' => 'Đô Thị', 'description' => 'Bối cảnh thế giới hiện đại.'],
        ];

        $genreModels = [];
        foreach ($genresData as $g) {
            $genreModels[$g['name']] = Genre::firstOrCreate(
                ['slug' => Str::slug($g['name'])],
                ['name' => $g['name'], 'description' => $g['description']]
            );
        }

        // 4. Create Sample Comics
        $sampleComics = [
            [
                'title' => 'Solo Leveling: Tôi Thăng Cấp Một Mình',
                'other_names' => 'Na Honjaman Rebeleop, Only I Level Up',
                'author' => 'Chugong',
                'artist' => 'DUBU (REDICE Studio)',
                'description' => "10 năm trước, sau khi 'Cánh cổng' kết nối thế giới thực với thế giới quái vật mở ra, một số người bình thường nhận được sức mạnh thức tỉnh để săn lùng quái vật bên trong cổng. Họ được gọi là 'Thợ săn'. Sung Jin-Woo là một Thợ săn cấp E yếu nhất thế giới...",
                'cover_image' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=600&auto=format&fit=crop&q=80',
                'status' => 'completed',
                'views' => 2450000,
                'rating_score' => 4.95,
                'rating_count' => 1250,
                'is_featured' => true,
                'is_recommended' => true,
                'genres' => ['Action', 'Manhwa', 'Fantasy', 'Hệ Thống'],
            ],
            [
                'title' => 'Toàn Trí Độc Giả - Omniscient Reader',
                'other_names' => 'Toàn Trí Độc Giả Chi Lộ, ORV',
                'author' => 'Sing Shong',
                'artist' => 'Sleepy-C',
                'description' => "Kim Dokja là một nhân viên văn phòng bình thường, sở thích duy nhất của anh là đọc tiểu thuyết mạng 'Ba Cách Để Sống Sót Trong Thế Giới Đổ Nát'. Khi chương cuối cùng được đăng tải, thế giới thực bỗng chốc biến đổi thành chính câu chuyện trong tiểu thuyết...",
                'cover_image' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=600&auto=format&fit=crop&q=80',
                'status' => 'ongoing',
                'views' => 1820000,
                'rating_score' => 4.92,
                'rating_count' => 980,
                'is_featured' => true,
                'is_recommended' => true,
                'genres' => ['Action', 'Manhwa', 'Fantasy', 'Hệ Thống'],
            ],
            [
                'title' => 'Ta Là Tà Đế - I Am An Evil God',
                'other_names' => 'Wo Shi Xie Di',
                'author' => 'Thời Đại Mạn Vương',
                'artist' => 'Mạn Cung Studio',
                'description' => "Tạ Diễm vô tình xuyên không đến thế giới tu tiên rộng lớn, nhập vào thân thể của một đệ tử tà phái vừa đẹp trai vừa sở hữu hệ thống thu thập điểm khởi nguyên từ cảm xúc của người khác...",
                'cover_image' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=600&auto=format&fit=crop&q=80',
                'status' => 'ongoing',
                'views' => 1250000,
                'rating_score' => 4.88,
                'rating_count' => 640,
                'is_featured' => true,
                'is_recommended' => false,
                'genres' => ['Manhua', 'Tu Tiên', 'Hệ Thống', 'Chuyển Sinh', 'Hài Hước'],
            ],
            [
                'title' => 'Võ Luyện Đỉnh Phong - Martial Peak',
                'other_names' => 'Martial Peak',
                'author' => 'Mạc Mặc',
                'artist' => 'Pikapi Studio',
                'description' => "Đỉnh cao võ đạo, là cô độc, là tịch mịch, là bước đi trên chông gai không ngừng tiến tới. Dương Khai từ một gã quét rác của Lăng Tiêu Các tình cờ có được một cuốn Hắc Thư vô tự...",
                'cover_image' => 'https://images.unsplash.com/photo-1563089145-599997674d42?w=600&auto=format&fit=crop&q=80',
                'status' => 'ongoing',
                'views' => 3100000,
                'rating_score' => 4.75,
                'rating_count' => 1500,
                'is_featured' => false,
                'is_recommended' => true,
                'genres' => ['Manhua', 'Tu Tiên', 'Võ Thuật', 'Action'],
            ],
            [
                'title' => 'Tái Sinh Thành Kiếm Khách Huyền Thoại',
                'other_names' => 'The Reincarnated Legendary Swordsman',
                'author' => 'Kim Jin-woo',
                'artist' => 'Red Moon',
                'description' => "Bị phản bội và giết chết ở đỉnh cao danh vọng, kiếm vương huyền thoại được tái sinh vào cơ thể của một thiếu gia phế vật của gia tộc quý tộc bị ruồng bỏ...",
                'cover_image' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&auto=format&fit=crop&q=80',
                'status' => 'ongoing',
                'views' => 840000,
                'rating_score' => 4.80,
                'rating_count' => 420,
                'is_featured' => false,
                'is_recommended' => true,
                'genres' => ['Manhwa', 'Action', 'Chuyển Sinh', 'Fantasy'],
            ],
            [
                'title' => 'Thiên Kim Trở Về: Nghịch Chuyển Vận Mệnh',
                'other_names' => 'The Return of the Heiress',
                'author' => 'Elena Park',
                'artist' => 'Studio Blossom',
                'description' => "Kiếp trước bị hãm hại đến chết trong tuyệt vọng, nàng được ban cho cơ hội quay trở lại thời điểm 5 năm trước khi bi kịch xảy ra. Lần này, nàng sẽ tự tay vạch trần bộ mặt giả tạo...",
                'cover_image' => 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=600&auto=format&fit=crop&q=80',
                'status' => 'ongoing',
                'views' => 690000,
                'rating_score' => 4.85,
                'rating_count' => 310,
                'is_featured' => false,
                'is_recommended' => false,
                'genres' => ['Manhwa', 'Romance', 'Chuyển Sinh', 'Đô Thị'],
            ],
        ];

        foreach ($sampleComics as $comicData) {
            $genreNames = $comicData['genres'];
            unset($comicData['genres']);

            $slug = Str::slug($comicData['title']);
            $comic = Comic::firstOrCreate(
                ['slug' => $slug],
                array_merge($comicData, ['slug' => $slug])
            );

            // Attach genres
            $genreIds = [];
            foreach ($genreNames as $gName) {
                if (isset($genreModels[$gName])) {
                    $genreIds[] = $genreModels[$gName]->id;
                }
            }
            $comic->genres()->sync($genreIds);

            // Create 3 sample chapters for each comic
            for ($cNum = 1; $cNum <= 3; $cNum++) {
                $chapter = Chapter::firstOrCreate(
                    [
                        'comic_id' => $comic->id,
                        'chapter_number' => $cNum,
                    ],
                    [
                        'title' => "Chapter {$cNum}: Khởi đầu hành trình",
                        'slug' => "chap-{$cNum}",
                        'views' => rand(500, 5000),
                    ]
                );

                // Create 4 sample pages for each chapter using default.png or artwork
                for ($p = 1; $p <= 4; $p++) {
                    ChapterPage::firstOrCreate(
                        [
                            'chapter_id' => $chapter->id,
                            'page_number' => $p,
                        ],
                        [
                            'image_url' => asset('images/default.png'),
                        ]
                    );
                }
            }

            // Create sample comments
            Comment::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'comic_id' => $comic->id,
                    'content' => 'Truyện vẽ nét đỉnh quá, đọc trên web mượt mà không bị lag xíu nào! Hóng chap mới <3',
                ],
                [
                    'likes' => rand(5, 20),
                ]
            );
        }
    }
}
