@echo off
title GTSCHunder - GTSCHUNDER Server
color 0B
chcp 65001 >nul

cd /d "%~dp0"

echo ================================================================
echo               GTSCHunder - WEB TRUYEN TRANH
echo ================================================================
echo.

:: Kiem tra PHP
where php >nul 2>nul
if %errorlevel% neq 0 (
    color 0C
    echo [LOI] Khong tim thay PHP tren may tinh!
    echo Vui long cai dat PHP hoac them duong dan PHP vao bien moi truong PATH.
    echo.
    pause
    exit /b 1
)

:: Kiem tra file .env
if not exist ".env" (
    if exist ".env.example" (
        echo [INFO] Dang khoi tao file .env tu .env.example...
        copy .env.example .env >nul
        php artisan key:generate
    )
)

:: Tao lien ket storage neu chua co
if not exist "public\storage" (
    echo [INFO] Dang tao lien ket thu muc storage:link...
    php artisan storage:link >nul 2>nul
)

echo [OK] Dang khoi dong may chu tai: http://127.0.0.1:8000
echo.
echo ================================================================
echo  * Trang chu      : http://127.0.0.1:8000
echo  * Trang Quan tri : http://127.0.0.1:8000/admin
echo  * Tai khoan Admin: admin@gmail.com / 123456
echo  * Nhan Ctrl + C de dung may chu
echo ================================================================
echo.

:: Tu dong mo trinh duyet web
start "" "http://127.0.0.1:8000"

:: Chay Laravel Development Server
php artisan serve --host=127.0.0.1 --port=8000

if %errorlevel% neq 0 (
    echo.
    echo [THONG BAO] May chu da dong.
    pause
)
