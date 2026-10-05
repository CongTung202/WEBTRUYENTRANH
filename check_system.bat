@echo off
chcp 65001 >nul
title GTSCHunder - System Connection Checker
cls
echo ================================================================
echo    GTSCHunder - Khoi Chay Cong Cu Kiem Tra Ket Noi
echo ================================================================
echo.
echo Dang khoi chay giao dien Desktop GUI...
echo.

python "%~dp0check_system.py"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Khong the mo giao dien GUI bang Python truc tiep.
    echo Dang chuyen sang chay kiem tra tren Terminal (CLI)...
    echo.
    python "%~dp0check_system.py" --cli
    echo.
    pause
)
