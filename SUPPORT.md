# 💬 Dukungan & Bantuan Teknis (Support)

Terima kasih telah menggunakan dan mempercayai **Kasir Pet Shop Desktop (Offline POS)** untuk operasional toko hewan peliharaan atau klinik hewan Anda.

Jika Anda membutuhkan bantuan teknis, konsultasi implementasi, pelaporan kendala, atau kustomisasi khusus, silakan hubungi kanal komunikasi resmi berikut:

---

## 📬 Kanal Komunikasi Resmi

* **Pemilik & Pengembang**: **Yanuar Ardhika Rahmadhani Ubaidillah (@ardhikaxx)**
* **Email Resmi**: `ardhikayanuar58@gmail.com`
* **GitHub Issues**: [https://github.com/ardhikaxx/kasir-petshop-dekstop/issues](https://github.com/ardhikaxx/kasir-petshop-dekstop/issues)
* **Repositori Resmi**: [https://github.com/ardhikaxx/kasir-petshop-dekstop](https://github.com/ardhikaxx/kasir-petshop-dekstop)

---

## 📚 Sumber Dokumentasi Mandiri

Sebelum mengajukan pertanyaan, pastikan Anda telah membaca dokumen pendukung yang tersedia:

1. [`README.md`](./README.md) — Gambaran umum fitur, panduan installer `.exe`, dan setup development.
2. [`DOKUMENTASI.md`](./DOKUMENTASI.md) — Arsitektur teknis lengkap, logika perhitungan kasir, dan isolasi SQLite.
3. [`CHANGELOG.md`](./CHANGELOG.md) — Riwayat pembaruan dan rilis versi.
4. [`SECURITY.md`](./SECURITY.md) — Kebijakan keamanan data dan pelaporan celah.
5. [`CONTRIBUTING.md`](./CONTRIBUTING.md) — Pedoman kontribusi kode bagi pengembang.
6. [`CITATION.md`](./CITATION.md) — Format sitasi karya untuk kebutuhan akademik.

---

## 🧭 Langkah Pemeriksaan Mandiri (Troubleshooting)

1. **Aplikasi Tidak Mau Terbuka**:
   - Periksa apakah aplikasi sudah berjalan di latar belakang melalui **System Tray** (ikon di dekat jam Windows di pojok kanan bawah).
   - Klik kanan ikon tray Kasir Pet Shop, lalu pilih **"Buka Kasir (POS)"**.
2. **Printer Thermal Tidak Mencetak**:
   - Pastikan driver printer thermal (58mm / 80mm) sudah terpasang dengan baik di Windows.
   - Jadikan printer thermal sebagai *Default Printer* pada menu *Windows Settings > Printers & Scanners*.
3. **Database & Log**:
   - Log aplikasi tersimpan di: `%LOCALAPPDATA%\KasirPetShop\logs\launcher.log` dan `storage\logs\laravel.log`.
   - File database SQLite tersimpan di: `%LOCALAPPDATA%\KasirPetShop\data\database.sqlite`.

---

## 🛠️ Layanan Kustomisasi & Implementasi

Butuh penyesuaian khusus untuk bisnis toko hewan Anda? Kami menyediakan layanan:
* Kustomisasi format struk thermal dengan logo toko khusus.
* Integrasi perangkat keras kasir tambahan (cash drawer RJ11, timbangan digital, dll).
* Pengembangan modul tambahan (booking jadwal dokter hewan, penitipan hewan berkala, dll).
* Instalasi langsung di komputer kasir toko Anda.

Hubungi email: `ardhikayanuar58@gmail.com` untuk konsultasi.

---

## 💖 Dukungan & Donasi

Jika proyek **Kasir Pet Shop Desktop** ini bermanfaat untuk bisnis Anda dan menghemat waktu operasional kasir Anda, Anda dapat menunjukkan apresiasi dan traktiran kopi kepada pengembang melalui pemindaian kode **QRIS** yang tersedia pada [README.md](./README.md#--dukungan--donasi).

---

**Yanuar Ardhika Rahmadhani Ubaidillah (@ardhikaxx)**  
*Lead Software Architect & Maintainer*
