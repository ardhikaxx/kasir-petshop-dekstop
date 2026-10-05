@echo off
setlocal
echo Starting Kasir Pet Shop Desktop Build...
powershell -ExecutionPolicy Bypass -NoProfile -File "%~dp0build-windows.ps1"
if %ERRORLEVEL% EQU 0 (
    echo.
    echo ==========================================================
    echo Build selesai dengan sukses!
    echo ==========================================================
) else (
    echo.
    echo ==========================================================
    echo Build mengalami kegagalan. Silakan periksa pesan error di atas.
    echo ==========================================================
)
pause
