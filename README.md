# Kasir Pet Shop Desktop (Offline POS)

Aplikasi kasir desktop profesional, mandiri, dan lengkap untuk operasional toko hewan peliharaan (Pet Shop) yang dibangun menggunakan **Laravel 13** dan **SQLite** sebagai database lokal.

Dirancang sejak awal dengan prinsip **100% Offline-First**:
- **Bebas Koneksi Internet**: Berjalan sepenuhnya tanpa memerlukan koneksi internet untuk seluruh operasional utama toko.
- **Tanpa CDN**: Semua asset (CSS, JavaScript, icon SVG lokal) dibundel di dalam aplikasi. Tidak ada dependensi terhadap Google Fonts, Font Awesome CDN, Bootstrap CDN, atau server eksternal.
- **Tanpa Autentikasi / Login**: Dibuka langsung menuju modul Kasir POS atau Dashboard tanpa akun, multi-user, maupun session login.
- **Single-Device & Single-Store**: Semua data tersimpan aman pada komputer lokal kasir.
- **Arsitektur Desktop Windows**: Siap dijalankan sebagai aplikasi desktop native melalui launcher Windows atau dibundel menjadi installer `.exe` dengan wrapper Electron.

---

## 🚀 Fitur Utama Aplikasi

### 1. Sistem Kasir POS (Point of Sale)
- **Tata Letak Dual-Pane Desktop**: Sisi kiri untuk pencarian katalog dan kategori; sisi kanan untuk keranjang belanja real-time dan kalkulator pembayaran.
- **Pencarian Cepat & Barcode**: Pencarian berdasarkan nama produk, SKU, kategori, serta pemindaian barcode USB (keyboard emulation dengan penekanan Enter otomatis).
- **Scanner Kamera Offline**: Opsi pemindaian barcode kamera lokal berbasis HTML5 Canvas / BarcodeDetector API dengan fallback informatif untuk scanner USB fisik.
- **Transaksi Campuran**: Keranjang mendukung pembelian kombinasi produk fisik (makanan, pasir, vitamin) dan layanan jasa (grooming, mandi, salon, hotel hewan) dalam satu nota.
- **Kalkulasi Otomatis & Akurat**: Menghitung subtotal, diskon (persentase % atau nominal tetap Rp), pajak penjualan (PPN yang dapat diaktifkan/dinonaktifkan), grand total, serta kalkulator uang kembalian.
- **Pilihan Uang Cepat (Quick Cash)**: Tombol nominal praktis (Uang Pas, Rp20.000, Rp50.000, Rp100.000, +Rp10.000, dll).
- **Pencatatan Metode Pembayaran**: Mendukung Tunai (Cash), QRIS, dan Transfer Bank tanpa ketergantungan payment gateway online.
- **Pintasan Keyboard (Shortcuts)**:
  - `F2`: Fokus ke input barcode / scanner
  - `F4`: Buka jendela pembayaran (Bayar)
  - `F8`: Kosongkan keranjang
  - `Escape`: Tutup modal popup

### 2. Format Struk Thermal & Struk Digital
- **Format Struk Hemat Tinta**: Didesain khusus untuk printer kasir thermal dengan pilihan ukuran **58mm** dan **80mm**.
- **Informasi Lengkap**: Nama toko, alamat, telepon, nomor transaksi unik (`PET-YYYYMMDD-XXXX`), tanggal dan jam WIB, rincian barang/jasa, nama hewan/pemilik jika ada, total, pembayaran, kembalian, dan pesan kaki nota (footer).
- **Print Stylesheet Khusus**: Aturan `@media print` menyembunyikan navigasi dan seluruh elemen UI, hanya mencetak struk kasir.
- **Struk Digital Offline**:
  - Tombol **Salin Teks Struk** ke clipboard (dapat langsung ditempel ke aplikasi pesan/WhatsApp desktop).
  - Tampilan struk thermal yang dapat dicetak ulang kapan saja dari menu Riwayat Transaksi.

### 3. Katalog & Inventaris Produk
- Data produk lengkap: SKU, Barcode, Nama Produk, Kategori, Jenis Produk, Satuan (pcs, kg, pouch, kaleng, pack), HPP (Harga Beli), Harga Jual, Stok Fisik, Batas Minimum Stok, Deskripsi, dan Status Aktif/Nonaktif.
- **Integritas Transaksi Lama**: Menggunakan soft delete dan snapshot nama/harga pada riwayat transaksi sehingga perubahan harga atau penghapusan produk di masa depan tidak merusak histori lama.
- Filter inventaris: Semua Produk, Stok Tersedia, Stok Menipis, dan Stok Habis.

### 4. Manajemen Stok & Kartu Mutasi (Stock Movement)
- **Pencatatan Atomik**: Setiap transaksi kasir otomatis memotong stok produk secara atomik melalui `DB::transaction()`.
- **Riwayat Pergerakan Lengkap**: Mencatat jenis pergerakan:
  - `initial`: Stok awal produk
  - `in`: Penerimaan stok masuk (restock) beserta harga beli dan faktur supplier
  - `sale`: Penjualan kasir
  - `adjustment`: Koreksi stok fisik / stock opname
  - `reversal`: Pengembalian stok otomatis saat transaksi dibatalkan
- **Peringatan Stok Menipis**: Peringatan visual otomatis ketika stok sama dengan atau di bawah batas minimum (`min_stock`).

### 5. Modul Kategori & Layanan Jasa
- **Kategori Dinamis**: Dikelola langsung melalui aplikasi (Kucing, Anjing, Burung, Pasir & Kebersihan, Makanan Hewan, Grooming, dll).
- **Layanan Jasa Pet Shop**: Mandi sehat, grooming kutu/jamur, potong kuku, penitipan hewan (pet hotel). Layanan tidak memiliki stok fisik dan tidak mengurangi inventaris.

### 6. Dashboard & Laporan Lengkap
- **Dashboard Operasional**: Total omset hari ini, estimasi laba kotor hari ini, jumlah transaksi, peringatan stok menipis, daftar produk habis, transaksi kasir terbaru, dan item terlaris minggu ini.
- **Laporan Penjualan**: Filter tanggal fleksibel (harian, mingguan, bulanan, atau rentang tanggal bebas), rincian pajak, diskon, dan grafik batang harian lokal tanpa library eksternal.
- **Laporan Item Terlaris (Best Sellers)**: Pemisahan jelas antara produk fisik paling laris dan layanan jasa paling diminati.
- **Pembagian Metode Bayar**: Ringkasan omset Tunai, QRIS, dan Transfer Bank.

### 7. Backup & Restore Mandiri
- **Backup Database SQLite**: Pembuatan salinan database lokal bertanda waktu (`.sqlite`) dengan satu klik.
- **Ekspor JSON & CSV**: Ekspor seluruh struktur data ke JSON serta ekspor tabel transaksi dan produk ke format CSV untuk pembukuan Excel.
- **Restore Aman**: Dilengkapi validasi header file SQLite dan pengecekan integritas tabel sebelum diterapkan, serta pembuatan *backup pengaman otomatis* sebelum database digantikan.

---

## 💻 Kebutuhan Sistem (System Requirements)

- **Sistem Operasi**: Windows 10 atau Windows 11 (64-bit)
- **PHP**: Versi 8.2 atau lebih baru (PHP 8.3 / 8.4 direkomendasikan) dengan ekstensi aktif:
  - `pdo_sqlite`
  - `sqlite3`
  - `fileinfo`
  - `mbstring`
  - `openssl`
- **Node.js**: Versi 18+ (hanya diperlukan jika ingin mengompilasi ulang asset frontend atau memaketkan ke Electron)
- **Resolusi Layar**: Dioptimalkan untuk 1280x720, 1366x768, 1440x900, dan 1920x1080.

---

## 📁 Struktur Folder Utama

```
kasir-petshop-desktop/
├── app/
│   ├── Console/Commands/
│   │   └── BootstrapDesktopCommand.php  # Perintah inisialisasi desktop otomatis
│   ├── Http/
│   │   ├── Controllers/               # Controller POS, Produk, Stok, Transaksi, dll
│   │   └── Requests/                  # Form Request validasi offline
│   ├── Models/                        # Product, Category, Service, Transaction, dll
│   └── Services/                      # Service Layer bisnis logic independen
├── database/
│   ├── migrations/                    # Migrasi tabel SQLite
│   ├── seeders/                       # Seeder kategori & layanan awal
│   └── database.sqlite                # File database lokal aktif
├── electron/
│   ├── main.cjs                       # Main process Electron wrapper
│   └── preload.cjs                    # Context bridge Electron
├── public/
│   ├── css/
│   │   └── petshop-ui.css             # CSS design system lokal buatan sendiri
│   └── js/
│       ├── petshop-app.js             # Toast, modal, dan utilitas offline
│       └── pos-engine.js              # Mesin kalkulasi kasir & scanner barcode
├── resources/views/                   # Template Blade lengkap
├── storage/app/backups/               # Lokasi arsip backup SQLite lokal
├── run-desktop.bat                    # Launcher Windows 1-klik (.bat)
├── run-desktop.ps1                    # Launcher Windows PowerShell (.ps1)
└── routes/web.php                     # Rute aplikasi (langsung menuju kasir)
```

---

## ⚡ Cara Menjalankan untuk Pengembangan (Development)

1. **Clone atau Buka Direktori Proyek**:
   ```bash
   cd C:\xampp\htdocs\kasir-petshop-desktop
   ```

2. **Pasang Dependensi PHP**:
   ```bash
   composer install
   ```

3. **Inisialisasi Database SQLite & Data Awal**:
   ```bash
   php artisan app:desktop-init
   ```

4. **Jalankan Automated Tests (Pest)**:
   ```bash
   php artisan test
   ```

5. **Jalankan Aplikasi Desktop**:
   Cukup klik ganda pada file `run-desktop.bat` atau jalankan via terminal:
   ```cmd
   run-desktop.bat
   ```

---

## 🖥️ Menjalankan sebagai Aplikasi Desktop Standalone

### Opsi 1: Windows App Mode Launcher (Rekomendasi Cepat & Ringan)
Cukup klik dua kali pada file **`run-desktop.bat`**.
Skrip ini akan secara otomatis:
1. Memeriksa keberadaan runtime PHP lokal.
2. Memastikan file database SQLite dan folder penyimpanan lokal tersedia.
3. Menjalankan migrasi dan seeding master awal secara otomatis jika baru pertama kali dijalankan.
4. Menjalankan server lokal terisolasi yang diikat strictly ke alamat **`127.0.0.1:8765`** (tidak membuka port ke jaringan publik/LAN).
5. Membuka jendela desktop aplikasi mandiri tanpa address bar atau tab browser menggunakan Microsoft Edge / Google Chrome Application Mode.

### Opsi 2: Pengemasan Menjadi Windows `.exe` (Electron)
Aplikasi telah dilengkapi dengan berkas `electron/main.cjs` dan `electron/preload.cjs`:
1. Pastikan dependensi Electron terpasang:
   ```bash
   npm install --save-dev electron electron-builder
   ```
2. Jalankan dalam mode Electron desktop:
   ```bash
   npm run desktop
   ```
3. Untuk memaketkan menjadi file installer `.exe` portable, gunakan `electron-builder` dengan menyertakan runtime PHP embedded lokal ke dalam folder bundle.

---

## 🖨️ Panduan Printer Thermal & Scanner Barcode

1. **Printer Thermal USB / Driver Windows**:
   - Pasang driver printer thermal (58mm atau 80mm) di Windows.
   - Atur printer sebagai Default Printer di Windows.
   - Pada halaman struk kasir, klik **"Cetak Struk Thermal"** (atau tekan `Ctrl+P`).
   - Dialog cetak sistem operasi akan terbuka otomatis dengan format yang pas sesuai ukuran kertas struk.
2. **Printer Thermal Bluetooth**:
   - Hubungkan (pair) printer Bluetooth ke komputer Windows.
   - Tambahkan printer Bluetooth melalui *Windows Settings > Bluetooth & Devices > Printers & Scanners*.
   - Pasang virtual COM / generic text driver jika disediakan oleh pabrikan printer.
3. **Scanner Barcode USB**:
   - Scanner barcode USB bekerja secara *plug-and-play* menyerupai keyboard standar.
   - Arahkan kursor ke input kasir (atau tekan `F2`), lalu scan barcode barang. Scanner akan otomatis mengirimkan kode barcode dan menekan `Enter`, sehingga item langsung masuk ke keranjang belanja.
4. **Scanner Kamera**:
   - Jika komputer kasir dilengkapi webcam dan engine browser mendukung `BarcodeDetector`, tombol **"Kamera"** dapat digunakan untuk mendeteksi barcode kemasan secara lokal tanpa internet.

---

## 🔒 Keamanan & Penyimpanan Data Lokal

- **Lokasi Database Aktif**: Tersimpan secara default di `database/database.sqlite`. Pada mode installer Windows, path dapat diarahkan ke folder `%APPDATA%\KasirPetshop\database.sqlite` agar data tidak hilang ketika aplikasi diperbarui atau di-uninstall.
- **Isolasi Jaringan**: Server internal hanya mendengarkan loopback interface `127.0.0.1`, mencegah akses tidak sah dari komputer lain di jaringan lokal tanpa izin pemilik toko.
- **Cadangan Rutin**: Lakukan backup berkala melalui menu **Backup** di dalam aplikasi untuk mengamankan data ke flashdisk atau harddisk eksternal.
