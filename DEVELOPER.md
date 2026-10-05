# Dokumentasi Pengembang (Developer Guide)
## Kasir Pet Shop Desktop — Windows Offline Edition

Dokumentasi ini ditujukan bagi pengembang yang memelihara, mengembangkan, dan mem-build installer desktop aplikasi **Kasir Pet Shop**.

---

### 1. Arsitektur Tiga Lapis (Three-Layer Architecture)

Aplikasi ini dirancang dengan prinsip **separasi total** antara kode aplikasi, runtime, dan data pengguna untuk memastikan keamanan data saat update versi:

```
[ LAYER 1: APLIKASI LARAVEL 13 ]
├── app/ (Models, Controllers, Services, Requests)
├── routes/ (web.php)
├── resources/views/ (Blade Templates UI)
├── public/ (Custom CSS & Local JS, tanpa CDN)
└── vendor/ (Dependency Composer production)

[ LAYER 2: RUNTIME MANDIRI (PORTABLE RUNTIME) ]
├── php/ (PHP 8.4 Windows x64 Portable + semua extension wajib)
├── KasirPetShop.exe (C# Launcher mandiri + System Tray + Single Instance Mutex)
├── uninstall.exe (Uninstaller terdaftar di Windows Add/Remove Programs)
└── PetShopPOS-Setup.exe (Self-contained Windows Installer GUI)

[ LAYER 3: DATA PENGGUNA (USER DATA DIRECTORY) ]
Lokasi: %LOCALAPPDATA%\KasirPetShop\ (C:\Users\<User>\AppData\Local\KasirPetShop)
├── data/
│   └── database.sqlite (Database aktif yang TIDAK PERNAH terhapus saat update)
├── backups/ (Penyimpanan file backup otomatis & manual .sqlite)
├── logs/ (Troubleshooting log lokal tanpa koneksi luar)
└── storage/ (Upload logo toko & file user)
```

---

### 2. Lingkungan Pengembangan (Development Environment)

#### Persyaratan Sistem Pengembang:
- Windows 10 atau Windows 11 (64-bit)
- PHP 8.4+ (dengan extension: `pdo_sqlite`, `sqlite3`, `mbstring`, `curl`, `openssl`, `fileinfo`, `gd`, `intl`)
- Composer 2.x
- Microsoft .NET Framework 4.8 (sudah bawaan Windows 10/11) dengan compiler `csc.exe`
- 7-Zip (opsional untuk mempercepat kompresi build)

#### Menjalankan Mode Development:
```powershell
# 1. Jalankan migrasi dan seeder awal
php artisan migrate
php artisan db:seed

# 2. Jalankan test otomatis
vendor/bin/pest

# 3. Jalankan server lokal untuk pengujian manual di browser
php artisan serve
# Akses: http://127.0.0.1:8000/pos
```

---

### 3. Pembuatan Installer Otomatis (`build-windows`)

Untuk menghasilkan file installer tunggal `.exe` yang siap dikirim ke pengguna:

#### Cara 1: Jalankan Batch File
Klik ganda file **`build-windows.bat`** di root folder project.

#### Cara 2: Jalankan via PowerShell
```powershell
powershell -ExecutionPolicy Bypass -NoProfile -File build-windows.ps1
```

#### Tahapan yang Dilakukan Script Build:
1. **Validasi Prerequisites:** Memeriksa ketersediaan compiler C# bawaan Windows (`csc.exe`).
2. **Generasi Ikon Multi-Resolusi:** Mengompilasi `build/MakeIcon.cs` dan membuat `build/app.ico` (resolusi 16x16 hingga 256x256).
3. **Kompilasi Launcher:** Mengompilasi `launcher/KasirPetShop.cs` menjadi `KasirPetShop.exe` dengan ikon tertanam.
4. **Staging Runtime:** Mengemas PHP 8.4 portable beserta seluruh DLL, extension, `php.ini`, dependensi Laravel, dan `uninstall.exe` ke folder `dist/portable_app`.
5. **Kompresi:** Memadatkan seluruh bundle menjadi `dist/app_package.zip` (~50 MB).
6. **Kompilasi Installer:** Mengompilasi `installer/KasirPetShopSetup.cs` dengan menyematkan payload ZIP dan ikon ke dalam **`PetShopPOS-Setup.exe`**.
7. **Penyalinan Otomatis:** Menyalin installer ke folder `C:\Users\LENOVO\Downloads\PetShopPOS-Setup.exe`.

---

### 4. Prosedur Pembaruan Versi Aplikasi (App Updates)

Ketika merilis versi baru (misal `1.0.0` ke `1.0.1`):
1. Ubah nomor versi pada:
   - `installer/KasirPetShopSetup.cs` (atribut `DisplayVersion`)
   - `build-windows.ps1`
2. Tulis migrasi database baru secara **non-destructive** (hanya `Schema::table(..., function(...) { $table->addColumn(...); });`).
3. Jalankan `build-windows.bat`.
4. Kirim installer baru `PetShopPOS-Setup.exe` kepada pengguna.
5. Saat pengguna menjalankan installer versi baru:
   - Installer memperbarui file biner aplikasi di folder program.
   - Database pengguna di `%LOCALAPPDATA%\KasirPetShop\data\database.sqlite` **tetap dipertahankan**.
   - Saat aplikasi pertama kali dibuka setelah update, launcher secara otomatis mengeksekusi `artisan app:desktop-init` yang menjalankan migrasi baru secara aman tanpa merusak riwayat transaksi yang sudah ada.

---

### 5. Troubleshooting & Audit Keamanan Offline

- **Pemeriksaan Koneksi Luar:**
  Seluruh asset frontend tersimpan lokal di `public/css/petshop-ui.css` dan `public/js/`. Tidak ada referensi CDN, font Google, maupun panggilan API pihak ketiga.
- **Log Permasalahan:**
  Jika aplikasi di komputer pengguna gagal berjalan, periksa log lokal di:
  `%LOCALAPPDATA%\KasirPetShop\logs\launcher.log` dan `storage/logs/laravel.log`.
