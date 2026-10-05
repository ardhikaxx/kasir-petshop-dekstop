@echo off
setlocal enabledelayedexpansion
title Kasir Pet Shop Desktop - Offline POS

echo ========================================================
echo         KASIR PET SHOP DESKTOP (OFFLINE POS)
echo ========================================================
echo Menyiapkan aplikasi kasir pet shop...

cd /d "%~dp0"

:: 1. Check PHP Runtime
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] PHP tidak terdeteksi pada sistem.
    echo Pastikan PHP 8.2+ terinstall dan terdaftar di PATH environment Windows.
    pause
    exit /b 1
)

:: 2. Ensure .env exists
if not exist ".env" (
    echo Menyiapkan file konfigurasi lokal .env...
    copy ".env.example" ".env" >nul
    call php artisan key:generate --force >nul
)

:: 3. AppData directory check for Windows user data
set "KASIR_DATA_DIR=%APPDATA%\KasirPetshop"
if not exist "%KASIR_DATA_DIR%" (
    mkdir "%KASIR_DATA_DIR%" 2>nul
)

:: 4. Initialize SQLite Database & Migrations
echo Memeriksa database SQLite lokal...
call php artisan app:desktop-init >nul

:: 5. Define Local Port strictly on 127.0.0.1
set "HOST=127.0.0.1"
set "PORT=8765"
set "URL=http://%HOST%:%PORT%"

:: Check if server is already running on port
netstat -ano | findstr /R /C:":%PORT% " >nul
if %errorlevel% equ 0 (
    echo Server lokal sudah berjalan di %URL%.
    goto open_browser
)

echo Menjalankan local desktop runtime di %URL%...
start /b "" php -S %HOST%:%PORT% -t public/ >nul 2>&1

:: Give it 1 second to start
timeout /t 1 /nobreak >nul

:open_browser
echo Membuka antarmuka desktop Kasir Pet Shop...

:: Try Edge app mode (Native Windows 10/11 Window without URL bar or tabs)
start "" msedge --app="%URL%" 2>nul
if %errorlevel% equ 0 goto ready

:: Fallback to Chrome app mode
start "" chrome --app="%URL%" 2>nul
if %errorlevel% equ 0 goto ready

:: Fallback to default browser
start "" "%URL%"

:ready
echo.
echo ========================================================
echo   Aplikasi Kasir Pet Shop Berhasil Dijalankan!
echo   Bekerja 100%% Offline (Tanpa Koneksi Internet)
echo   Untuk menutup server lokal, tutup jendela ini.
echo ========================================================
echo.
pause >nul

:: Cleanup on exit
echo Menutup runtime Kasir Pet Shop...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":%PORT%"') do (
    taskkill /f /pid %%a >nul 2>&1
)
exit /b 0
