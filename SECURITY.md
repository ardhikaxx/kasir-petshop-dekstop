# 🛡️ Kebijakan Keamanan (Security Policy)

Keamanan integritas data transaksi kasir, inventaris toko, serta database lokal adalah hal yang sangat diperhatikan dalam pengembangan sistem **Kasir Pet Shop Desktop (Offline POS)**.

Meskipun aplikasi ini berjalan secara lokal dan offline tanpa koneksi internet, langkah-langkah proteksi tetap diterapkan secara ketat demi menjaga keamanan sistem pengguna.

---

## 📦 Versi yang Didukung

Pembaruan keamanan dan perbaikan celah aktif diberikan untuk rilis berikut:

| Versi | Status Keamanan |
| :--- | :--- |
| **v1.2.x** | ✅ Didukung Penuh (*Active Support*) |
| **v1.1.x** | ⚠️ Patch Terbatas |
| < 1.1.0 | ❌ Tidak Didukung |

---

## 🔒 Proteksi Keamanan Bawaan (Built-in Security Layers)

1. **Isolasi Jaringan (Network Isolation)**:
   * Server PHP internal yang dijalankan oleh native launcher terikat secara ketat (*strictly bound*) hanya ke antarmuka loopback lokal **`127.0.0.1:8765`**.
   * Server tidak membuka port ke IP publik, Wi-Fi umum, atau jaringan LAN luar tanpa otorisasi pemilik komputer.
2. **Penyimpanan Terpisah dari Program Files**:
   * Database SQLite aktif (`database.sqlite`), backup transaksi, dan log disimpan di direktori data pengguna Windows (`%LOCALAPPDATA%\KasirPetShop`).
   * Aplikasi tidak mencoba menulis file database ke folder sistem yang memerlukan hak Administrator (*UAC elevation*), mencegah konflik izin baca-tulis di Windows.
3. **Pencegahan Injeksi & Integritas Data**:
   * Seluruh query database menggunakan Prepared Statements melalui Eloquent ORM Laravel, kebal dari serangan **SQL Injection**.
   * Validasi integritas header SQLite dan tabel dilakukan sebelum proses *Restore Database* dieksekusi.
   * Backup otomatis pengaman dibuat sebelum database digantikan oleh proses restore.
4. **Proteksi Form & Output Web**:
   * Seluruh request HTTP POST/PUT/DELETE dilindungi oleh token **CSRF**.
   * Output Blade otomatis di-escape untuk mencegah serangan Cross-Site Scripting (**XSS**).

---

## 🚨 Melaporkan Celah Keamanan (Reporting a Vulnerability)

Jika Anda menemukan potensi celah keamanan atau kerentanan pada launcher desktop, script build, maupun aplikasi Laravel:

> [!CAUTION]
> **JANGAN** membuat laporan celah keamanan secara terbuka melalui GitHub Issues publik demi keamanan seluruh pengguna.

Silakan kirimkan laporan secara privat dan bertanggung jawab (*Responsible Disclosure*) ke:

* **Email Resmi**: `ardhikayanuar58@gmail.com`
* **Subjek Email**: `[SECURITY VULNERABILITY] - Kasir Pet Shop Desktop`

### Informasi yang Disarankan:
1. Deskripsi mendalam mengenai celah atau potensi risiko yang ditemukan.
2. Langkah-langkah detail atau *proof-of-concept (PoC)* untuk mereproduksi masalah.
3. Versi Windows dan versi aplikasi yang diuji.

Laporan yang masuk akan ditindaklanjuti dan diberikan perbaikan dalam kurun waktu **1x24 jam**.

---

**Yanuar Ardhika Rahmadhani Ubaidillah (@ardhikaxx)**  
*Maintainer & Security Lead*
