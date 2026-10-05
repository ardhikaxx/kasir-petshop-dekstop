# 🐾 Kasir Pet Shop Desktop v1.2.0 - Standalone Windows Release

Aplikasi kasir desktop profesional, mandiri (*self-contained*), dan lengkap untuk operasional toko hewan peliharaan (*Pet Shop* & Klinik Hewan) yang dibangun menggunakan **Laravel 13**, **SQLite**, dan dibungkus dengan **Native Windows Launcher (C# .NET)**.

Dapat diinstal dan digunakan oleh siapa saja di Windows cukup dengan **1 file installer `.exe`**, tanpa perlu menginstal PHP, Composer, Node.js, XAMPP, maupun konfigurasi server secara manual.

---

### 📦 Unduh Installer (Binary Assets)
- **`KasirPetShop-Setup.exe`** (~50.7 MB): Installer resmi Windows dengan ikon visual siluet anjing & kucing.
- **`PetShopPOS-Setup.exe`** (~50.7 MB): Installer alternatif mandiri.

> **Petunjuk Singkat:**
> 1. Unduh salah satu file installer di atas.
> 2. Klik ganda pada file installer `.exe`.
> 3. Klik tombol **"Pasang Sekarang"**.
> 4. Buka aplikasi dari shortcut **Desktop** atau **Start Menu**, sistem kasir langsung siap digunakan!

---

### 🌟 Fitur Utama Rilis v1.2.0:
- **100% Offline-First & Zero CDN**: Berjalan penuh tanpa koneksi internet, tanpa dependensi server eksternal, dan tanpa proses login.
- **Tema Desain Klinik Hewan**: Mengadopsi palet warna hangat *Rose/Mauve Veterinary* (`#D88C9A` & `#B76E79`) dengan kontras tinggi.
- **Ikon Win32 Native**: Ikon multi-resolusi (16×16 s/d 256×256) berstandar Win32 DIB di-render langsung dari siluet vektor `logo-klinik2.svg`.
- **Kasir POS Dual-Pane**: Pencarian katalog instan, keranjang kasir real-time, barcode scanner USB plug-and-play, kalkulasi diskon & pajak, tombol uang pas, dan kalkulator uang kembalian.
- **Format Struk Thermal**: Format cetak hemat tinta untuk printer kasir 58mm dan 80mm, serta tombol salin struk digital (WhatsApp).
- **Inventaris & Mutasi Stok Atomik**: Manajemen produk, batas minimum stok, dan pencatatan riwayat kartu mutasi stok otomatis via transaksi database.
- **Modul Layanan Jasa Pet Shop**: Manajemen jasa perawatan hewan (grooming, mandi, salon, pet hotel).
- **Dashboard & Laporan Penjualan**: Rekap omset, laba kotor, item terlaris, dan rincian metode pembayaran (Tunai, QRIS, Transfer).
- **Isolasi Data Pengguna Windows**: Database SQLite dan folder backup tersimpan di `%LOCALAPPDATA%\KasirPetShop` sehingga data transaksi tidak akan hilang saat aplikasi diperbarui.
- **Backup & Restore Mandiri**: Backup 1-klik file SQLite bertanda waktu dan ekspor CSV untuk Microsoft Excel.

---

**Yanuar Ardhika Rahmadhani Ubaidillah (@ardhikaxx)**  
*Lead Software Architect & Maintainer*
