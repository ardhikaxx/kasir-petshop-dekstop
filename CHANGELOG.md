# 📋 Changelog

Semua perubahan penting dan riwayat pengembangan proyek **Kasir Pet Shop Desktop (Offline POS)** didokumentasikan dalam berkas ini.

Format pencatatan mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.0.0/) dan mematuhi prinsip [Semantic Versioning (SemVer)](https://semver.org/).

---

## [1.2.0] - 2026-10-06
### 🎨 Pembaruan Tema Warna Klinik Hewan & Ikon Win32 Native
- **Tema Warna Klinik Hewan**:
  - Mengadopsi palet warna hangat *Rose/Mauve Veterinary*: Primary `#D88C9A`, Dark `#B76E79` & `#9E5F6A`, Background `#FDE8E8` & `#FFF5F5`, Card `#F2C5C5`, Text `#433133`, dan header topbar linear gradient.
- **Ikon & Logo Resmi (`logo-klinik2.svg`)**:
  - Parser vektor SVG C# (`build/MakeIcon.cs`) dengan auto-bounding-box untuk memusatkan dan memaksimalkan siluet anjing, kucing, dan hati di dalam squircle `#D88C9A`.
  - Generator ikon multi-resolusi (16, 24, 32, 48, 64, 128) menggunakan standar Win32 DIB (`BITMAPINFOHEADER` + XOR/AND mask) dan 256×256 PNG agar Windows Explorer langsung menampilkan ikon tanpa error.
- **Integrasi Setup & Asset**:
  - Tampilan wizard installer Windows (`KasirPetShopSetup.cs`) disesuaikan dengan warna mauve `#D88C9A`.
  - Penambahan output otomatis ke `KasirPetShop-Setup.exe` dan `PetShopPOS-Setup.exe` di folder Downloads.
  - Refresh cache shell Windows otomatis pasca kompilasi.

---

## [1.1.0] - 2026-10-05
### 📦 Pipeline Build Standalone Windows Installer (.exe)
- **Native C# Desktop Launcher (`KasirPetShop.exe`)**:
  - Single instance mutex (`KasirPetShopDesktopSingleInstanceMutex`).
  - System Tray NotifyIcon interaktif untuk navigasi kasir, dashboard, produk, dan laporan.
  - Penanganan startup otomatis: migrasi aman via `app:desktop-init`, bind server lokal `127.0.0.1:8765`, dan pembukaan jendela kasir.
- **Isolasi Data Pengguna Windows**:
  - Database SQLite aktif dialihkan ke `%LOCALAPPDATA%\KasirPetShop\data\database.sqlite`.
  - Folder backup dan log terisolasi agar database tidak hilang saat update atau uninstall.
- **Standalone Windows Installer (`installer/KasirPetShopSetup.cs`)**:
  - Wizard instalasi visual mandiri, bundling PHP runtime 8.4 portabel, Laravel 13, dan vendor production.
  - Ekstraksi otomatis dan pembuatan shortcut Desktop & Start Menu.
- **Native Uninstaller (`installer/KasirPetShopUninstall.cs`)**:
  - Pembersihan file program, registri, dan opsi mempertahankan database transaksi pengguna.
- **Reset Transaksi Awal**:
  - Fitur hapus seluruh transaksi demo dengan mempertahankan master produk, kategori, dan pengaturan toko.

---

## [1.0.0] - 2026-10-05
### 🚀 Rilis Perdana (Initial Release)
Dikembangkan oleh **Yanuar Ardhika Rahmadhani Ubaidillah (@ardhikaxx)**.

### ✨ Added (Fitur Utama)
- **Arsitektur 100% Offline-First**:
  - Berjalan sepenuhnya tanpa ketergantungan koneksi internet.
  - Zero CDN: seluruh asset CSS, JS, dan ikon SVG bersifat lokal.
  - Tanpa sistem login/autentikasi: langsung menuju kasir POS.
- **Sistem Kasir Dual-Pane (POS)**:
  - Pencarian katalog produk & kategori dinamis di sisi kiri.
  - Keranjang belanja real-time, kalkulator diskon/pajak, dan uang kembalian di sisi kanan.
  - Dukungan Barcode Scanner USB (keyboard emulation).
  - Pintasan keyboard operasional (`F2`, `F4`, `F8`, `Escape`).
  - Pilihan uang cepat (*Quick Cash*).
  - Transaksi kombinasi produk fisik & layanan jasa.
- **Format Struk Thermal & Digital**:
  - Format cetak printer thermal 58mm dan 80mm.
  - Aturan `@media print` untuk cetak bersih tanpa navbar.
  - Salin teks struk ke clipboard (struk digital WhatsApp).
- **Katalog & Inventaris Produk**:
  - Manajemen SKU, Barcode, HPP, Harga Jual, dan Stok Minimum.
  - Perlindungan riwayat transaksi menggunakan soft delete dan snapshot harga.
- **Mutasi Stok Atomik**:
  - Pengurangan stok aman berbasis database transaction.
  - Riwayat kartu stok (`initial`, `in`, `sale`, `adjustment`, `reversal`).
- **Modul Layanan Jasa Pet Shop**:
  - Manajemen perawatan hewan (grooming, mandi, salon, pet hotel) tanpa pengurangan stok fisik.
- **Dashboard & Laporan**:
  - Dashboard statistik omset hari ini, laba kotor, item terlaris, dan stok menipis.
  - Laporan filter tanggal, rekapitulasi metode pembayaran (Tunai, QRIS, Transfer).
- **Cadangan Data (Backup & Restore)**:
  - Backup 1-klik file SQLite bertanda waktu.
  - Ekspor CSV transaksi dan produk untuk Microsoft Excel.
  - Restore aman dengan validasi integritas header database SQLite.
- **Automated Testing**:
  - 40 Unit & Feature Test menggunakan Pest PHP dengan 100% kelulusan.
