@echo off
title GTSCHunder - Cài Đặt Môi Trường Dự Án Khi Clone Mới
color 0B
chcp 65001 >nul

cd /d "%~dp0"

echo ==============================================================================
echo        GTSCHUNDER - SCRIPT TỰ ĐỘNG CÀI ĐẶT MÔI TRƯỜNG DỰ ÁN
echo ==============================================================================
echo.
echo  Script này sẽ giúp bạn thiết lập toàn bộ môi trường cần thiết khi mới clone:
echo    1. Kiểm tra các phần mềm: PHP, Composer, Node.js, Python, Pip.
echo    2. Khởi tạo file cấu hình .env và sinh APP_KEY.
echo    3. Cài đặt các gói phụ thuộc Composer (PHP Backend).
echo    4. Cài đặt các gói phụ thuộc NPM và biên dịch giao diện (Frontend Vite).
echo    5. Cài đặt thư viện Python (Pillow cho nén ảnh WebP).
echo    6. Tạo liên kết thư mục Storage và khởi tạo CSDL MySQL (Tuỳ chọn).
echo.
echo ==============================================================================
echo.

set HAS_ERROR=0

:: ------------------------------------------------------------------------------
:: BƯỚC 1: KIỂM TRA CÁC CÔNG CỤ CỐT LÕI
:: ------------------------------------------------------------------------------
echo [BƯỚC 1/6] Đang kiểm tra các phần mềm cốt lõi...
echo.

:: 1.1 Kiểm tra PHP
where php >nul 2>nul
if %errorlevel% equ 0 (
    for /f "tokens=1,2" %%a in ('php -r "echo PHP_VERSION;"') do set PHP_VER=%%a
    echo   [OK] PHP đã cài đặt: v%PHP_VER%
) else (
    echo   [X] THIẾU: Không tìm thấy PHP!
    echo       -> Vui lòng cài đặt PHP (hoặc XAMPP/Laragon) và thêm PHP vào PATH.
    set HAS_ERROR=1
)

:: 1.2 Kiểm tra Composer
where composer >nul 2>nul
if %errorlevel% equ 0 (
    for /f "tokens=1,2,3" %%a in ('composer --version 2^>nul') do (
        if "%%a"=="Composer" set COMPOSER_VER=%%c
    )
    echo   [OK] Composer đã cài đặt: %COMPOSER_VER%
) else (
    echo   [X] THIẾU: Không tìm thấy Composer!
    echo       -> Tải và cài đặt tại: https://getcomposer.org/download/
    set HAS_ERROR=1
)

:: 1.3 Kiểm tra Node.js & NPM
where node >nul 2>nul
if %errorlevel% equ 0 (
    for /f "tokens=*" %%a in ('node -v 2^>nul') do set NODE_VER=%%a
    echo   [OK] Node.js đã cài đặt: %NODE_VER%
) else (
    echo   [X] THIẾU: Không tìm thấy Node.js!
    echo       -> Tải và cài đặt tại: https://nodejs.org/
    set HAS_ERROR=1
)

where npm >nul 2>nul
if %errorlevel% equ 0 (
    for /f "tokens=*" %%a in ('npm -v 2^>nul') do set NPM_VER=%%a
    echo   [OK] NPM đã cài đặt: v%NPM_VER%
) else (
    echo   [X] THIẾU: Không tìm thấy NPM!
    set HAS_ERROR=1
)

:: 1.4 Kiểm tra Python & Pip
where python >nul 2>nul
if %errorlevel% equ 0 (
    for /f "tokens=1,2" %%a in ('python --version 2^>nul') do set PY_VER=%%b
    echo   [OK] Python đã cài đặt: v%PY_VER%
) else (
    echo   [!] CẢNH BÁO: Không tìm thấy Python. Các tool nén ảnh WebP/check kết nối sẽ không chạy được.
    echo       -> Tải tại: https://www.python.org/ (Nhớ tick chọn 'Add Python to PATH')
)

where pip >nul 2>nul
if %errorlevel% equ 0 (
    echo   [OK] Python Pip đã sẵn sàng.
) else (
    echo   [!] CẢNH BÁO: Không tìm thấy Pip.
)

echo.
if %HAS_ERROR% equ 1 (
    color 0C
    echo ==============================================================================
    echo [LỖI] Máy tính của bạn đang thiếu các phần mềm bắt buộc (PHP / Composer / Node).
    echo Vui lòng cài đặt các công cụ còn thiếu theo hướng dẫn phía trên rồi chạy lại file này!
    echo ==============================================================================
    echo.
    pause
    exit /b 1
)

echo [✓] Kiểm tra phần mềm hoàn tất, tiếp tục tiến hành cài đặt!
echo.
pause

:: ------------------------------------------------------------------------------
:: BƯỚC 2: KHỞI TẠO FILE .ENV VÀ CÁC THƯ MỤC HỆ THỐNG
:: ------------------------------------------------------------------------------
echo.
echo ==============================================================================
echo [BƯỚC 2/6] Đang cấu hình file môi trường .env và thư mục hệ thống...
echo ==============================================================================

if not exist ".env" (
    if exist ".env.example" (
        echo   - Đang sao chép .env.example sang .env...
        copy .env.example .env >nul
        echo   - Đang sinh APP_KEY ngẫu nhiên...
        php artisan key:generate
        echo   [OK] Đã tạo file .env thành công.
    ) else (
        echo   [!] Cảnh báo: Không tìm thấy file .env.example.
    )
) else (
    echo   [OK] File .env đã tồn tại.
)

:: Tạo các thư mục lưu trữ cache/logs nếu chưa có
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\framework\cache" mkdir "storage\framework\cache"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\logs" mkdir "storage\logs"
if not exist "bootstrap\cache" mkdir "bootstrap\cache"

:: ------------------------------------------------------------------------------
:: BƯỚC 3: CÀI ĐẶT CÁC GÓI COMPOSER (BACKEND)
:: ------------------------------------------------------------------------------
echo.
echo ==============================================================================
echo [BƯỚC 3/6] Đang cài đặt thư viện PHP Backend qua Composer...
echo ==============================================================================
echo (Quá trình này có thể mất từ 1-3 phút tuỳ tốc độ mạng)
echo.

call composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo.
    echo [!] Composer gặp lỗi khi tải gói. Đang thử chạy composer update...
    call composer update --no-interaction
)

echo.
echo [✓] Đã cài đặt xong thư viện PHP!

:: ------------------------------------------------------------------------------
:: BƯỚC 4: CÀI ĐẶT THƯ VIỆN NPM VÀ BIÊN DỊCH GIAO DIỆN (FRONTEND)
:: ------------------------------------------------------------------------------
echo.
echo ==============================================================================
echo [BƯỚC 4/6] Đang cài đặt thư viện Node.js và biên dịch Assets (Vite)...
echo ==============================================================================

call npm install
if %errorlevel% neq 0 (
    echo [!] NPM install gặp lỗi. Vui lòng kiểm tra lại kết nối mạng.
) else (
    echo   - Đang biên dịch CSS/JS sản phẩm (npm run build)...
    call npm run build
    echo [✓] Biên dịch giao diện hoàn tất!
)

:: ------------------------------------------------------------------------------
:: BƯỚC 5: CÀI ĐẶT THƯ VIỆN PYTHON (Pillow)
:: ------------------------------------------------------------------------------
echo.
echo ==============================================================================
echo [BƯỚC 5/6] Đang cài đặt thư viện Python (xử lý ảnh WebP)...
echo ==============================================================================

where pip >nul 2>nul
if %errorlevel% equ 0 (
    if exist "requirements.txt" (
        pip install -r requirements.txt
    ) else (
        pip install pillow
    )
    echo [✓] Đã cài đặt thư viện Python Pillow!
) else (
    echo [!] Bỏ qua bước này do không tìm thấy pip.
)

:: ------------------------------------------------------------------------------
:: BƯỚC 6: TẠO STORAGE LINK VÀ KHỞI TẠO CSDL
:: ------------------------------------------------------------------------------
echo.
echo ==============================================================================
echo [BƯỚC 6/6] Thiết lập liên kết Storage và Cơ sở dữ liệu...
echo ==============================================================================

:: Tạo storage link
echo   - Đang liên kết thư mục public/storage...
php artisan storage:link >nul 2>nul

echo.
echo ------------------------------------------------------------------------------
echo LƯU Ý VỀ CƠ SỞ DỮ LIỆU:
echo Hãy đảm bảo máy chủ MySQL (XAMPP / Laragon / MySQL Server) của bạn đang BẬT.
echo Tên CSDL mặc định trong .env là: 'webtruyentranh'
echo ------------------------------------------------------------------------------
echo.

set /p DB_CHOICE="👉 Bạn có muốn chạy Migrate và nạp dữ liệu mẫu ngay bây giờ? (y/n): "
if /i "%DB_CHOICE%"=="y" (
    echo.
    echo Đang thực thi 'php artisan migrate:fresh --seed'...
    php artisan migrate:fresh --seed --force
    if %errorlevel% equ 0 (
        echo [✓] Đã khởi tạo cấu trúc CSDL và nạp tài khoản Admin mẫu thành công!
    ) else (
        echo [!] Chưa thể migrate CSDL. Hãy tạo database 'webtruyentranh' trong MySQL rồi chạy lại 'php artisan migrate --seed'.
    )
) else (
    echo Đã bỏ qua bước nạp CSDL. Bạn có thể tự chạy 'php artisan migrate' sau.
)

:: ------------------------------------------------------------------------------
:: TỔNG KẾT HOÀN TẤT
:: ------------------------------------------------------------------------------
color 0A
echo.
echo ==============================================================================
echo        🎉 CHÚC MỪNG! TOÀN BỘ MÔI TRƯỜNG DỰ ÁN ĐÃ ĐƯỢC THIẾT LẬP THÀNH CÔNG!
echo ==============================================================================
echo.
echo  CÁC FILE KHỞI ĐỘNG NHANH CÓ SẴN:
echo   1. [run.bat]           : Khởi động Web Server (http://127.0.0.1:8000)
echo   2. [check_system.bat]  : Giao diện Desktop kiểm tra Cloudinary, DB, Đăng Chapter
echo   3. [upload_chapter.bat]: Giao diện CLI tự động nén WebP và đăng truyện
echo.
echo  THÔNG TIN MẶC ĐỊNH:
echo   - Trang chủ          : http://127.0.0.1:8000
echo   - Trang Quản trị     : http://127.0.0.1:8000/admin
echo   - Tài khoản Admin    : admin@gmail.com  / Mật khẩu: 123456
echo   - Tài khoản Độc giả  : user@gmail.com   / Mật khẩu: 123456
echo.
echo ==============================================================================
echo.
pause
