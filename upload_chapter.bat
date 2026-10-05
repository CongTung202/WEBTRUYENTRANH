@echo off
chcp 65001 >nul
title GTSCHunder - Tool Đăng Chapter Nhanh
cls

python "%~dp0upload_chapter.py"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Co loi xay ra khi chay tool.
    pause
)
