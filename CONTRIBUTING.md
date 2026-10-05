# 🤝 Panduan Kontribusi (Contributing Guidelines)

Terima kasih atas ketertarikan Anda untuk berkontribusi pada pengembangan **Kasir Pet Shop Desktop (Offline POS)**!

Aplikasi ini dirancang sebagai sistem kasir mandiri (*offline-first*) yang tangguh, cepat, dan mudah digunakan pada komputer kasir Windows. Untuk menjaga kualitas dan konsistensi basis kode, silakan ikuti pedoman berikut:

---

## 📌 Prinsip Arsitektur Aplikasi

Setiap kontribusi wajib mematuhi pilar utama sistem ini:
1. **100% Offline-First**: Tidak boleh menambahkan ketergantungan yang membutuhkan koneksi internet untuk menjalankan fungsi utama (misal: API eksternal, cloud database, remote CDN).
2. **Zero Remote CDN**: Seluruh stylesheet, JavaScript, SVG icon, dan font harus tersimpan secara lokal di folder `public/`.
3. **Tanpa Sistem Login**: Aplikasi dirancang langsung masuk ke menu kasir POS / Dashboard untuk efisiensi toko single-device.
4. **Isolasi Database Pengguna**: Konfigurasi database SQLite harus mendukung lokasi terisolasi `%LOCALAPPDATA%\KasirPetShop` pada lingkungan Windows Desktop.

---

## 🛠️ Standar Pengembangan Kode (Code Standards)

* **Bahasa & Framework**: PHP 8.4+ dan Laravel 13.x.
* **Format Gaya Kode (Formatting)**: Wajib menjalankan formatter Pint sebelum commit:
  ```bash
  vendor/bin/pint --dirty --format agent
  ```
* **Struktur Kode Laravel**:
  - Controller tetap ramping (*thin controllers*).
  - Validasi form menggunakan Form Request (`app/Http/Requests/`) dengan pesan error Bahasa Indonesia.
  - Logika bisnis yang kompleks atau melibatkan transaksi database wajib ditempatkan di Service Layer (`app/Services/`), seperti `PosService`, `StockService`, dan `ReportService`.
  - Operasi mutasi stok wajib menggunakan `DB::transaction()`.
* **Testing**:
  - Setiap penambahan fitur atau perbaikan bug wajib disertai dengan automated tests Pest (`php artisan make:test --pest {NamaTest}`).
  - Pastikan seluruh test lulus 100%:
    ```bash
    php artisan test --compact
    ```
* **Launcher & Installer Windows**:
  - Komponen wrapper desktop ditulis dalam C# (.NET Framework 4.8) yang dapat dikompilasi langsung menggunakan `csc.exe` bawaan Windows tanpa dependency external besar.
  - Skrip build otomatis berada di `build-windows.ps1`.

---

## 🌿 Alur Kerja Git (Git Workflow)

1. **Fork & Clone Repositori**:
   ```bash
   git clone https://github.com/ardhikaxx/kasir-petshop-dekstop.git
   cd kasir-petshop-dekstop
   ```

2. **Buat Branch Kerja Baru**:
   ```bash
   git checkout -b feat/nama-fitur-baru
   # atau
   git checkout -b fix/perbaikan-bug
   ```

3. **Format Commit Message**:
   Gunakan standar [Conventional Commits](https://www.conventionalcommits.org/):
   - `feat: tambah filter tanggal pada laporan omset`
   - `fix: perbaiki validasi stok pada transaksi kasir`
   - `docs: perbarui panduan printer thermal di README`
   - `refactor: optimasi query penghitungan laba kotor`

4. **Kirim Pull Request (PR)**:
   - Pastikan branch Anda telah sinkron dengan branch `main` terbaru.
   - Cantumkan penjelasan perubahan, alasan, dan hasil pengujian (*test pass*).
   - PR akan ditinjau oleh maintainer sebelum digabungkan ke repositori utama.

---

## 🐞 Melaporkan Masalah (Issues & Bug Reports)

- Gunakan fitur [GitHub Issues](https://github.com/ardhikaxx/kasir-petshop-dekstop/issues) untuk melaporkan bug atau mengajukan permintaan fitur baru.
- Sertakan versi Windows, langkah mereproduksi error, dan screenshot bila memungkinkan.
- Untuk laporan celah keamanan sensitif, ikuti panduan di [SECURITY.md](./SECURITY.md).

---

**Yanuar Ardhika Rahmadhani Ubaidillah (@ardhikaxx)**  
*Maintainer Kasir Pet Shop Desktop*
