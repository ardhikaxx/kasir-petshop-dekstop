# Panduan Penggunaan Aplikasi Kasir Pet Shop Desktop
## Aplikasi Kasir POS & Inventaris Pet Shop (100% Offline)

Selamat datang di aplikasi **Kasir Pet Shop Desktop**. Aplikasi ini dirancang khusus untuk operasional harian toko perlengkapan hewan dan klinik grooming tanpa memerlukan koneksi internet sama sekali.

---

### 1. Cara Memasang Aplikasi (Instalasi Pertama Kali)

Anda hanya memerlukan satu file installer: **`PetShopPOS-Setup.exe`**.

1. **Klik ganda (double-click)** pada file `PetShopPOS-Setup.exe`.
2. Jendela instalasi akan muncul di layar.
3. Anda dapat langsung mengklik tombol hijau **"Pasang Sekarang"**.
4. Tunggu beberapa detik hingga proses ekstraksi selesai dan muncul pesan **"Instalasi Berhasil!"**.
5. Klik **"Selesai"**. Aplikasi kasir akan langsung terbuka di layar komputer Anda.

> **Catatan Penting:** Anda tidak perlu menginstal software pendukung apa pun (seperti XAMPP atau database). Aplikasi ini sudah mandiri dan lengkap.

---

### 2. Membuka & Menutup Aplikasi

- **Membuka Aplikasi:**
  Klik ganda shortcut **"Kasir Pet Shop"** di layar **Desktop** atau cari di **Start Menu Windows**.
- **Ikon System Tray (Pojok Kanan Bawah Taskbar):**
  Saat aplikasi berjalan, akan ada ikon jejak kaki kucing di taskbar pojok kanan bawah.
  - **Klik ganda ikon:** Membuka kembali layar kasir.
  - **Klik kanan ikon:** Menampilkan menu cepat (Buka Kasir, Buka Dashboard, Laporan, atau Tutup Kasir Pet Shop).
- **Menutup Aplikasi:**
  Klik kanan ikon di pojok kanan bawah taskbar lalu pilih **"Tutup Kasir Pet Shop (Keluar)"**.

---

### 3. Panduan Operasional Kasir (POS)

#### A. Melakukan Transaksi Penjualan
1. Pada layar kasir, cari produk melalui:
   - Kolom pencarian (ketik nama produk atau SKU).
   - Scanner barcode USB (cukup arahkan scanner ke barcode produk fisik).
   - Memilih kategori produk di sebelah atas.
2. Klik item untuk memasukkannya ke dalam keranjang belanja.
3. Anda dapat menggabungkan **produk fisik** (seperti pakan kucing, pasir, vitamin) dan **layanan jasa** (seperti grooming, mandi sehat, pet hotel) dalam satu nota transaksi yang sama.
4. Sesuaikan jumlah (*quantity*) atau diskon per item jika ada.
5. Klik tombol besar **"Lanjut ke Pembayaran"**.
6. Pilih metode pembayaran:
   - **Tunai:** Masukkan uang yang diterima pembeli. Sistem otomatis menghitung kembalian.
   - **QRIS / Transfer Bank:** Metode pembayaran non-tunai offline.
7. Klik **"Selesaikan Transaksi"**.

#### B. Mencetak Struk Pembayaran
Setelah transaksi selesai, jendela struk akan otomatis tampil:
- **Cetak Struk:** Klik tombol **"Cetak Struk (Thermal)"** untuk mencetak langsung ke printer kasir thermal (ukuran 58mm atau 80mm).
- **Salin Teks Nota:** Klik **"Salin Nota Teks"** jika Anda ingin mengirim rincian belanja ke pelanggan melalui aplikasi pesan teks.

---

### 4. Manajemen Produk & Stok Barang

- **Katalog Produk:** Menu untuk menambah produk baru, harga beli (HPP), harga jual, dan batas stok minimum.
- **Stok Masuk:** Catat setiap kali ada kiriman barang dari supplier lengkap dengan nomor referensi dan catatan agar histori stok selalu rapi.
- **Peringatan Stok Menipis:** Dashboard akan otomatis menampilkan badge kuning/merah jika ada barang yang hampir habis atau sudah kosong.

---

### 5. Cadangan Data (Backup & Restore)

Untuk memastikan data penjualan Anda selalu aman dari kerusakan komputer:

1. Buka menu **Cadangan & Pulihkan (Backup)** pada bilah samping.
2. Klik tombol **"Buat Cadangan Baru"**. File cadangan `.sqlite` dengan cap tanggal dan jam akan dibuat seketika.
3. Anda dapat mengunduh file cadangan tersebut dan menyimpannya di Flashdisk atau hard disk eksternal.
4. Jika suatu saat Anda ingin memulihkan data pada komputer baru, cukup pilih file cadangan Anda pada menu **Pulihkan Database**.

---

### 6. Memperbarui Aplikasi ke Versi Baru (Update)

Jika Anda menerima file installer versi terbaru:
1. Pastikan aplikasi lama sudah ditutup.
2. Cukup jalankan installer versi baru tersebut.
3. **Seluruh data transaksi, riwayat penjualan, data produk, dan pengaturan toko Anda akan tetap tersimpan aman** karena database disimpan pada folder terpisah di komputer Anda (`AppData\Local\KasirPetShop`).
