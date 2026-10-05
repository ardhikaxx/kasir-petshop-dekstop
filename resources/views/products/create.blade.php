@extends('layouts.app')

@section('title', 'Tambah Produk Baru')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            <span>Tambah Produk Baru</span>
        </h1>
        <p class="page-subtitle">Daftarkan item fisik baru ke dalam inventaris toko.</p>
    </div>

    <div class="page-actions">
        <a href="{{ route('products.index') }}" class="btn btn-outline">Kembali ke Daftar</a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h2 class="card-title">Formulir Data Produk</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('products.store') }}">
            @csrf

            <!-- Product Name -->
            <div class="form-group">
                <label class="form-label required">Nama Produk</label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" 
                       placeholder="Contoh: Royal Canin Kitten 400g" required autofocus>
            </div>

            <div class="form-row">
                <!-- Category -->
                <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- Pilih Kategori --</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Product Type -->
                <div class="form-group">
                    <label class="form-label required">Jenis Produk</label>
                    <select name="type" class="form-select" required>
                        <option value="Makanan" {{ old('type') == 'Makanan' ? 'selected' : '' }}>Makanan Hewan</option>
                        <option value="Obat/Vitamin" {{ old('type') == 'Obat/Vitamin' ? 'selected' : '' }}>Obat & Vitamin</option>
                        <option value="Pasir" {{ old('type') == 'Pasir' ? 'selected' : '' }}>Pasir & Kebersihan</option>
                        <option value="Aksesori" {{ old('type') == 'Aksesori' ? 'selected' : '' }}>Aksesori & Tali</option>
                        <option value="Mainan" {{ old('type') == 'Mainan' ? 'selected' : '' }}>Mainan Hewan</option>
                        <option value="Kandang" {{ old('type') == 'Kandang' ? 'selected' : '' }}>Kandang & Tas</option>
                        <option value="Perlengkapan" {{ old('type') == 'Perlengkapan' ? 'selected' : '' }}>Perlengkapan Grooming</option>
                        <option value="Lainnya" {{ old('type') == 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                    </select>
                </div>

                <!-- Unit -->
                <div class="form-group">
                    <label class="form-label required">Satuan</label>
                    <select name="unit" class="form-select" required>
                        <option value="pcs" {{ old('unit', 'pcs') == 'pcs' ? 'selected' : '' }}>pcs</option>
                        <option value="kg" {{ old('unit') == 'kg' ? 'selected' : '' }}>kg</option>
                        <option value="pouch" {{ old('unit') == 'pouch' ? 'selected' : '' }}>pouch</option>
                        <option value="kaleng" {{ old('unit') == 'kaleng' ? 'selected' : '' }}>kaleng</option>
                        <option value="botol" {{ old('unit') == 'botol' ? 'selected' : '' }}>botol</option>
                        <option value="pack" {{ old('unit') == 'pack' ? 'selected' : '' }}>pack</option>
                        <option value="karung" {{ old('unit') == 'karung' ? 'selected' : '' }}>karung</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <!-- SKU -->
                <div class="form-group">
                    <label class="form-label required">Kode SKU</label>
                    <input type="text" name="sku" class="form-control" 
                           value="{{ old('sku', $suggestedSku) }}" required>
                    <span class="form-text">Dibuat otomatis, dapat diubah sesuai kebutuhan toko.</span>
                </div>

                <!-- Barcode -->
                <div class="form-group">
                    <label class="form-label">Barcode Fisik</label>
                    <input type="text" name="barcode" class="form-control" 
                           value="{{ old('barcode') }}" placeholder="Scan barcode kemasan produk di sini">
                    <span class="form-text">Dapat di-scan langsung menggunakan scanner barcode USB.</span>
                </div>
            </div>

            <div class="form-row">
                <!-- Cost Price / HPP -->
                <div class="form-group">
                    <label class="form-label required">Harga Beli / HPP (Rp)</label>
                    <input type="number" name="cost_price" class="form-control" 
                           value="{{ old('cost_price', 0) }}" min="0" step="100" required>
                    <span class="form-text">Digunakan untuk menghitung estimasi laba kotor.</span>
                </div>

                <!-- Selling Price -->
                <div class="form-group">
                    <label class="form-label required">Harga Jual (Rp)</label>
                    <input type="number" name="selling_price" class="form-control" 
                           value="{{ old('selling_price', 0) }}" min="0" step="100" required>
                    <span class="form-text">Tarif harga jual ke pelanggan di kasir.</span>
                </div>
            </div>

            <div class="form-row">
                <!-- Initial Stock -->
                <div class="form-group">
                    <label class="form-label">Stok Awal</label>
                    <input type="number" name="stock" class="form-control" 
                           value="{{ old('stock', 0) }}" min="0">
                    <span class="form-text">Jumlah stok fisik saat pertama kali didaftarkan.</span>
                </div>

                <!-- Minimum Stock Alert -->
                <div class="form-group">
                    <label class="form-label required">Batas Stok Minimum</label>
                    <input type="number" name="min_stock" class="form-control" 
                           value="{{ old('min_stock', 5) }}" min="0" required>
                    <span class="form-text">Status akan berubah menjadi "Menipis" jika stok mencapai angka ini.</span>
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label">Deskripsi / Keterangan</label>
                <textarea name="description" class="form-control" rows="3" 
                          placeholder="Catatan tambahan tentang produk, varian rasa, atau aturan pakai...">{{ old('description') }}</textarea>
            </div>

            <!-- Is Active -->
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                    <span style="font-weight:600;">Produk Aktif (Tersedia untuk dijual di kasir)</span>
                </label>
            </div>

            <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1.5rem;padding-top:1rem;border-top:1px solid var(--border-light);">
                <a href="{{ route('products.index') }}" class="btn btn-outline">Batal</a>
                <button type="submit" class="btn btn-primary btn-lg">Simpan Produk Baru</button>
            </div>
        </form>
    </div>
</div>
@endsection
