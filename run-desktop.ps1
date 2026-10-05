# Kasir Pet Shop Desktop - PowerShell Launcher
$Host.UI.RawUI.WindowTitle = "Kasir Pet Shop Desktop - Offline POS"

Write-Host "========================================================" -ForegroundColor Cyan
Write-Host "         KASIR PET SHOP DESKTOP (OFFLINE POS)          " -ForegroundColor Yellow
Write-Host "========================================================" -ForegroundColor Cyan

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $ScriptDir

# 1. Check PHP
if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Host "[ERROR] PHP tidak terdeteksi di sistem PATH." -ForegroundColor Red
    Write-Host "Pastikan PHP terinstal." -ForegroundColor Yellow
    Read-Host "Tekan Enter untuk keluar..."
    exit 1
}

# 2. Check .env
if (-not (Test-Path ".env")) {
    Write-Host "Menyiapkan file konfigurasi lokal .env..." -ForegroundColor Gray
    Copy-Item ".env.example" ".env"
    php artisan key:generate --force | Out-Null
}

# 3. Initialize SQLite
Write-Host "Memeriksa dan menginisialisasi database SQLite..." -ForegroundColor Gray
php artisan app:desktop-init | Out-Null

$HostIp = "127.0.0.1"
$Port = 8765
$AppUrl = "http://${HostIp}:${Port}"

# 4. Start local PHP process if not already running
$ExistingProcess = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if (-not $ExistingProcess) {
    Write-Host "Menjalankan runtime server lokal di $AppUrl..." -ForegroundColor Green
    $PhpProcess = Start-Process php -ArgumentList "-S ${HostIp}:${Port} -t public/" -WindowStyle Hidden -PassThru
} else {
    Write-Host "Runtime lokal sudah aktif di $AppUrl." -ForegroundColor Green
}

Start-Sleep -Seconds 1

# 5. Open window in standalone App Mode
Write-Host "Membuka jendela aplikasi Kasir Pet Shop..." -ForegroundColor Cyan
try {
    Start-Process msedge -ArgumentList "--app=$AppUrl" -ErrorAction Stop
} catch {
    try {
        Start-Process chrome -ArgumentList "--app=$AppUrl" -ErrorAction Stop
    } catch {
        Start-Process $AppUrl
    }
}

Write-Host "`nAplikasi Kasir Pet Shop Desktop telah aktif (100% Offline)." -ForegroundColor Green
Write-Host "Tekan Enter di jendela ini kapan saja untuk menutup server aplikasi." -ForegroundColor Gray
Read-Host

if ($PhpProcess) {
    Stop-Process -Id $PhpProcess.Id -Force -ErrorAction SilentlyContinue
}
