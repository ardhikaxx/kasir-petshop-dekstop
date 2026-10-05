# ==============================================================================
# Build Windows Installer Script - Kasir Pet Shop Desktop
# ==============================================================================
# Usage:
#   powershell -ExecutionPolicy Bypass -File build-windows.ps1
# ==============================================================================

$ErrorActionPreference = "Stop"
Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "  BUILDING KASIR PET SHOP DESKTOP WINDOWS INSTALLER" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

$rootDir = $PSScriptRoot
if (-not $rootDir) { $rootDir = Get-Location }
Set-Location $rootDir

$distDir = Join-Path $rootDir "dist"
$portableAppDir = Join-Path $distDir "portable_app"
$zipPackage = Join-Path $distDir "app_package.zip"
$installerExe = Join-Path $distDir "PetShopPOS-Setup.exe"
$downloadsDest = "C:\Users\LENOVO\Downloads\PetShopPOS-Setup.exe"
$cscPath = "C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe"

# 1. Check prerequisites
Write-Host "`n[1/7] Checking build prerequisites..." -ForegroundColor Yellow
if (-not (Test-Path $cscPath)) {
    throw "Microsoft C# Compiler (csc.exe) not found at: $cscPath"
}
Write-Host "  - .NET Framework 4.8 Compiler: OK" -ForegroundColor Green

# 2. Generate Multi-Resolution Application Icon
Write-Host "`n[2/7] Generating Windows Application Icon (app.ico)..." -ForegroundColor Yellow
if (-not (Test-Path "build")) { New-Item -ItemType Directory -Path "build" -Force | Out-Null }
& $cscPath /target:exe /out:build\MakeIcon.exe /r:System.Drawing.dll build\MakeIcon.cs
& "build\MakeIcon.exe" "build\app.ico"
Write-Host "  - Application Icon generated: OK" -ForegroundColor Green

# 3. Compile Launcher & Uninstaller
Write-Host "`n[3/7] Compiling native launcher and uninstaller..." -ForegroundColor Yellow
& $cscPath /target:winexe /out:KasirPetShop.exe /win32icon:build\app.ico /r:System.Windows.Forms.dll /r:System.Drawing.dll launcher\KasirPetShop.cs
Write-Host "  - KasirPetShop.exe compiled: OK" -ForegroundColor Green

# 4. Assemble Portable App Directory
Write-Host "`n[4/7] Assembling portable release directory..." -ForegroundColor Yellow
if (Test-Path $portableAppDir) { Remove-Item $portableAppDir -Recurse -Force }
New-Item -ItemType Directory -Path $portableAppDir -Force | Out-Null

# Copy Portable PHP
$phpDest = Join-Path $portableAppDir "php"
New-Item -ItemType Directory -Path "$phpDest\ext" -Force | Out-Null
Copy-Item "C:\xampp\php\*.dll" "$phpDest\" -Force
Copy-Item "C:\xampp\php\php.exe" "$phpDest\" -Force
Copy-Item "C:\xampp\php\ext\*.dll" "$phpDest\ext\" -Force
Copy-Item "dist\test_php\php.ini" "$phpDest\php.ini" -Force

# Copy Core Laravel Folders
$appDirs = @('app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'vendor')
foreach ($d in $appDirs) {
    Copy-Item $d "$portableAppDir\$d" -Recurse -Force
}

# Create Clean Storage Tree
$storageDirs = @(
    'storage\app\public',
    'storage\app\backups',
    'storage\framework\cache\data',
    'storage\framework\sessions',
    'storage\framework\views',
    'storage\logs'
)
foreach ($sd in $storageDirs) {
    New-Item -ItemType Directory -Path "$portableAppDir\$sd" -Force | Out-Null
}

# Copy Essential App Files
Copy-Item "artisan" "$portableAppDir\artisan" -Force
Copy-Item ".env" "$portableAppDir\.env" -Force
Copy-Item "composer.json" "$portableAppDir\composer.json" -Force
Copy-Item "composer.lock" "$portableAppDir\composer.lock" -Force
Copy-Item "KasirPetShop.exe" "$portableAppDir\KasirPetShop.exe" -Force
Copy-Item "build\app.ico" "$portableAppDir\app.ico" -Force

# Compile Uninstaller directly into portable directory
& $cscPath /target:winexe /out:"$portableAppDir\uninstall.exe" /win32icon:build\app.ico /r:System.Windows.Forms.dll /r:System.Drawing.dll installer\KasirPetShopUninstall.cs
Write-Host "  - Portable app staged successfully: OK" -ForegroundColor Green

# 5. Compress into app_package.zip
Write-Host "`n[5/7] Compressing application package..." -ForegroundColor Yellow
if (Test-Path $zipPackage) { Remove-Item $zipPackage -Force }

$sevenZipPaths = @(
    "C:\Program Files\AMD\CIM\Bin64\7z.exe",
    "C:\Program Files\7-Zip\7z.exe",
    "C:\Program Files (x86)\7-Zip\7z.exe"
)
$sevenZip = $sevenZipPaths | Where-Object { Test-Path $_ } | Select-Object -First 1

if ($sevenZip) {
    Write-Host "  - Using 7-Zip for high-speed multi-core compression..." -ForegroundColor Gray
    & $sevenZip a -tzip "$zipPackage" "$portableAppDir\*" -mx5 | Out-Null
} else {
    Write-Host "  - Using PowerShell ZipFile compression..." -ForegroundColor Gray
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::CreateFromDirectory($portableAppDir, $zipPackage)
}
$zipSize = (Get-Item $zipPackage).Length / 1MB
Write-Host ("  - Archive generated: {0:N2} MB" -f $zipSize) -ForegroundColor Green

# 6. Compile Standalone Installer EXE
Write-Host "`n[6/7] Compiling standalone Windows installer (PetShopPOS-Setup.exe)..." -ForegroundColor Yellow
& $cscPath `
    /target:winexe `
    /out:"$installerExe" `
    /win32icon:build\app.ico `
    /resource:"$zipPackage,KasirPetShopInstaller.app_package.zip" `
    /r:System.Windows.Forms.dll `
    /r:System.Drawing.dll `
    /r:System.IO.Compression.dll `
    /r:System.IO.Compression.FileSystem.dll `
    installer\KasirPetShopSetup.cs

$exeSize = (Get-Item $installerExe).Length / 1MB
Write-Host ("  - Installer generated: {0:N2} MB" -f $exeSize) -ForegroundColor Green

# 7. Copy to Downloads folder & Refresh Shell Icon Cache
Write-Host "`n[7/7] Copying installer to Downloads folder..." -ForegroundColor Yellow
$downloadsAlt = "C:\Users\LENOVO\Downloads\KasirPetShop-Setup.exe"

if (Test-Path (Split-Path $downloadsDest)) {
    $copied = $false
    for ($i = 0; $i -lt 5; $i++) {
        try {
            Copy-Item $installerExe $downloadsDest -Force
            Copy-Item $installerExe $downloadsAlt -Force
            $copied = $true
            break
        } catch {
            Start-Sleep -Seconds 1
        }
    }
    if ($copied) {
        Write-Host "  - Copied to: $downloadsDest" -ForegroundColor Green
        Write-Host "  - Copied to: $downloadsAlt" -ForegroundColor Green
    } else {
        Write-Warning "  - File is currently locked by a scanner. Ready at: $installerExe"
    }

    # Refresh Windows Shell Icon Cache so Explorer displays new icon immediately
    try {
        if (Test-Path "$env:SystemRoot\system32\ie4uinit.exe") {
            Start-Process "$env:SystemRoot\system32\ie4uinit.exe" -ArgumentList "-show" -Wait -WindowStyle Hidden
        }
    } catch { }
}

Write-Host "`n==========================================================" -ForegroundColor Cyan
Write-Host "  BUILD SUCCESSFUL!" -ForegroundColor Green
Write-Host "  Installer Location: $downloadsDest" -ForegroundColor White
Write-Host "  Kirim file .exe ini ke siapa pun untuk instalasi langsung!" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan
